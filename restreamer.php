<?php

/* Audio streaming script for Jabbo File Server */

require 'config.php';
require 'core/general.inc.php';
require 'core/auth.inc.php';
require 'core/common.php';

//session_write_close();

$_GET = get_query_string();

if( preg_match("/^192\.168\.|127\.0\.0\.1/", $_SERVER['REMOTE_ADDR']) )
	$params = array(320, 2, 0, true);
elseif( preg_match("/^(87.245.220.242)$/", $_SERVER['REMOTE_ADDR']) )
	$params = array(96, 2, 1, true);
elseif( $ismobile )
	$params = array(192, 2, 0, false);
else
	$params = array(256, 2, 0, true);

//$params = array(16, 2, 0, true);


list( $inbitrate, $inchannel, $throttle, $showsize ) = $params;


if(isset($_GET['id']) || isset($_GET['file'])) {

    $index = isset($_GET['id']) ? (int) $_GET['id'] : false;
    $file  = isset($_GET['file']) ?  mysql_real_escape_string(urldecode($_GET['file'])) : false;

    if( $file === 'random_mix.mp3' ) {
		$result = mysql_query("select * from `search_files` where `filepath` like '/mnt/disk1/Music/Special/Solarsoul/Mixes \& Lives%' order by rand() limit 1");
    } elseif( $index ) {
        $result = mysql_query("select * from `search_files` where `index` = '${index}' limit 1");
    } else {
        $result = mysql_query("select * from `search_files` where `filename` = '${file}' limit 1");
    }

	if(mysql_num_rows($result) == 1) {

		$row = mysql_fetch_assoc($result);
				
		if( ! $allow = is_allowed($row['filepath'], $uid['uid']) ) {
			header('HTTP/1.1 403 Forbidden');
			die('HTTP/1.1 403 Forbidden');
		} else {

			$ftype = strtolower($row['filetype']);
			$fsize = filesize($row['filepath'] . '/' . $row['filename']);
			$mtime = filemtime($row['filepath'] . '/' . $row['filename']);
			$hash = $row['md5'];

			if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mtime) {
				header('HTTP/1.1 304 Not Modified');
				die();
			} else {
				header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mtime) . " GMT");
				header('Cache-Control: max-age=0');
			}

			$files_array = escapeshellarg($row['filepath'] . '/' . $row['filename']);

			$bitrate = get_audio_bitrate($row['filepath'] . '/' . $row['filename']);
			if($bitrate > 0)
				$global_duration = $fsize / ($bitrate / 8);
			else
				$global_duration = 0;

			
			header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
			header('Content-Type: audio/mp3');
			header('Content-Description: File Transfer');

			tlog("Parameters: BPS=$inbitrate CHS=$inchannel THR=$throttle SIZ=$showsize FN=${row['filename']}");

			if($ftype == 'mp3' && ($bitrate / 1000) <= $inbitrate) {
				$tmpsize = $fsize;
				header(sprintf('Content-length: %d', $fsize));
				header('Content-Disposition: filename="' . $row['filename'] . '"');
				do_this('/bin/cat ' . $files_array, $tmpsize);
			} else {
				if($showsize && $global_duration) {
					$tmpsize = (int) ceil($global_duration * $inbitrate * 125);
					header(sprintf('Content-length: %d', $tmpsize));
				} else {
					$tmpsize = -1;
				}
				header('Content-Disposition: filename="' . replace_extension($row['filename'], 'mp3') . '"');
				do_this($cfg['restreamer']['convert'] . " -i ${files_array} -vn -ab 16 -ar 44100 -ac ${inchannel} -ab ${inbitrate}k -f mp3 - 2>/dev/null", $tmpsize);
			}
		}
	}
}

my_users_unique("listen");

dbClose();


function get_audio_length($file) {
	global $cfg;
	$stream = shell_exec( $cfg['restreamer']['info'] . " -f " . escapeshellarg($file) );
	preg_match("/Duration.+:.(\d+)\n/", $stream, $stream_grep);
	return (int) $stream_grep[1];
}

function get_audio_bitrate($file) {
	global $cfg;
	$stream = shell_exec( $cfg['restreamer']['info'] . " -f " . escapeshellarg($file) );
	preg_match("/Overall\sbit\srate.+:.(\d+)\n/", $stream, $stream_grep);
	return (int) $stream_grep[1];
}

/* some king of my black magic */
function do_this( $cmd, $elapsed ) {

	global $cfg, $throttle, $inbitrate, $row, $uid, $link;
	
	$buffer = 4096;
	$pre_seconds = 5;
	$pre_load  = ($inbitrate * $pre_seconds) * 125;
	$pipe_size = $inbitrate * 125 * 2;
	$delay_sec = 1 / ($pipe_size / $buffer) * 1000000;
	
	$proc = popen($cmd, "r");
	$actual_size = 0;
  
	if(!$proc) return 1;
	$dt = time();
	
	$time_start = microtime(true);	// begin counting timer
	
	while( $data = fread($proc, $buffer) ) {
		$actual_size += strlen($data);

		if( ($elapsed != -1) && ($actual_size > $elapsed) )
			$data = substr($data, 0, 4096 - ($actual_size - $elapsed));
			
		echo $data;
		
		if( ($elapsed != -1) && ($actual_size > $pre_load) && ($throttle == 1) ) usleep($delay_sec);
		
		flush();
		
		set_time_limit(30);
		
		if( connection_status() != 0 ) {
			pclose( $proc );
			die();
		}
	}

	pclose( $proc );
	
	if($elapsed == -1) return true;
	
	$padding = $elapsed - $actual_size;

	if($padding > 0) {
		$sub_padding = $padding % 4096;
		echo str_repeat("\x00", $sub_padding);
		$pads = (int)($padding / 4096);
		for($n=1;$n<=$pads;$n++)
			echo str_repeat("\x00", 4096);
	}

	return true;
  
}

?>