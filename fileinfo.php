<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/common.php';

ob_start("ob_gzhandler");

header("Content-Type: text/html; charset=utf-8");

if(isset($_GET['id'])) {
	$id = (int)$_GET['id'];
	$result = mysql_query("SELECT * FROM `search_files` WHERE `index` = '$id' LIMIT 1");
	if(mysql_num_rows($result) == 1) {
		$row = mysql_fetch_assoc($result);
		
		if(is_allowed($row['filepath'], $uid['uid']) == false) {
			echo '<div>You do not have access to this file</div>';
			die();
		}
		
		if($row['filegroup'] == 'aa' || $row['filegroup'] == 'image') {
			echo '<center><img class="fi-preview fx-round-top" src="/audiocover.php?id=' . $row['index'] . '&s=380"></center>';
		}

		echo '<div><table class="tbl-fileinfo">';
		echo '<tr><th class="hd" colspan="2">common info</th></tr>';
		//echo '<tr><th></th><td></td></tr>';
		echo '<tr><th>File name</th><td>' . htmlspecialchars($row['filename'], ENT_QUOTES) . '</td></tr>';
		echo '<tr><th>File type</th><td>' . htmlspecialchars(strtoupper($row['filetype']), ENT_QUOTES) . '</td></tr>';
		echo '<tr><th>Modified</th><td>' . htmlspecialchars(my_human_date($row['filemtime']), ENT_QUOTES) . '</td></tr>';
		echo '<tr><th>Size</th><td>' . my_bytes_to_human($row['filesize']) . ' (' . my_format_size($row['filesize']) . ' bytes)' . '</td></tr>';
		echo '<tr><th>Location</th><td>' . htmlspecialchars($row['filepath'], ENT_QUOTES) . '</td></tr>';
		echo '<tr><th class="sep hd" colspan="2">jfs info</th></tr>';
		echo '<tr><th>ID</th><td>' . htmlspecialchars($row['index'], ENT_QUOTES) . '</td></tr>';
		echo '<tr><th>Indexed</th><td>' . htmlspecialchars(my_human_date($row['indexed']), ENT_QUOTES) . '</td></tr>';
		echo '<tr><th>Downloads</th><td>' . mysql_result(mysql_query("SELECT IFNULL(SUM(downloads), 0) FROM `jfs_file_stats` WHERE `id` = $id"), 0, 0) . '</td></tr>';

		if($row['filegroup'] == 'audio') {
			echo '<tr><th>Played</th><td>' . mysql_result(mysql_query("SELECT IFNULL(SUM(playcount), 0) FROM `jfs_file_stats` WHERE `id` = $id"), 0, 0) . ' times</td></tr>';
		}

		if($row['filegroup'] == 'audio') {
			echo '<tr><th class="sep hd" colspan="2">audio info</th></tr>';
			echo '<tr><th>Title</th><td>' . htmlspecialchars($row['audio_title'], ENT_QUOTES) . '</td></tr>';
			echo '<tr><th>Artist</th><td>' . htmlspecialchars($row['audio_artist'], ENT_QUOTES) . '</td></tr>';
			echo '<tr><th>Band</th><td>' . htmlspecialchars($row['audio_band'], ENT_QUOTES) . '</td></tr>';
			echo '<tr><th>Album</th><td>' . htmlspecialchars($row['audio_album'], ENT_QUOTES) . '</td></tr>';
			echo '<tr><th>Genre</th><td>' . htmlspecialchars($row['audio_genre'], ENT_QUOTES) . '</td></tr>';
			echo '<tr><th>Track number</th><td>' . htmlspecialchars($row['audio_tracknum'], ENT_QUOTES) . '</td></tr>';
			echo '<tr><th>Bitrate</th><td>' . (int)($row['avg_bitrate']/1000) . ' kbps</td></tr>';
			echo '<tr><th>Duration</th><td>' . my_sec_to_time($row['avg_duration']/1000) . '</td></tr>';
		} else if($row['filegroup'] == 'video') {
			echo '<tr><th class="sep hd" colspan="2">video info</th></tr>';
			echo '<tr><th>Bitrate</th><td>' . (int)($row['avg_bitrate']/1000) . ' kbps</td></tr>';
			echo '<tr><th>Resolution</th><td>' . htmlspecialchars($row['video_dimension'], ENT_QUOTES) . '</td></tr>';
		} else if($row['filegroup'] == 'image') {
			echo '<tr><th class="sep hd" colspan="2">picture info</th></tr>';
			echo '<tr><th>Resolution</th><td>' . htmlspecialchars($row['video_dimension'], ENT_QUOTES) . '</td></tr>';
		}
		echo '</table></div>';
	} else {
		echo "File not found in database!";
	}
} else {
	echo "Specify <b>id</b> parameter!";
}

dbClose();

?>