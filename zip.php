<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/common.php';
session_write_close();

$env = array();
$suffix = md5(time()) . '.log';
$tmpname = '/tmp/error-zip-' . $suffix;
$donename = '/tmp/done-zip-' . $suffix;


$_GET = get_query_string();

if( ! isset($_GET['d'], $_GET['f']) ) die("Nothing to do");

$location_id = (int) $_GET['d'];
$locatName = mysql_real_escape_string($_GET['f']);

$result = mysql_query("SELECT * FROM `search_folders` WHERE `id` = '$location_id' AND `child` = '$locatName' LIMIT 1");

if( ! $result ) die('<h1>Database error!</h1>');

if(mysql_num_rows($result) == 0) {
	header("HTTP/1.1 404 Not found");
	die('<h1>File not found!</h1>');
}

$row = mysql_fetch_assoc($result);

if($row['parent'] == '')
	$location_path = $row['root'];
else
	$location_path = $row['parent'] . '/' . $row['child'];

if( ! is_allowed($location_path, $uid['uid']) ) {
	header("HTTP/1.1 403 Forbidden");
	die('<h1>Access denied!</h1>');
}


$location_folder = substr($location_path, strrpos($location_path, '/') + 1);

$descriptorspec = array(
   0 => array("pipe", "r"),
   1 => array("pipe", "w"),
   2 => array("file", $tmpname, "a")
);

$lp = mysql_real_escape_string($location_path);

$result = mysql_query("select * from `search_files` where `filepath` LIKE '${lp}/%' or `filepath` = '${lp}' order by `filepath`,`filename`");

if(mysql_num_rows($result) != 0) {

	$process = proc_open('/usr/local/bin/zip - -@', $descriptorspec, $pipes, $location_path, $env);

	if(is_resource($process)) {

		while($row = mysql_fetch_assoc($result)) {
			$relative_path = substr($row['filepath'], strlen($location_path) + 1);
			$relative_path = $relative_path ? $relative_path . '/' : '';
			if(is_allowed($row['filepath'], $uid['uid']))
				fwrite($pipes[0], $relative_path . $row['filename'] . "\n");
		}
		fclose($pipes[0]);

		header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
		//header('Content-Disposition: filename="' . $location_id . '-' . $location_folder . '.zip"');
		header('Content-Type: application/octet-stream');

		while($data = stream_get_contents($pipes[1], 4096)) {
			set_time_limit(30);
			echo $data;
			flush();
		}
		fclose($pipes[1]);
		rename($tmpname, $donename);
		
		mysql_query("update `search_users` set `zip_count`=`zip_count`+1 where `uid` = '${uid['uid']}'");
		mysql_query("INSERT INTO `jfs_dir_stats` VALUES(${location_id}, 0, 1)
					 ON DUPLICATE KEY
					 UPDATE `zipped` = `zipped` + 1");

	}
}


dbClose();

?>