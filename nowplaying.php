<?php

session_set_cookie_params(time() + 2592000);
session_start();

if(empty($_POST['action'])) die();

if($_POST['action'] == 'save') {
	if(empty($_POST['data'])) die();
	$_SESSION['playlist'] = $_POST['data'];
} else if($_POST['action'] == 'load') {
	if(isset($_SESSION['playlist']))
		echo $_SESSION['playlist'];
}
	
?>