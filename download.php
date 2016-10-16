<?php

require 'config.php';
require 'core/common.php';
require 'core/jabbo.inc.php';
require 'core/auth.inc.php';
require 'core/general.inc.php';

//session_write_close();

// fix url decode
$_GET = get_query_string();

// test get parameters
$index      = isset($_GET['id'])    ? (int) $_GET['id']		: false;
$filename   = isset($_GET['file'])  ? $_GET['file']  		: false;
$filepath   = isset($_GET['path'])  ? $_GET['path']  		: false;

if( $index && $filename ) {
	$query = sprintf("SELECT * FROM `search_files` WHERE `index` = '%d' AND `filename` = '%s' LIMIT 1", 
        $index, mysql_real_escape_string($filename));
} elseif( $filepath && $filename ) {
	$query = sprintf("SELECT * FROM `search_files` WHERE `filepath` = '%s' AND `filename` = '%s' LIMIT 1", 
        mysql_real_escape_string($filepath), mysql_real_escape_string($filename));
} elseif( $index ) {
	$query = sprintf("SELECT * FROM `search_files` WHERE `index` = '%d' LIMIT 1", 
        $index);
} elseif( $filename ) {
	$query = sprintf("SELECT * FROM `search_files` WHERE `filename` = '%s' LIMIT 1", 
        mysql_real_escape_string($filename));
} else {
	die('<html><h1>Nothing to download!</h1></html>');
}

if( ! $result = mysql_query($query) )
	die('<html><h1>Database error!</h1></html>');

if( mysql_num_rows($result) == 0 ) {
	header('HTTP/1.1 404 Not Found');
	die('<html><h1>File not found!</h1></html>');
}

$row = mysql_fetch_assoc($result);
$sfile = $row['filepath'] . '/' . $row['filename'];

if(! (is_allowed($row['filepath'], $uid['uid']) || is_allowed_ch($row['index'])) ) {
	header('HTTP/1.1 403 Forbidden');
	die();
}

if(! file_exists($sfile) ) { 
	header('HTTP/1.1 404 Not Found');
	die('<html><h1>File not found!</h1></html>');
}

$fsize = filesize($sfile);

if (isset($_SERVER['HTTP_RANGE'])) {
	$range = $_SERVER['HTTP_RANGE'];
	$range = str_replace('bytes=', '', $range);
	list($start, $end) = explode('-', $range);
} else {
	$start = 0; 
}

$end = $fsize;

if(isset($range)) {
	header($_SERVER['SERVER_PROTOCOL'].' 206 Partial Content');
} else {
	header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
}

header('Content-Description: File Transfer');
#header(sprintf('Content-Disposition: filename="%s"', $row['filename']));
header('Accept-Ranges: bytes');
header('Content-length: ' . $fsize);

if(isset($range)) {
	header("Content-Range: bytes " . $start . "-" . $fsize . "/" . $fsize);
}

$mime = mime_content_type_my($sfile);

header('Content-Type: ' . $mime);

$fh = fopen($sfile, "r");
if (isset($range)) {
	fseek($fh, $start);
} else {

	increase_download_count($uid['uid']);

	mysql_query("INSERT INTO `jfs_file_stats` VALUES(${row['index']}, ${uid['uid']}, 1, 0, 0)
				 ON DUPLICATE KEY
				 UPDATE `downloads` = `downloads` + 1");

}

while(!feof($fh)) {
	set_time_limit(30);
	$data = fread($fh, $cfg['download']['buffer_size']);
	echo $data;
	flush();
}

tlog("download: filename=\"${row['filepath']}/${row['filename']}\"");

my_users_unique("download");
dbClose();

?>