#!/usr/bin/perl

exit if check_lock();
create_lock();

use DBI;
use POSIX;
use Digest::MD5::File qw( file_md5_hex );
use FindBin qw($Bin);
use POSIX qw(strftime);
use Date::Parse;

require "$Bin/tags.pl";

# path to folder with data
my @datadirs = (
	"/mnt/disk1",
	"/mnt/disk2",
	"/mnt/disk3",
#	"/mnt/disk4",
	"/mnt/disk5"
);

# list of excluded files and folders
my @exclude = ("/mnt/disk4");
my @denyfiles = ("jabbo.db", "Thumbs.db", "desktop.ini", "jaboutme.ini");

# additional parameters
my $readonly = 0;
my $timebegin = time();

# file grouping
my %formats = (
	'audio' 	=> ['mp3', 'ogg', 'm4a', 'tta', 'ape', 'wma', 'flac', 'wav'],
	'video' 	=> ['avi', 'mkv', 'flv', 'mp4', 'vob', 'wmv', 'mpeg', 'mpg', '3gp'],
	'image' 	=> ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'tif'],
	'archive'	=> ['rar', 'zip', '7z', 'tar', 'gz'],
	'iso'     	=> ['iso', 'isz', 'mds', 'mdf', 'nrg', 'bin', 'cue'],
	'playlist' 	=> ['m3u', 'pls']
);

# mysql database
my $db_host = "localhost";
my $db_user = "root";
my $db_pass = "";
my $db_base = "search";
my $dsn = "dbi:mysql:$db_base:$db_host";

my $stats = "$Bin/stats.log";

# cache variables
my %dhash = ();
my %filehash = ();
my $begin = time();

$SIG{'INT'} = sub {
    terminate();
};

uccons("Подключаюсь к базе данных...");
$dbh = DBI->connect($dsn, $db_user, $db_pass) || die "Не могу соединиться с базой данных!";

$dbh->do("SET NAMES 'utf8'");


# Loading table of indexed directories
uccons("Loading table of indexed directories...");
$res = $dbh->prepare("select * from `search_folders`");
$res->execute();

while( $row = $res->fetchrow_hashref() ) {
	@{$dhash{$row->{child}}} = ($row->{id}, str2time($row->{mtime}));
}


# Loading table of indexed files
uccons("Loading table of indexed files...");
$res = $dbh->prepare("select * from `search_files`");
$res->execute();
while($row = $res->fetchrow_hashref()) {
	push @{$filehash{$row->{filepath} . '/' . $row->{filename}}}, 
	$row->{index}, 
	str2time($row->{filemtime});
}


# Now we scan directories recursively and update information if it changed
foreach $datadir(@datadirs) {
	uccons("Scanning $datadir...");
	update_info_about_dir($datadir, '', 0);
	scan_dir($datadir);
}

foreach $key (keys %filehash) { 
	uccons("[xF] $key");
	$dbh->do(sprintf("delete from `search_files` where `index` = '%d'", @{$filehash{$key}}[0])) unless ($readonly == 1); 
}

foreach $key (keys %dhash) { 
	uccons("[xD] $key");
	$dbh->do(sprintf("delete from `search_folders` where `id` = '%d'", $dhash{$key}[0])) unless ($readonly == 1); 
}

uccons("Finishing...");

$dbh->do(sprintf("DELETE FROM rights WHERE touched < %d", $timebegin));
$dbh->do("optimize table `search_folders`");
$dbh->do("optimize table `search_files`");
$dbh->disconnect();

remove_lock();

exit;

sub get_directory_id() {
	my $parent_id = shift;
	my $name = shift;
	my $parent_id_sql = $dbh->quote($parent_id);
	my $name_sql = $dbh->quote($name);

	my $mtime = (stat($name))[9];

	$query = $dbh->prepare("SELECT * FROM `folders` WHERE `parent_id` = $parent_id_sql AND `name` = $name_sql LIMIT 1");
	$query->execute();
	if($query->rows() == 1) {
		my $row = $query->fetchrow_hashref();
		return $row->{id};
	} else {
		my $id = $dbh->do("INSERT INTO `folders` 
				(`parent_id`, `name`, `mtime`) 
				VALUES
				(${parent_id_sql}, ${name_sql}, ${mtime})");
		return $id;
	}
}

sub scan_dir() {
	my $path = shift;
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
			    $dbh->do(sprintf("REPLACE INTO rights VALUES (%s, %s, %d)", $dbh->quote($path . '/'), $dbh->quote($val), $timebegin));
			}
		    }
		} elsif(-e $fullfile) {
			my $fmtime = (stat($fullfile))[9];
#			uccons($fullfile);
			if(!exists($filehash{$fullfile})) {
				# index new file
				index_this_file($file, $path) unless ($readonly == 1);
			} elsif( $fmtime != @{$filehash{$fullfile}}[1] ) {
				# update this file
				update_this_file($file, $path) unless ($readonly == 1);
				delete( $filehash{$fullfile} );
			} else {
				# file not changed
				delete( $filehash{$fullfile} );
			}
		}
	}
	
	# continue recursion here
	foreach $dfile(@darray) {
		update_info_about_dir($path.'/'.$dfile, $path, $dhash{$path}[0]);
		scan_dir($path.'/'.$dfile);
	}

}

sub update_info_about_dir {
    my $path = shift;
    my $parent = $dbh->quote(shift);
    my $pid = $dbh->quote(shift);

    my $ddir = $dbh->quote($datadir);
    my $dmtime = (stat($path))[9];
    my $qfile = $dbh->quote($path);

    if(!exists($dhash{$path})) {
	uccons("[+D] $path");
	$dbh->do("
	    insert into `search_folders` 
		(`mtime`, `child`, `parent`, `root`) 
	    values 
		('" . getfulldate($dmtime) . "', $qfile, $parent, $ddir)");

    } elsif($dmtime != @{$dhash{$path}}[1]) {
	uccons("[uD] $path");
	$dbh->do(sprintf("update `search_folders` set `root` = %s, `mtime` = '" . getfulldate($dmtime) . "' where `id` = '%d'", $ddir, @{$dhash{$path}}[0]));
	delete($dhash{$path});
    } else {
	delete($dhash{$path});
    }
}

sub excluded {
	my $path = shift;
	foreach $fp (@denyfiles) {
		return 1 if ( $path =~ /\/$fp$/i );
	}
	foreach $path_ (@exclude) {
		return 1 if ( $path =~ /^$path_/i );
	}
	return 1 if ( $path =~ /\.part$/i );
	return 0;
}


sub update_this_file () {

	my @argm = ();
	my $fname = shift;
	my $fpath = shift;
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
	push @argm, @minfo; #8..15

	for my $n (0..$#argm) { $argm[$n] = $dbh->quote($argm[$n]); }
	
	$qr  = sprintf("update `search_files` set `filemtime` = %s, `filesize` = %s, `md5` = %s, `video_dimension` = %s, `avg_bitrate` = %s, `avg_duration` = %s, `audio_artist` = %s, `audio_title` = %s, `audio_album` = %s, `audio_tracknum` = %s where `filepath` = %s and `filename` = %s limit 1", @argm[4..6,8..14,1,0]);
#	print "$qr\n";
	$dbh->do($qr);
	return 0;
}

sub index_this_file () {

	my @argm = ();
	my $fname = shift;
	my $fpath = shift;
	my ($ftype) = $fname =~ /\.(\w+)$/;
	push @argm, $fname, $fpath, $ftype; # 0..2

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
	push @argm, @minfo; #8..15

	for my $n (0..$#argm) { $argm[$n] = $dbh->quote($argm[$n]); }

	my $moved;
	if($moved = get_lost_one($fhash, $fname, $fmtime)) {
		$qr  = sprintf("update `search_files` set `filename` = %s, `filepath` = %s, `filetype` = %s, `filegroup` = %s, `tags` = %s where `index` = '%d' limit 1", @argm[0..3, 7], $moved);
		uccons("[>F] $fpath/$fname");
	} else {
		$qr  = sprintf("insert into `search_files` (`filename`, `filepath`, `filetype`, `filegroup`, `filemtime`, `filesize`, `md5`, `tags`, `video_dimension`, `avg_bitrate`, `avg_duration`, `audio_artist`, `audio_title`, `audio_album`, `audio_tracknum`) values (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);", @argm);
		uccons("[+F] $fpath/$fname");
	}
	$dbh->do($qr);
	return 0;
}

sub get_lost_one {
	my $hash = shift;
	my $fname = $dbh->quote(shift);
	my $fmtime = getfulldate(shift);

	# test move
	my $result = $dbh->prepare(sprintf("select * from `search_files` where `filename` = %s AND `filemtime` = '%s'", $fname, $fmtime));
	$result->execute();
	while($array = $result->fetchrow_hashref()) {
		$fn = $array->{filepath} . '/' . $array->{filename};
		if(! -e $fn) {
			delete $filehash{$fn};
			return $array->{index};
		}
	}

	# test rename
	my $result = $dbh->prepare(sprintf("select * from `search_files` where `md5` = '%s'", $hash));
	$result->execute();
	while($array = $result->fetchrow_hashref()) {
		$fn = $array->{filepath} . '/' . $array->{filename};
		if(! -e $fn) {
			delete $filehash{$fn};
			return $array->{index};
		}
	}
	return;
}

sub getfulldate {
  @tm_arr = localtime($_[0]);
  return sprintf "%04d-%02d-%02d %02d:%02d:%02d", @tm_arr[5]+1900, @tm_arr[4]+1, @tm_arr[3], @tm_arr[2], @tm_arr[1], @tm_arr[0];
}

sub uccons() {
	my $text = shift;
	print seconds_to_time(time() - $begin);
	print " $text\n";
}


sub dotpoint {
  my $sign;
  my $inval=$_[0];
  if ($inval<0) { $sign="-"; } else { $sign=""; }
  return $sign.scalar reverse (join (' ', reverse ($inval) =~ m/\d{1,3}/g));
}

sub mseconds {
  ($mysec,$mymsec) = gettimeofday;
  return $mysec+($mymsec/1000000);
}

sub gethash {
    my $file = shift;
    if(-e $file) { return file_md5_hex($file); }
}

sub seconds_to_time() {
	my $sec = shift;
	return sprintf("%02d", int($sec/3600)) . ':' . sprintf("%02d", int($sec/60) % 60) . ':' . sprintf("%02d",  $sec % 60);
}

sub file_class {
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

sub terminate {
	remove_lock();
	exit;
}

sub create_lock {
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

sub getIndexedFiles() {
	my $directory = shift;
	my %results = ();
	uccons("Получаю данные об проиндексированных файлах в директории ${directory}..."):
	my $exec = $dbh->prepare(sprintf("SELECT * FROM `search_files` WHERE `filepath` = %s", $dbh->quote($directory)));
	$exec->execute();
	while(my $row = $dbh->fetchrow_hashref()) {
		%{$results{$row->{filepath} . "/" . $row->{filename}}} = 
		(
			mtime => $row->{filemtime}, 
			id => $row->{index}
		);
	}
	return %results;
}

sub getIndexedFolders() {
	my $directory = shift;
	my %results = ();
	uccons("Получаю данные об проиндексированных папках в директории ${directory}..."):
	my $exec = $dbh->prepare(sprintf("SELECT * FROM `search_folders` WHERE `parent` = %s", $dbh->quote($directory)));
	$exec->execute();
	while(my $row = $dbh->fetchrow_hashref()) {
		%{$results{$row->{child}}} = 
		(
			mtime => $row->{mtime}, 
			id => $row->{id}
		);
	}
	return %results;
}


sub getDirectoryFiles() 
{
	my $directory = shift;
	my @resultFiles = ();
	my @resultFolders = ();
	uccons("Получаю данные об файлах в директории ${directory}..."):
	opendir(FLD, $directory);
	while(my $file = readdir(FLD)) {
		next if($file eq "." || $file eq "..");
		push(@resultFolders, $directory . "/" . $file) if(-d $file);
		push(@resultFiles, $directory . "/" . $file) if(-e $file);
	}
	closedir(FLD);
	return (@resultFiles, @resultFolders);
}

sub removeAbsentFileFromIndex() 
{
	my $fileToRemove = shift;
	$dbh->do(sprintf("DELETE FROM `search_files` WHERE id = %d LIMIT 1", $dbh->quote($fileToRemove)));
}

sub removeAbsentDirFromIndex() 
{
	my $dirToRemove = shift;
	$dbh->do(sprintf("DELETE FROM `search_files` WHERE id = %d LIMIT 1", $dbh->quote($dirToRemove)));
}

sub processBegin() 
{
	my $startPoint = shift;
	
}

sub processRecursion() 
{

	my $startPoint = shift;
	my (@files, @dirs) = getDirectoryFiles($startPoint);
	my %indexFiles = getIndexedFiles($startPoint);
	my %indexFolders = getIndexedFolders($startPoint);

	# process included files
	foreach my $file (@files) 
	{
		if(exists($indexFiles{$file})) 
		{
			my $fileModified = (stat($file))[9];
			if($fileModified != $indexFiles{$file}{mtime}) 
			{
				# update Indexed file
			}
			delete($indexFiles{$file});
		} 
		else 
		{
			# index new file here
		}
	}

	# process included directories
	foreach my $path (@dirs) 
	{
		if(exists($indexFolders{$path})) 
		{
			my $fileModified = (stat($path))[9];
			if($fileModified != $indexFolders{$path}{mtime}) 
			{
				# update Indexed directory
			}
			delete($indexFolders{$path});
		} 
		else 
		{
			# index new directory here
		}
	}

}