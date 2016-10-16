<?php

if(isset($_POST['id'], $_POST['s']))
{

require 'config.php';
require 'core/auth.inc.php';
require 'core/general.inc.php';
require 'core/common.php';

$dirid  = (int) $_POST['id'];
$action = (int) $_POST['s'];
$userid = (int) $uid['uid'];

if($userid == 0) {
	echo 'NOAUTH';
} elseif($action == 1) {
	if(mysql_query("REPLACE INTO `jfs_folder_bookmark` VALUES ($userid, $dirid)")) 
		echo 'OK';
	else
		echo 'ERROR';
} elseif($action == 0) {
	if(mysql_query("DELETE FROM `jfs_folder_bookmark` WHERE `user_id` = $userid AND `folder_id` = $dirid"))
		echo 'OK';
	else
		echo 'ERROR';
}

dbClose();

} 
else 
{
die("<h1>Parameters required.</h1>");
}

?>