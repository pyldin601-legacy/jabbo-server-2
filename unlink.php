<?php

require 'config.php';
require 'core/auth.inc.php';
//require 'core/general.inc.php';

if($uid['uid'] != '1') { print "<h1>This isn't for you!</h1>"; exit; }

if(isset($_GET['f'])) {
	$i = (int)$_GET['f'];
	$res = mysql_query("select * from `search_files` where `index` = '$i' limit 1");
	if(mysql_num_rows($res) == 1) {
		$row = mysql_fetch_assoc($res);
		$res = unlink($row['filepath'] . '/' . $row['filename']);
		if($res) {
			mysql_query("delete from `search_files` where `index` = '$i' limit 1");
			print "delete:$i:ok";
		} else {
			print "delete:$i:deny";
		}
	} else {
		print "delete:$i:absent";
	}
}

dbClose();

?>