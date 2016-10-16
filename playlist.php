<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/layout.inc.php';
require 'core/general.inc.php';

if(ae_detect_ie()) {
	header('HTTP/1.1 403 Forbidden');
	exit; 
}

if($action = $_GET['action']) {
	switch($action) {
		case 'add' : {
			$index = (int)($_GET['i']);
			$plist = (int)($_GET['pl']);
			if(mysql_result(mysql_query("select `uid` from `playlist_titles` where `id` = '$plist'"),0,0) == $uid['uid']) {
				mysql_query("insert into `playlist_items` values(NULL, '$plist', '$index')");
				print "OK";
			} else {
				print "Deny";
			}
			break;
		}
		case 'new' : {
			$index = (int)($_GET['i']);
			$plname = mysql_real_escape_string($_GET['plname']);
			mysql_query("insert into `playlist_titles` values(NULL, '${uid['uid']}', '$plname')");
			$lastid = mysql_insert_id();
			mysql_query("insert into `playlist_items` values(NULL, '$lastid', '$index')");
			print "OK";
			break;
		}
		case 'del' : {
			$index = (int)($_GET['item']);
			$plist = (int)($_GET['pl']);
			if(mysql_result(mysql_query("select `uid` from `playlist_titles` where `id` = '$plist'"),0,0) == $uid['uid']) {
				mysql_query("delete from `playlist_items` where `plist` = '$plist' and `id` = '$index')");
				print "OK";
			} else {
				print "Deny";
			}
			break;
		}
	}
} else {

	$pl = (int)($_GET['pl']);
	$plname = mysql_result(mysql_query("select `plname` from `playlist_titles` where `id` = '$pl'"), 0, 0);

	active_log("ACT_VIEW", $pl);

	$sql = "SELECT a.* FROM `search_files` a, `playlist_items` b WHERE a.`index` = b.`fileid` and b.`plist` = '$pl'";

	$result = mysql_query($sql);

	page_header("Filelist '" . $plname . "'");
	page_banner('');
	show_table_head();
	show_playlist_title($pl);
	while ($row = mysql_fetch_assoc($result)) {
		if(is_allowed($row['filepath'], $uid['uid'])) {
			$files ++;
			show_directory_item_file($row);
		}
	}

	//show_directory_summary($files, $sum_size, $dirid);

	page_footer();

}

dbClose();

?>