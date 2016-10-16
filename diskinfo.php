<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/common.php';

ob_start("ob_gzhandler");

$sum_total = 0;
$sum_free = 0;

header("Content-Type: text/html; charset=utf-8");

echo '<div><table class="tbl-fileinfo">';

$result = mysql_query("SELECT `root` FROM `search_folders` WHERE PARENT = '' ORDER BY `child`");
echo '<tr class="head"><th>Disk Label</th><th>Total Space</th><th>Free Space</th></tr>';

while($row = mysql_fetch_assoc($result))
{
	$r = explode('/', $row['root']);
	echo '<tr><th>' . end($r) . '</th><td>'.my_bytes_to_human(disk_total_space($row['root'])).'</td><td>'.my_bytes_to_human(disk_free_space($row['root'])).'</td></tr>';
	$sum_total += disk_total_space($row['root']);
	$sum_free += disk_free_space($row['root']);
}

echo '<tr><th>total</th><td>' . my_bytes_to_human($sum_total) . '</td><td>' . my_bytes_to_human($sum_free) . '</td></tr>';

echo '</table></div>';


dbClose();

?>