#!/usr/bin/perl

use DBI;
use POSIX;
use Digest::MD5;
use FindBin qw($Bin);
use POSIX qw(strftime);
use Date::Parse;
use Getopt::Long;
use Time::HiRes qw(nanosleep);

exit if check_lock();
create_lock();

require "$Bin/tags.pl";

chdir($Bin);

my $where = '/';
my $silent = 0;

$result = GetOptions("d=s" => \$where, "silent" => \$silent);

# path to folder with data
#my @datadirs = ( "/mnt/disk1", "/mnt/disk2", "/mnt/disk4", "/mnt/disk5" );
my @datadirs = ( "/media" );

my @denyfiles = ( "jabbo.db", "Thumbs.db", "desktop.ini", "jaboutme.ini", ".snap" );
my @exclude = ( "/media/#BACKUP", "/media/#ETC" );

my @alwaysReindex = ();

my $readonly = 0;

# file group
my %formats = (
    'audio'     => ['mp1', 'mp2', 'mp3', 'ogg', 'm4a', 'tta', 'ape', 'wma', 'flac', 'wav', 'xm', 'mod'],
    'video'     => ['avi', 'mkv', 'flv', 'mp4', 'vob', 'wmv', 'mpeg', 'mpg', '3gp'],
    'image'     => ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'tif'],
    'archive'   => ['rar', 'zip', '7z', 'tar', 'gz'],
    'iso'       => ['iso', 'isz', 'mds', 'mdf', 'nrg', 'bin', 'cue'],
    'playlist'  => ['m3u', 'pls']
);

# mysql database
my $db_host = "localhost";
my $db_user = "root";
my $db_pass = "";
my $db_base = "search";
my $dsn = "dbi:mysql:database=$db_base:mysql_socket=/tmp/mysql.sock";
my $stats = "$Bin/indexing.stat";

# cache variables
%directoriesHash = ();
%filesHash = ();

my $begin = time;

$SIG{'INT'} = sub {
    terminate();
};

uccons("Connecting to mysql...");
$dbh = DBI->connect($dsn, $db_user, $db_pass) || die "Can't connect to mysql!";
$dbh->do("set names 'utf8'");
#$dbh->do("START TRANSACTION");

# load indexed folders
uccons("Loading folders index...");
$res = $dbh->prepare("SELECT * FROM `search_folders` WHERE 1");
$res->execute();
while($row = $res->fetchrow_hashref()) {
	if($row->{parent} eq '') { 
		$hash = $row->{root}; 
	} else { 
		$hash = $row->{parent} . '/' . $row->{child}; 
	}

	next unless filter($hash);

	%{$directoriesHash{$hash}} = (
		id => $row->{id}, 
		mtime => str2time($row->{mtime}),
	);
}


# Loading table of indexed files
uccons("Loading file index...");
$res = $dbh->prepare("SELECT * FROM `search_files` WHERE 1");
$res->execute();
while($row = $res->fetchrow_hashref()) {
	next unless filter($row->{filepath});
	%{$filesHash{$row->{filepath} . '/' . $row->{filename}}} = ( 
		id => $row->{index}, 
		mtime => str2time($row->{filemtime})
	);
}

# Now we scan directories recursively and update information if it changed
foreach $datadir(@datadirs) {
    uccons("Scanning $datadir...");
    $dataDir_sql = $dbh->quote($datadir);
    update_info_about_dir($datadir, '');
    scan_dir($datadir);
}

foreach $key (keys %filesHash) {
    uccons("[xF] $key");
    $dbh->do("delete from `search_files` where `index` = ?", undef, $filesHash{$key}{id}) unless ($readonly == 1);
    $dbh->do("delete from `jfs_file_stats`  where `id` = ?", undef, $filesHash{$key}{id}) unless ($readonly == 1);
}

foreach $key (keys %directoriesHash) {
    uccons("[xD] $key");
    $dbh->do("delete from `search_folders` where `id` = ?", undef, $directoriesHash{$key}{id}) unless ($readonly == 1); 
    $dbh->do("delete from `jfs_dir_stats` where `id` = ?", undef, $directoriesHash{$key}{id}) unless ($readonly == 1); 
}

uccons("Finishing...");

$dbh->do("DELETE FROM rights WHERE touched < ?", undef, $begin);

#$dbh->do("COMMIT");

#$dbh->do("optimize table `search_folders`");
#$dbh->do("optimize table `search_files`");
$dbh->disconnect();

remove_lock();

exit;

sub scan_dir() {
    my $path = shift;

    return unless filter($path);

    my $q_path = $dbh->quote($path);

    my @darray = ();
    opendir(DIR, $path) || return -1; 
    my @tmp = readdir DIR;
    closedir(DIR);
    foreach my $file (sort @tmp) {
        $fullfile = $path . '/' . $file;
        next if(excluded($fullfile));
        if(-d $fullfile) {
            next if(($file eq '.') or ($file eq '..'));
            push(@darray, $file);
        } elsif($file eq '.jabbo_access') {
            # take info about directory rights
            open TMP, "<", $fullfile;
            @buffr = join('', <TMP>);
            close TMP;
            foreach $line (@buffr) {
                chomp $line;
                ($key, $val) = split(':', $line);
                if($key eq 'allow') {
                    $dbh->do("REPLACE INTO rights VALUES (?, ?, ?)", undef, $path . '/', $val, $begin);
                }
            }
        } elsif($file =~ /^(\.)/) {
			next;
		} elsif(-e $fullfile) {
            my $fmtime = (stat($fullfile))[9];
            if(!exists($filesHash{$fullfile})) {
                # index new file
                index_this_file($file, $path) unless ($readonly == 1);
            } elsif( $fmtime != $filesHash{$fullfile}{mtime} || checkReindexing($path) == 1 ) {
                # update this file
                update_this_file($file, $path, $filesHash{$fullfile}{id}) unless ($readonly == 1);
                delete( $filesHash{$fullfile} );
            } else {
                # file not changed
                delete( $filesHash{$fullfile} );
            }
        }
    }
    
    # continue recursion here
    foreach $dfile(@darray) {
        update_info_about_dir($path.'/'.$dfile, $path);
        scan_dir($path.'/'.$dfile);
    }

}

sub update_info_about_dir()
{
    my $path = shift;
    my $parent = shift;

	next unless filter($path);

    my $ddir = $datadir;
    my $dmtime = (stat($path))[9];
    my $qfile = (split('/', $path))[-1];

    if(!exists($directoriesHash{$path})) 
    {
        uccons("[+D] $path");
        $dbh->do("insert into `search_folders` (`mtime`, `child`, `parent`, `root`) 
        		values (?, ?, ?, ?)", undef, getfulldate($dmtime), $qfile, $parent, $ddir);

    } 
    elsif($dmtime != $directoriesHash{$path}{mtime}) 
    {
        uccons("[uD] $path");
        $dbh->do("update `search_folders` set `root` = ?, `mtime` = ? where `id` = ?", undef, $ddir, getfulldate($dmtime), $directoriesHash{$path}{id});
        delete($directoriesHash{$path});
    }
    else
    {
        delete($directoriesHash{$path});
    }
}

sub excluded
{
    my $path = shift;
    foreach $fp (@denyfiles) 
    {
        return 1 if ( $path =~ /\/$fp$/i );
    }
    foreach $fp (@exclude) 
    {
        return 1 if ( $path =~ /^$fp/i );
    }
    return 1 if ( $path =~ /\.part$/i );
    return 0;
}


sub update_this_file () 
{

    my @argm = ();
    my $fname = shift;
    my $fpath = shift;
    my $id = shift;
    
    my ($ftype) = $fname =~ /\.(\w+)$/;
    push @argm, $fname, $fpath, $ftype; # 0..2

    uccons ("[uF] $fpath/$fname");

    my $fclass = file_class($ftype);
    push @argm, $fclass; # 3

    my $filename = $fpath . '/' . $fname;
    
    my $fmtime = getfulldate((stat($filename))[9]);
    push @argm, $fmtime; #4

    my $fsize = -s $filename;
    push @argm, $fsize; #5

    my $fhash = gethash($filename);
    push @argm, $fhash; #6
    
    my $ftags = get_tags($filename . '/' . $fhash);
    push @argm, $ftags; #7
    
    my @minfo = get_file_info($filename, $fclass);
    push @argm, @minfo; #8..16
    
    push @argm, $id; # 17 File ID

    for my $n (0..$#argm) 
    { 
        $argm[$n] = $dbh->quote($argm[$n]); 
    }
    
    $qr  = sprintf
    ("	update
	`search_files` 
	set 
	    `filemtime` = %s, 
	    `filesize` = %s, 
	    `md5` = %s, 
	    `video_dimension` = %s, 
	    `avg_bitrate` = %s, 
	    `avg_duration` = %s, 
	    `audio_artist` = %s, 
	    `audio_band` = %s, 
	    `audio_title` = %s, 
	    `audio_album` = %s, 
	    `audio_tracknum` = %s,
	    `audio_genre` = %s
	where 
	    `index` = %s 
	limit 1", 
	
	@argm[4..6,8..16,17]);
	$affected = $dbh->do($qr);
	uccons("No rows affected: $qr") if ($affected eq '0E0');
	return 0;
}

sub index_this_file () 
{

    my @argm = ();
    my $fname = shift;
    my $fpath = shift;
    my ($ftype) = $fname =~ /\.(\w+)$/;
    push @argm, $fname, $fpath, $ftype; # 0..2

    my $fclass = file_class($ftype);
    push @argm, $fclass; #3

    my $filename = $fpath . '/' . $fname;
    my $fmtime = getfulldate((stat($filename))[9]);
    push @argm, $fmtime; #4

    my $fsize = -s $filename;
    push @argm, $fsize; #5

    my $fhash = gethash($filename);
    push @argm, $fhash; #6

    my $ftags = get_tags($filename);
    push @argm, $ftags; #7

    my @minfo = get_file_info($filename, $fclass);
    push @argm, @minfo; #8..16

#    print Dumper(@argm);

    for my $n (0..$#argm) { $argm[$n] = $dbh->quote($argm[$n]); }

    
    my $moved;
    if($moved = get_lost_one($fhash, $fname, $fmtime)) {
        $qr  = sprintf("update `search_files` set `filename` = %s, `filepath` = %s, `filetype` = %s, `filegroup` = %s, `tags` = %s where `index` = '%d' limit 1", @argm[0..3, 7], $moved);
        uccons("[>F] $fpath/$fname");
    } else {
        $qr  = sprintf("insert into `search_files` (`filename`, `filepath`, `filetype`, `filegroup`, `filemtime`, `filesize`, `md5`, `tags`, `video_dimension`, `avg_bitrate`, `avg_duration`, `audio_artist`, `audio_band`, `audio_title`, `audio_album`, `audio_tracknum`, `audio_genre`) values (%s)", join(', ', @argm));
        uccons("[+F] $fpath/$fname");
    }
    $dbh->do($qr);
    return 0;
}

sub get_lost_one {
    
    return;
    
    my $hash = shift;
    my $fname = $dbh->quote(shift);
    my $fmtime = getfulldate(shift);

    # test move : name
    my $result = $dbh->prepare(sprintf("select * from `search_files` where `filename` = %s", $fname));
    $result->execute();
    while($array = $result->fetchrow_hashref()) {
        $fn = $array->{filepath} . '/' . $array->{filename};
        if(! -e $fn) {
		$result->finish();
            delete $filesHash{$fn};
            uccons(">> FILE MOVED >>");
            return $array->{index};
        }
    }

    return;

    # test rename : hash
    my $result = $dbh->prepare(sprintf("select * from `search_files` where `md5` = '%s'", $hash));
    $result->execute();
    while($array = $result->fetchrow_hashref()) 
    {
        $fn = $array->{filepath} . '/' . $array->{filename};
        if(! -e $fn) 
        {
		$result->finish();
            delete $filesHash{$fn};
            uccons(">> FILE MOVED AND RENAMED>>");
            return $array->{index};
        }
    }
    
    return;
}

sub getfulldate()
{
    @tm_arr = localtime($_[0]);
    return sprintf "%04d-%02d-%02d %02d:%02d:%02d", @tm_arr[5]+1900, @tm_arr[4]+1, @tm_arr[3], @tm_arr[2], @tm_arr[1], @tm_arr[0];
}

sub uccons() 
{
    my $text = shift;
    print seconds_to_time(time() - $begin);
    print " $text\n";
}


sub dotpoint()
{
    my $sign;
    my $inval=$_[0];
    if ($inval<0) { $sign="-"; } else { $sign=""; }
    return $sign.scalar reverse (join (' ', reverse ($inval) =~ m/\d{1,3}/g));
}

sub mseconds()
{
    ($mysec,$mymsec) = gettimeofday;
    return $mysec+($mymsec/1000000);
}

sub gethash()
{
    my $file = shift;
    if(-e $file) { return file_md5_hex_slow($file); }
}

sub seconds_to_time() 
{
    my $sec = shift;
    return sprintf("%02d", int($sec/3600)) . ':' . sprintf("%02d", int($sec/60) % 60) . ':' . sprintf("%02d",  $sec % 60);
}

sub file_class 
{
    my $ftype = shift;
    foreach my $key (keys %formats) {
        foreach my $subkey (@{$formats{$key}}) {
            if($subkey eq lc($ftype)) {
                return $key;
            }
        }
    }
    return 'file';
}

sub terminate() 
{
    remove_lock();
    exit;
}

sub create_lock() 
{
    print "[LOCK] CREATING\n";
    open PD, ">", "$Bin/run/index.pid";
    print PD $$;
    close PD;
}

sub remove_lock() {
    print "[LOCK] REMOVING\n";
    unlink "$Bin/run/index.pid";
}

sub check_lock() {

    return 0 if (! -e "$Bin/run/index.pid");

    open PD, "<", "$Bin/run/index.pid";
    $pid = <PD>;
    close PD;
    chomp $pid;

    return 0 unless ( kill 0, $pid );

    print "[LOCK] LOCKED!\n";
    return 1;

}

sub checkReindexing() {
    my $where = shift;
    foreach $val (@alwaysReindex) {
	return 1 if ( $where =~ m/^$val/ );
    }
    return 0;
}

sub filter() {
	my $path = shift;
	if($path =~ /^$where/) {
		return 1;
	} else {
		return 0;
	}
}

sub file_md5_hex_slow {
    my $fn = shift;
    my $ctx = Digest::MD5->new;
    my $buffer;
    open FILE, "<", $fn;
    binmode FILE;
    while(read(FILE, $buf, 32767)) {
	$ctx->add($buf);
#	nanosleep(1);
    }
    close FILE;
    return $ctx->hexdigest;
}