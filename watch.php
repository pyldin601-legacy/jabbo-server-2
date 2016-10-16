<?php

require 'config.php';
require 'core/common.php';
require 'core/auth.inc.php';
require 'core/layout.inc.php';
require 'core/general.inc.php';
require 'core/media.inc.php';
session_write_close();

$next_index = 0;

if(isset($_GET['i'], $_GET['t'])) {

	$index = (int)$_GET['i'];
	$test = $_GET['t'];
	
	$result = mysql_query("SELECT * FROM `search_files` WHERE `index` = '${index}' LIMIT 1");

	if(mysql_num_rows($result) == 1) {

		$row = mysql_fetch_assoc($result);
		
		if(get_challenge($index) != $test) {
			header("HTTP/1.1 403 Forbidden");
			die('Access denied!');
		}
		
		if($row['filetype'] == 'flv') 
			$flv_real_path = $row['filepath'] . '/' . $row['filename'];
		else
			$flv_real_path = flv_path($row['md5']);
		
		$fsize = filesize($flv_real_path);
		$mtime = filemtime($flv_real_path);

		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mtime) {
			header('HTTP/1.1 304 Not Modified');
			die();
		}

		if(isset($_GET['start']))
				$start = (int) $_GET['start'];
		else
				$start = 0;
		
		$mediainfo = mediafile_info($flv_real_path);
		$bitrate = (int) $mediainfo['General']['Overall bit rate'][0];
		$flowspeed = ($bitrate / 8) * 1.5;
		$delay = 1000000 / ($flowspeed / 4096);
		
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mtime) . " GMT");
		header('Cache-Control: max-age=0');
		header('Content-Type: video/x-flv');
		header('Content-Disposition: filename="' . $row['md5'] . '.flv"');
		$header = "FLV" . pack('C', 1) . pack('C', 5) . pack('N', 9) . pack('N', 9);

		$fh = fopen($flv_real_path, "r");
		
		if( ! $fh ) die("Can't open flv stream!");

		if($start > 0) {
			header("Content-Length: " . (strlen($header) + $fsize - $start));
			echo $header;
			fseek($fh, $start);
		} else {
			header('Content-length: ' . $fsize);
		}
		
		$pre = 0;
		while(!feof($fh)) {
			$pre += 4096;
			set_time_limit(30);
			$data = fread($fh, 4096);
			echo $data;
			if($pre > $fsize * 0.10) usleep($delay);
			flush();
		}
		
		tlog("watch: filename=\"${row['filepath']}/${row['filename']}\"");
		
		mysql_query("update `search_users` set `watch_count` = `watch_count` + 1 where `uid` = '${uid['uid']}'");
		mysql_query("INSERT INTO `jfs_file_stats` VALUES(${row['index']}, ${uid['uid']}, 0, 0, 1)
					 ON DUPLICATE KEY
					 UPDATE `watchcount` = `watchcount` + 1");
	} else {
		header("HTTP/1.1 404 Not found");
		die("File not found!");
	}
} else if(isset($_GET['v'], $_GET['t'])) {

	$index = (int) $_GET['v'];
	$test = $_GET['t'];

	$userid = $uid['uid'];
	
	$next_id = 0;
	$next_crc = 0;

	$result = mysql_query("SELECT `pos` FROM `videoposition` WHERE `uid` = '${userid}' AND `index` = '${index}' LIMIT 1");

	if(mysql_num_rows($result) == 1)
		$startfrom = (int) mysql_result($result, 0, 0);
	else
		$startfrom = 0;

	$result = mysql_query("SELECT * FROM `search_files` WHERE `index` = '${index}' LIMIT 1");

	if(mysql_num_rows($result) == 1) {
		$row = mysql_fetch_assoc($result);
		$flv_real_path = $row['filepath'] . '/' . $row['filename'];
		$fsize = filesize($row['filepath'] . '/' . $row['filename']);
		
		// get next file id
		$next_query = mysql_query(sprintf("SELECT `index` FROM `search_files` WHERE `filepath` = '%s' ORDER BY `filename`", mysql_real_escape_string($row['filepath'])));
		$rs = mysql_num_rows($next_query);
		if($rs > 0) {
			$cur = 0;
			while($rw = mysql_fetch_assoc($next_query)) {
				$cur ++;
				if($rw['index'] == $index && $cur < $rs) {
					$rw = mysql_fetch_assoc($next_query);
					$next_id = $rw['index'];
					$next_crc = get_challenge($rw['index']);
					break;
				}
			}
		}
		
		$dim = explode('x', $row['video_dimension']);
		foreach($dim as &$size) $size = (int) $size;
		$aspect = $dim[1] / $dim[0];

	}	

	echo <<<JS
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
	<HTML>
	<HEAD>
	<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=UTF-8">
	<TITLE>${row['filename']}</TITLE>
	<META NAME="AUTHOR" CONTENT="Roman Gemini">
	<META NAME="CREATED" CONTENT="20110526;10190900">
	<META http-equiv="content-type" CONTENT="text/html; charset=UTF-8">
	<link href="/jabbo.css" rel="stylesheet" type="text/css">
	<link href="/player.css" rel="stylesheet" type="text/css">
	<script src="/js/jquery-1.7.1.min.js"></script>
	<script src="/js/jquery-ui-1.8.17.custom.min.js.gz"></script>
	<script type="text/javascript" src="/player/jwplayer.js"></script>
	<script type="text/javascript" src="/player/vplayer.js"></script>
	<script type="text/javascript">
		var uid = ${uid['uid']};
		var startPosition = ${startfrom};
		var fileIndex = ${row['index']};
		var nextIndex = ${next_id};
		var nextCRC = '${next_crc}';
	</script>
	</HEAD>
	<BODY>
	<div class="player"><div id="mediaspace"></div></div>
	</BODY>
	</HTML>

	<script type='text/javascript'>
	jwplayer('mediaspace').setup({
		'flashplayer': '/player/player.swf',
		'file': '/stream/${index}.flv?t=${test}',
		'provider':'http',
		'autostart': 'true',
		'width': '100%',
		'height': '100%',
		'stretching': 'uniform',
		'skin': 'player/skin/glow2.zip'
	});
	jwplayer('mediaspace').onReady(		function () { jwplayer('mediaspace').seek(startPosition); });
	jwplayer('mediaspace').onSeek(		function () { putPos(); });
	jwplayer('mediaspace').onComplete(  function () { if(nextIndex > 0) goNext(nextIndex, nextCRC); });
	</script>
JS;

}

dbClose();

?>