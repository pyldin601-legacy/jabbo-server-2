<?php

require 'config.php';
require 'core/auth.inc.php';
//require 'core/general.inc.php';

if($uid['uid'] != '1') { print "<h1>This isn't for you!</h1>"; exit; }

if(isset($_GET['f'], $_GET['n'])) {
	$i = (int)$_GET['f'];
	$n = $_GET['n'];
	if(!preg_match('/\/+/', $n)) {
		$res = mysql_query("select * from `search_files` where `index` = '$i' limit 1");
		if(mysql_num_rows($res) == 1) {
			$row = mysql_fetch_assoc($res);
			$res = rename($row['filepath'] . '/' . $row['filename'], $row['filepath'] . '/' . $n);
			if($res) {
				$ext = substr($n, strrpos($n, ".") + 1);
				mysql_query(sprintf("update `search_files` set `filename` = '%s', `filetype` = '%s' where `index` = '$i' limit 1", mysql_real_escape_string($n), $ext));
				print "rename:$i:ok";
			} else {
				print "rename:$i:deny";
			}
		} else {
			print "rename:$i:absent";
		}
	} else {
			print "rename:$i:incorrect";
	}
}

dbClose();

?>