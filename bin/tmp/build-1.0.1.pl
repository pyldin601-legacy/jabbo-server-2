#!/usr/bin/perl

use DBI;
use FindBin qw($Bin);

require "$Bin/tags.pl";

# mysql database
my $db_host = "127.0.0.1";
my $db_user = "root";
my $db_pass = "GDk4F/so";
my $db_base = "search";
my $dsn="dbi:mysql:$db_base:$db_host";

require "$Bin/tags.pl";

$dbh = DBI->connect($dsn, $db_user, $db_pass) || die "Can't connect to mysql!";
$dbh->do("set names 'utf8'");

$qh = $dbh->prepare("select * from `search_files` where `audio_artist` = 'Various Artists'");
$qh->execute();
$count = $qh->rows();
$position = 0;

while($row = $qh->fetchrow_hashref()) {
	$position ++;
	$rownum = $row->{index};
#	$words = lc($row->{filepath} . ' ' . $row->{filename} . ' ' . $row->{md5} . ' ' . $rownum . ' ' . $row->{filegroup});
#	print $words, "\n";
	@nwd = get_file_info($row->{filepath} . '/' . $row->{filename}, 'audio');
	$q = sprintf("update `search_files` set `audio_artist` = %s where `index` = '%d'", $dbh->quote($nwd[3]), $rownum);
	print $q , "\n";
	$dbh->do($q);
#	sleep 1;
}


$dbh->disconnect();
