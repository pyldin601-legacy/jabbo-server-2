<?php

require 'config.php';
require 'core/general.inc.php';
require 'core/auth.inc.php';
require 'core/common.php';

header("Content-type:text/plain");

if(empty($_GET['id'])) {
	header("HTTP/1.1 404 Not Found");
	die("404 Not Found");
}

$id = (int) $_GET['id'];

$query = mysql_query("SELECT * FROM `search_folders` WHERE `id` = '$id' LIMIT 1");

if(mysql_num_rows($query) == 0) {
	header("HTTP/1.1 404 Not Found");
	die("404 Not Found");
}

$row = mysql_fetch_assoc($query);

$location = mysql_real_escape_string( $row['parent'] . '/' . $row['child'] );

$query = mysql_query("SELECT * FROM `search_files` WHERE `filepath` = '$location' AND `filegroup` = 'audio' ORDER BY `filepath`, `filename`");

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream; charset=utf-8');

echo "#EXTM3U\r\n";
while($row = mysql_fetch_assoc($query)) {
	echo "#EXTINF:" . (int)($row['avg_duration'] / 1000) . "," . $row['audio_artist'] . " - " . $row['audio_title'] . "\r\n";
	/*if(preg_match('/^192\.168\.1\./', $_SERVER['REMOTE_ADDR'])) {
		echo "\\\\" . $_SERVER['SERVER_ADDR'] . str_replace("/", "\\", str_replace("/mnt", "", $row['filepath'])) . "\\" . $row['filename'] . "\r\n";;
	} else {*/
		echo "http://jabbo.tedirens.com/prelisten-" . $uid['session'] . '/' . $row['index'] . ".mp3\r\n";
		//echo rawurlencode(replace_extension($row['filename'], 'mp3')) . "\r\n";
	//}
}


dbClose();

?>