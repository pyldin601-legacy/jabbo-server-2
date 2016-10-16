<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/layout.inc.php';
require 'core/general.inc.php';

if(ae_detect_ie()) {
	header('HTTP/1.1 403 Forbidden');
	exit; 
}

if($mode = $_GET['m']) {
	switch($mode) {
		case 'top_played' : {
			page_header("Top played per all time");
			$sql = "SELECT a.* FROM `search_files` a, `search_player` b WHERE a.`md5` = b.`file` and b.`uid` = '${uid['uid']}' order by b.`played` desc limit 200";
			break;
		}
		case 'last_played' : {
			page_header("Last played per all time");
			$sql = "SELECT a.* FROM `search_files` a, `search_player` b WHERE a.`md5` = b.`file` and b.`uid` = '${uid['uid']}' order by b.`played_last` desc limit 200";
			break;
		}
		default : { exit; }
	}
} else {
	exit;
}

$result = mysql_query($sql);
page_banner('');
show_table_head();
while ($row = mysql_fetch_assoc($result)) {
	if(is_allowed($row['filepath'], $uid['uid'])) {
		$files ++;
		show_directory_item_file($row);
	}
}

page_footer();


dbClose();

?>