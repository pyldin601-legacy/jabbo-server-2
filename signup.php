<?php

include 'config.php';

$allow_new_users = false;

$link = mysql_connect($cfg['db_host'], $cfg['db_user'], $cfg['db_pass']);
if( ! $link ) die("<html><h1>Can't establish connection to database!</h1></html>");

mysql_select_db($cfg['db_base'], $link);
mysql_query("set names 'utf8'");

if($allow_new_users == false)
	$error_message = 'Registration disabled by administrator';

if($_SERVER['REQUEST_METHOD'] == 'POST' && $allow_new_users == true) {
	$form_login = isset($_POST['login']) ? htmlspecialchars($_POST['login'], ENT_NOQUOTES) : "";
	$form_ip = isset($_POST['ip']) ? htmlspecialchars($_POST['ip'], ENT_NOQUOTES) : "";
	if(isset($_POST['login'], $_POST['password'])) {
		// check login collision
		$sub_login = mysql_real_escape_string($_POST['login']);
		$sub_passw = mysql_real_escape_string($_POST['password']);
		$sub_ip = isset($_POST['ip']) ? mysql_real_escape_string($_POST['ip']) : "";
		$coll = mysql_result(mysql_query("SELECT COUNT(*) FROM `search_users` WHERE `login` = '$sub_login'"), 0, 0);
		if($coll == 0) {
			// register user
			if(mysql_query("INSERT INTO `search_users` (`login`, `password`, `access_level`, `locked_ip`) VALUES ('$sub_login', MD5(CONCAT('$sub_login','$sub_passw')), 0, '$sub_ip')") ) {
				mysql_query("INSERT INTO `jfs_users_stats` (`uid`) VALUES ('" . mysql_insert_id($link) . "')");
				session_start();
				$_SESSION['jfs_login'] = $sub_login;
				$_SESSION['jfs_password'] = md5($sub_login . $sub_passw);
				header("Location: " . $cfg['link']['home']);
			} else {
				$error_message = "Some problems with database.<BR>Can't sign up... :(";
			}
		} else {
			$error_message = 'This login is used by another user';
		}
	}
}

mysql_close();

?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=yes" />
	<meta name="MobileOptimized" content="176" />
	<meta name="description" content="Jabbo File Server">
	<title>Sign up @ Jabbo File Server</title>
	<link href="/css/fonts.css" rel="stylesheet" type="text/css">
	<link href="/jabbo.css" rel="stylesheet" type="text/css">
	<link href="/css/common.css" rel="stylesheet" type="text/css">
	<link href="/css/tooltip.css" rel="stylesheet" type="text/css">
	<link href="/css/search.css" rel="stylesheet" type="text/css">
	<link href="/css/aplayer.css" rel="stylesheet" type="text/css">
	<link href="/css/playlist.css" rel="stylesheet" type="text/css">
	<link href="/css/login.css" rel="stylesheet" type="text/css">
	<link href="/css/signup.css" rel="stylesheet" type="text/css">
	<link rel="icon" type="image/png" href="/images/search.png">
	<link id="favicon" rel="icon" type="image/png" href="/images/favicon/jabbo-icon.png">
</head>
<body>
<br>
<div class="fx-round fx-blur su-body">
	<h1>Sign up</h1>
<?php 
	if(isset($error_message))
		echo '<div class="err fx-round red-error">' . htmlspecialchars($error_message, ENT_NOQUOTES) . '</div>';
?>
	<form method="post">
	<table class="su-table">
		<tr><td>Login</td><td><input class="su-input" name="login" maxlength="30" type="text" value="<?php echo $form_login ?>" required></td></tr>
		<tr><td>Password</td><td><input class="su-input" name="password" maxlength="30" type="password" required></td></tr>
		<tr><td>Locked IP<br>(optional)</td><td><input class="su-input" name="ip" maxlength="30" type="text"></td></tr>
		<tr><td colspan="2" style="text-align:right"><input type="submit" value="Continue"></td></tr>
	</table>
	</form>
</div>
</body>
</html>