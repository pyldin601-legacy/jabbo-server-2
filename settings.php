<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/common.php';

if(empty($_GET['a'])) die("no action");

if($_GET['a'] == 'get') {
	$query = mysql_query("SELECT * FROM `jfs_users_settings` WHERE `uid` = ${uid['uid']}");
	if(mysql_num_rows($query) > 0) {
		$row = mysql_fetch_assoc($query);
		echo json_encode($row);
		die();
	}
}

if(($_GET['a'] == 'put') && isset($_GET['k']) && isset($_GET['v'])) {
	$key = mysql_real_escape_string($_GET['k']);
	$val = mysql_real_escape_string($_GET['v']);
	mysql_query("INSERT INTO `jfs_users_settings` (`uid`,`$key`) VALUES ('${uid['uid']}','$val') ON DUPLICATE KEY UPDATE `$key` = '$val'");
}

dbClose();

?>