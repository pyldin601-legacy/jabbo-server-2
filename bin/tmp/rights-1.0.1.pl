#!/usr/bin/perl

use DBI;
use FindBin qw($Bin);

# mysql database
my $db_host = "127.0.0.1";
my $db_user = "root";
my $db_pass = "";
my $db_base = "search";
my $dsn="dbi:mysql:$db_base:$db_host";

require "$Bin/tags.pl";

$dbh = DBI->connect($dsn, $db_user, $db_pass) || die "Can't connect to mysql!";
$dbh->do("set names 'utf8'");

$qh = $dbh->prepare("select * from `search_folders` where 1");
$qh->execute();

while($row = $qh->fetchrow_hashref()) {
}

$dbh->disconnect();
