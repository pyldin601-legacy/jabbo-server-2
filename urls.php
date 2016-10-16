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

$query = mysql_query("SELECT * FROM `search_files` WHERE `filepath` = '$location' ORDER BY `filepath`, `filename`");

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');

$mpeg = array();

while($row = mysql_fetch_assoc($query)) {
	$href = 'http://jabbo.tedirens.com/fs-' . $uid['session'] . my_rawurlencode($row['filepath'] . '/' . $row['filename']);
//	echo "http://jabbo.tedirens.com/file-" . $row['index'] . "-" . $uid['session'] . "/" . rawurlencode($row['filename']) . "\r\n";
	echo "$href\r\n";
	if($row['filegroup'] == 'audio' && $row['filetype'] != 'mp3')
		array_push($mpeg, "http://jabbo.tedirens.com/mp3-" . $row['index'] . "-" . $uid['session'] . "/" . rawurlencode(replace_extension($row['filename'], 'mp3')) . "\r\n");
}

echo implode('', $mpeg);

dbClose();

?>