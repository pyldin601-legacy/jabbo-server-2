<?php

require 'config.php';
require 'core/auth.inc.php';

if(isset($_POST['i'], $_POST['p'])) {

	$index = (int)$_POST['i'];
	$pos = (int)$_POST['p'];
	$userid = $uid['uid'];

	$result = mysql_query("select * from `videoposition` where `uid` = '$userid' and `index` = '$index' limit 1");
	if(mysql_num_rows($result) == 1) {
		$opos = mysql_result($result, 0, 3);
		mysql_query("update `videoposition` set `pos` = '$pos' where `uid` = '$userid' and `index` = '$index' limit 1");
		print 'OK. UPDATE.';
	} else {
		mysql_query("insert into `videoposition` values(NULL, '$userid', '$index', '$pos')");
		print 'OK. ADD.';
	}
}

dbClose();

?>