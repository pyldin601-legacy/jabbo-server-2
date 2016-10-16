#!/usr/bin/perl

use DBI;
use FindBin qw($Bin);

# mysql database
my $db_host = "localhost";
my $db_user = "root";
my $db_pass = "";
my $db_base = "search";
my $dsn="dbi:mysql:$db_base:$db_host";

require "$Bin/tags.pl";

$dbh = DBI->connect($dsn, $db_user, $db_pass) || die "Can't connect to mysql!";
$dbh->do("set names 'utf8'");

print "====================================================\n";
print "             Jabbo Search Engine v 2.0\n";
print "====================================================\n";
print "Database statistics:\n";

$res = $dbh->prepare("SELECT COUNT(*), SUM(filesize) as sum FROM search_files WHERE 1");
$res->execute();
($files, $size) = $res->fetchrow_array();
$res->finish();

print "  Count of indexed files:\n\t$files ($size bytes)\n";

$dirs = $dbh->do("SELECT * FROM search_folders WHERE 1");
print "  Count of indexed directories: \n\t$dirs\n";

$res = $dbh->prepare("SELECT count(count), sum(size) FROM (SELECT count(md5) as count, SUM(filesize) as size FROM search_files GROUP BY md5 HAVING(count(md5)>1)) as subq");
$res->execute();
($files, $size) = $res->fetchrow_array();
$res->finish();

print "  Count of files which having duplicates:\n\t$files ($size bytes)\n";
<>;
print "  Duplicates list:\n";

$res = $dbh->prepare("SELECT `md5` FROM search_files GROUP BY `md5` HAVING(count(`md5`)>1)");
$res->execute();
while( $row = $res->fetchrow_hashref() ) {
	$m5 = $row->{md5};
	printf("%s:\n", $m5);
	$fl = $dbh->prepare("SELECT CONCAT(`filepath`,`filename`) FROM `search_files` WHERE `md5` = '$m5'");
	$fl->execute();
	while( my ($fn) = $fl->fetchrow_array() ) {
		print "$fn\n";
	}
}

$dbh->disconnect();
