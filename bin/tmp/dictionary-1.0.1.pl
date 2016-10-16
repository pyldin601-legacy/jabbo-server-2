#!/usr/bin/perl

use DBI;
use POSIX;
use Digest::MD5::File qw( file_md5_hex );
use FindBin qw($Bin);
use POSIX qw(strftime);
use Date::Parse;

require "$Bin/tags.pl";

# mysql database
my $db_host = "localhost";
my $db_user = "root";
my $db_pass = "";
my $db_base = "search";
my $dsn = "dbi:mysql:$db_base:$db_host";
my $stats = "$Bin/indexing.stat";
my $coversDir = "$Bin/../images/covers/";

$dbh = DBI->connect($dsn, $db_user, $db_pass) || die "Can't connect to mysql!";
$dbh->do("set names 'utf8'");


# Loading table of indexed directories
$res = $dbh->prepare("select * from `search_files`");
$res->execute();
while($row = $res->fetchrow_hashref()) { 
    $filename = $row->{filepath} . '/' . $row->{filename} . '/' . $row->{md5};
    $tags = get_tags($filename);
    if($row->{tags} ne $tags) {
	$id = $row->{index};
	$dbh->do("UPDATE `search_files` SET `tags` = '$tags' WHERE `index` = '$id'");
	print "Update $id: $tags\n";
    }
}

print "Finishing...\n";
$dbh->do("optimize table `search_files`");
$dbh->disconnect();


