<?php

require 'config.php';
require 'core/auth.inc.php';

if(! isset($_POST['id'])) die();

$index = (int)$_POST['index'];
$result = mysql_query("select * from `search_files` where `index` = '${index}' limit 1");

if(!$result) exit;

if($uid) { 
	$uu = $uid['uid']; 
} else { 
	$uu = 0; 
}

if(mysql_num_rows($result) == 1) {
    $row = mysql_fetch_assoc($result, 0);
	mysql_query("update `search_users` set `play_count` = `play_count` + 1 where `uid` = '${uid['uid']}'");
	mysql_query(sprintf("insert into `audio_scrobbles` values (NULL, NULL, '%d', '%s', '%s')", $uu, mysql_real_escape_string($row['audio_artist']), mysql_real_escape_string($row['audio_title'])));
	print 'OK:' . $index;
}

dbClose();


?>
