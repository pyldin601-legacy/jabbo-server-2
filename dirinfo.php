<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/common.php';

ob_start("ob_gzhandler");

header("Content-Type: text/html; charset=utf-8");

if(empty($_GET['id'])) { die("Directory ID not set"); }

$id = (int)$_GET['id'];
$result = mysql_query("SELECT * FROM `search_folders` WHERE `id` = '$id' LIMIT 1");

if(mysql_num_rows($result) == 1) {
	$row = mysql_fetch_assoc($result);
	
	echo '<div><table class="tbl-fileinfo">';
	echo '<tr><th class="hd" colspan="2">directory info</th></tr>';
	echo '<tr><th>Directory name</th><td>' . htmlspecialchars($row['child'], ENT_QUOTES) . '</td></tr>';
	if($row['parent'] != '')
		echo '<tr><th>Location</th><td>' . htmlspecialchars($row['parent'], ENT_QUOTES) . '</td></tr>';
	echo '<tr><th>Modified</th><td>' . htmlspecialchars(my_human_date($row['mtime']), ENT_QUOTES) . '</td></tr>';

	$result = mysql_query("SELECT * FROM `jfs_dir_stats` WHERE `id` = '$id' LIMIT 1");
	if(mysql_num_rows($result) == 1) {
		$sub = mysql_fetch_assoc($result);
		echo '<tr><th>Opened times</th><td>' . htmlspecialchars($sub['opened'], ENT_QUOTES) . '</td></tr>';
		echo '<tr><th>Zipped times</th><td>' . htmlspecialchars($sub['zipped'], ENT_QUOTES) . '</td></tr>';
	}

	$path = path_id_to_fullpath($id);
	$size = mysql_result(mysql_query("SELECT IFNULL(SUM(`filesize`), 0) FROM `search_files` WHERE `filepath` = '$path' OR `filepath` LIKE '$path/%'"), 0, 0);
	echo '<tr><th>Folder size</th><td>' . my_bytes_to_human($size) . '</td></tr>';

	echo '</table></div>';
} else {
	echo "File not found in database!";
}

dbClose();

?>