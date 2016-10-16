<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/layout.inc.php';
require 'core/general.inc.php';

if(ae_detect_ie()) {
	header('HTTP/1.1 403 Forbidden');
	exit; 
}


$files = 0;
$sum_size = 0;
$expand = false;
$loc = '';

if(isset($_GET['x']))
	if($_GET['x'] == '1')
		$expand = true;

$get_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if($get_id == 0) {
	$current_path = "[root]";
} else {
	$result = mysql_query(sprintf("select * from `search_folders` where `id` = '%d' limit 1", $get_id));
	if(mysql_num_rows($result) == 1) {
		$row = mysql_fetch_assoc($result);
		$spath = $row['child'];
		$parent_path = $row['parent'];
		$current_path = substr($row['child'], 1 + strrpos($row['child'], '/'));
		$parent_path_id = pathNameToId($parent_path);
	} else {
		$current_path = '[Location not found!]';
	}
}

page_header($current_path);
page_banner('');
show_table_head();


if($get_id > 0) {
	/* display directory contents */
	show_directory_head($spath);
	if(isset($parent_path_id)) show_directory_return($parent_path_id);
	$dirs = mysql_query("select * from `search_folders` where `parent` = '$spath' order by `child`");
	while ($row = mysql_fetch_assoc($dirs)) {
		if(is_allowed($spath, $uid['uid'])) {
			show_directory_item_dir($row); $files++;
		}
	}
	if(is_allowed($spath, $uid['uid'])) {
		$result = mysql_query("SELECT * FROM `search_files` WHERE (`filepath` LIKE '" . mysql_real_escape_string($spath) . "') ORDER BY `filepath`, `filetype`, `filename` limit 1000");
		while ($row = mysql_fetch_assoc($result)) {
			$files++;
			show_directory_item_file($row);
		}
	} else {
		show_info('You are not allowed to access this directory!!!');
	}
} else {
	show_directory_root();
	$dirs = mysql_query("select `root` from `search_folders` group by `root`");
	while ($row = mysql_fetch_assoc($dirs)) {
		$res = mysql_query(sprintf("SELECT * FROM `search_folders` WHERE `child` = '%s' LIMIT 1", mysql_real_escape_string($row['root'])));
		if(mysql_num_rows($res) == 1) {
			$incept = mysql_fetch_assoc($res);
			show_directory_item_dir($incept);
			$files++;
		}
	}
}
show_directory_summary($files, $sum_size, $get_id);
page_footer();
dbClose();

?>


