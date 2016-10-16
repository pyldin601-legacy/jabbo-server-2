<?php

$sessions = '/tmp/jabbo_sessions/';

$handle = opendir($sessions);
while($file = readdir($handle)) {
	$dt = file_get_contents($sessions . $file);
	$_SESSION = array();
	session_decode($dt);
	print_r($_SESSION);
}

closedir($handle);

?>