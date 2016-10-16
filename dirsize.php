<?php
require 'config.php';
require 'core/auth.inc.php';
require 'core/common.php';

ob_start("ob_gzhandler");

header("Content-Type: text/html; charset=utf-8");

if(isset($_GET['id'])) 
{
	$id = (int)$_GET['id'];
	$result = mysql_query("SELECT * FROM `search_folders` WHERE `id` = $id");
	if($row = mysql_fetch_assoc($result))
	{
		
		if($row['parent'] == '')
			$path = $row['root'];
		else
			$path = $row['parent'] . '/' . $row['child'];
		
		$path = mysql_real_escape_string($path);
		
		$size = mysql_result(mysql_query("SELECT IFNULL(SUM(`filesize`), 0) FROM `search_files` WHERE `filepath` = '$path' OR `filepath` LIKE '$path/%'"), 0, 0);
		
		echo my_bytes_to_human($size);
		
	}
}

dbClose();

?>