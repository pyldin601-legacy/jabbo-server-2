<?php

// GET AUDIO FILE IN MP3 320K

require 'config.php';
require 'core/general.inc.php';
require 'core/auth.inc.php';
require 'core/common.php';
session_write_close();

$inbitrate = 320;
$inchannel = 2;

$_GET = get_query_string();

$index 		= isset($_GET['id']) 	? 	(int) $_GET['id'] 			: false;
$filename 	= isset($_GET['file']) 	? 	urldecode($_GET['file']) 	: false;
$test 		= isset($_GET['t'])		?	$_GET['t']					: false;


if( $index && $filename ) {

    $result = mysql_query(sprintf("SELECT * FROM `search_files` WHERE `index` = '%d' LIMIT 1", $index));

	if(mysql_num_rows($result) == 1) {

		$row = mysql_fetch_assoc($result);

		if(replace_extension($row['filename'], 'mp3') != $filename) {
			header('HTTP/1.1 404 Not Found');
			die("HTTP/1.1 404 File Not Found");
		}

/*		if(get_challenge($row['index']) != $test) {
			header("HTTP/1.1 403 Forbidden");
			die('Access denied!');
		} */
		
		if(! is_allowed($row['filepath'], $uid['uid']) ) {
			header('HTTP/1.1 403 Forbidden');
			die('Access denied!');
		}
		
		$ftype = strtolower($row['filetype']);
		$mtime = filemtime($row['filepath'] . '/' . $row['filename']);

		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mtime) {
			header('HTTP/1.1 304 Not Modified');
			die();
		} else {
			header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mtime) . " GMT");
			header('Cache-Control: max-age=0');
		}

		$files_array = escapeshellarg($row['filepath'] . '/' . $row['filename']);

		header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
		header('Content-Type: audio/mp3');
		header('Content-Description: File Transfer');

		if($ftype == 'mp3') {
			header('Content-Disposition: filename="' . $row['filename'] . '"');
			do_this("/bin/cat ${files_array}");
		} else {
			header('Content-Disposition: filename="' . replace_extension($row['filename'], 'mp3') . '"');
			do_this($cfg['restreamer']['convert'] . " -i ${files_array} -vn -ar 44100 -ac 2 -ab ${inbitrate}k -f mp3 - 2>/dev/null");
		}
		inclease_play_count($row['index']);
		
		mysql_query("INSERT INTO `jfs_file_stats` VALUES(${row['index']}, ${uid['uid']}, 1, 0, 0)
				 ON DUPLICATE KEY
				 UPDATE `downloads` = `downloads` + 1");

	}
}

dbClose();


function do_this( $command ) {

	$kick_block = 4096;
	
	$proc = popen($command, "r");
  	if(! $proc) return die();
	
	while( $data = fread($proc, $kick_block) ) {
		echo $data;
		flush();
		set_time_limit(30);
	}

	pclose( $proc );
	return true;
  
}


?>