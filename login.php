<?php

// check parameters for registration
if(isset($_REQUEST['login']) && isset($_REQUEST['passw'])) {

	require 'config.php';
	require 'core/common.php';
	require 'core/auth.inc.php';

    $login = mysql_real_escape_string($_REQUEST['login']);
    $passw = md5($login.$_REQUEST['passw']);
    $passwx = mysql_real_escape_string($_REQUEST['passw']);

    my_users_unique("login");

	// test user login existence
	$result = mysql_query("select * from `search_users` where `login` = '$login' and `password` = '$passw'");
	if(! mysql_num_rows($result)) { 
		echo 'Incorrect login or password!'; 
		mysql_query("INSERT INTO `jfs_login_fail` VALUES (NULL, '${_SERVER['REMOTE_ADDR']}', '${login}', '${passwx}', NULL)");
		die();
	} else {
		$row = mysql_fetch_assoc($result);
		$_SESSION['jfs_login'] = $row['login'];
		$_SESSION['jfs_password'] = $row['password'];
		echo 'OK';
		tlog("User logged in");
	}
}

if($_SERVER['REQUEST_METHOD'] == 'GET') {
	header("HTTP/1.1 301 Moved Permanently");
	if(isset($_GET['go']))
		header("Location: /go/" . (int) $_GET['go']);
	else if(isset($_GET['q']))
		header("Location: /search.php?q=" . urlencode($_GET['q']));
	else
		header("Location: /");
}

?>