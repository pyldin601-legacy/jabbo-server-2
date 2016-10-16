<?php

global $cfg;
global $link;
global $uid, $ufm;

$time = time();
$thisip = $_SERVER['HTTP_X_REAL_IP'];

session_start();

$link = mysql_connect( $cfg['db_host'], $cfg['db_user'], $cfg['db_pass'] );

if( ! $link ) die("<html><h1>Can't establish connection to database!</h1></html>");

mysql_select_db($cfg['db_base'], $link);
mysql_query("set names 'utf8'");

if(isset($_GET['session']))
	$sess = mysql_real_escape_string($_GET['session']);

if(isset($_GET['ss']))
	$tsess = mysql_real_escape_string($_GET['ss']);

// auth by cookie
if(isset($_SESSION['jfs_login'], $_SESSION['jfs_password'])) {
	$login = $_SESSION['jfs_login'];
	$passw = $_SESSION['jfs_password'];
	$res = mysql_query("select * from `search_users` where `login` = '$login' and `password` = '$passw' limit 1");
	if(mysql_num_rows($res) == 1)
		$uid = mysql_fetch_assoc($res);
}

// auth by ip
if(empty($uid)) {
	$res = mysql_query("select * from `search_users` where `locked_ip` = '$thisip' limit 1");
	if(mysql_num_rows($res) == 1)
		$uid = mysql_fetch_assoc($res);
}

// auth by session
mysql_query("delete from `sessions` where `session_expire` <= '$time'");

if(empty($uid) && isset($sess)) {
    $sw = $cfg['lock_session_ip'] ? "and `session_ip` = '$thisip'" : "";
	$res = mysql_query("select `session_user` from `sessions` where `session_id` = '$sess' $sw limit 1");
	if(mysql_num_rows($res) == 1) {
		$tmpu = mysql_result($res, 0, 0);
		$res = mysql_query("select * from `search_users` where `uid` = '$tmpu' limit 1");
		if(mysql_num_rows($res) == 1) {
			$uid = mysql_fetch_assoc($res);
		}
    }
}

if(empty($uid)) {
	$uid['uid'] = 0;
	$uid['access_level'] = 0;
} else {
	$fm = mysql_query("SELECT * FROM `jfs_scrobbler_keys` WHERE `uid` = '${uid['uid']}' LIMIT 1");
	if(mysql_num_rows($fm) == 1)
		$ufm = mysql_fetch_assoc($fm);

	$st = mysql_query("SELECT * FROM `jfs_users_stats` WHERE `uid` = '${uid['uid']}' LIMIT 1");
	if(mysql_num_rows($st) == 1) {
		$tmp = mysql_fetch_assoc($st);
		unset($tmp['uid']);
		$uid = array_merge($uid, $tmp);
	}
}

if( isset($sess) ) {
	$uid['session'] = $sess;
} else {
	$uid['session'] = get_active_session();
}

$result = mysql_query("select * from rights where 1");
while($row = mysql_fetch_assoc($result)) {
	$rights[$row['path']] = $row['users'];
}

// UPDATE USER ACTIVITY
update_activity();


function update_scrobbler_profile($ufm) {

    global $link;
    
	$query = "REPLACE INTO `jfs_scrobbler_keys` (%s) VALUES (%s)";
	$params = "";
	$values = "";
	
	foreach($ufm as $key=>$val) {
		$params .= "`" . mysql_real_escape_string($key) . "`,";
		$values .= "'" . mysql_real_escape_string($val) . "',";
	}
	$params = rtrim($params, ",");
	$values = rtrim($values, ",");
	
	mysql_query(sprintf($query, $params, $values));
	
	return $ufm;
	
}

function get_active_session() {
	global $uid, $cfg;
	$thisip = $_SERVER['HTTP_X_REAL_IP'];
	$thisid = $uid['uid'];
	$exp = time() + $cfg['session_expire'];
	$res = mysql_query("select `row_id`, `session_id` from `sessions` where `session_user` = '$thisid' and `session_ip` = '$thisip' and `session_expire` > '" . time() . "' limit 1");
	if(mysql_num_rows($res) == 1) {
		list($rowid, $sid) = mysql_fetch_array($res);
		$res = mysql_query("update `sessions` set `session_expire` = '$exp' where `row_id` = '$rowid' limit 1");
	} else {
		$sid = sess_challenge(md5($thisip . time() . rand(0, 1000000000000), true));
		$agent = mysql_real_escape_string($_SERVER['HTTP_USER_AGENT']);
		mysql_query("insert into `sessions` (`session_id`, `session_user`, `session_ip`, `session_expire`, `user_agent`) values ('$sid', '$thisid', '$thisip', '$exp', '$agent')");
	}
	return $sid;
}

function active_log($action, $where = '') {
	global $uid;
	$ip = $_SERVER['HTTP_X_REAL_IP'];
	$id = $uid['uid'];
	mysql_query("insert into `search_active_log` (`uid`, `ip`, `action`, `where`) values ('$id', '$ip', '$action', '$where')");
}

function is_dir_allowed($item, $uid) {
	global $rights;
	$flag = false;
	$path .= $item['child'] . '/';

	if(isset($_GET['secret']))
		if($_GET['secret'] == sess_challenge($item['id']))
			return true;

	
	foreach($rights as $key=>$val) {
		foreach(explode(',', $val) as $aid) {
			if(strlen($path) <= strlen($key)) {
				$sub = substr($key, 0, strlen($path));
				if($sub == $path) {
					if($aid == strval($uid)) {
						$flag = true;
					} elseif(($aid == '+') and ($uid > 0)) {
						$flag = true;
					} elseif($aid == '*') { 
						$flag = true;
					}
				}
			}
		}
	}
	return $flag;
}

// File allowed by challenge
function is_allowed_ch($id) {
	return (isset($_GET['t']) && (get_challenge($id) == $_GET['t']));
}

// File allowed by directory rights
function is_allowed($path, $id) {
	global $rights, $uid;
	
	// Always NO
	if( isset($uid['access_level']) && ($uid['access_level'] == -1) ) return false;
	// Always YES
	if( isset($uid['access_level']) && ($uid['access_level'] == 10) ) return true;
	
	$lpath = ''; 
	$flag = false;
	foreach(explode('/', $path) as $level) {
		$lpath .= $level . '/';
		if(isset($rights{$lpath})) {
			$flag = false;
			$allow = $rights{$lpath};
			foreach(explode(',', $allow) as $aid) {
				if($aid == strval($id)) {
					$flag = true;
				} elseif(($aid == '+') and ($id > 0)) {
					$flag = true;
				} elseif( preg_match("/^(\d+)\+$/", $aid, $matches) ) {
					if((int)$matches[1] <= (int)$uid['access_level'])
						$flag = true;
				} elseif($aid == '*') { 
					$flag = true;
				}
			}
		}
	}
	return $flag;
}

function is_allowed_dir($path, $uid) {
	global $rights;
	$flag = false;
	foreach($rights as $key => $val) {
		if(str_begin_compare($key, $path)) {
			//$flag = false;
			foreach(explode(',', $val) as $aid) {
				if($aid == strval($uid)) {
					$flag = true;
				} elseif(($aid == '+') and ($uid > 0)) {
					$flag = true;
				} elseif($aid == '*') { 
					$flag = true;
				}
			}
		}
	}

	return $flag;
}

function str_begin_compare($str1, $str2) {
    log_function_usage(__FUNCTION__);
	$str1_len = strlen($str1);
	$str2_len = strlen($str2);
	if($str1_len > $str2_len) {
		if(substr($str1, $str2_len) == $str2) return true;
	} else if($str2_len > $str1_len) {
		if(substr($str2, $str1_len) == $str1) return true;
	} else {
		if($str2 == $str1) return true;
	}
	return false;
}

function show_auth() {
	global $uid;
	return '';
}

function show_lastfm_frame() {

global $ufm;

$data = "";

if(isset($ufm))

$data .= <<<FM
<a target="_blank" href="http://last.fm/user/${ufm['fmuser']}" tooltip="Open <b>${ufm['fmuser']}</b>'s Last.fm profile">
<b>${ufm['fmuser']}</b> </a><span tooltip="Stop scrobbling" class="like-a" onclick="scrobbler_off()">[x]</span>
FM;

else

$data .= <<<FM
<span class="like-a" tooltip="Enable Last.fm scrobbling in <b>Jabbo Audio Player</b>" onclick="return scrobbler_on()">
Connect to Last.fm</span>
FM;

return $data;

}

function fill_topbar() {
	global $uid,$ismobile;
	
	$data = "";
	$data .= '<img class="icon-lastfm" src="/images/icons/lastfm_off.png" tooltip="Last.fm Scrobbler"> <span class="tb-lastfm">' . show_lastfm_frame() . '</span> ';

	if(!$ismobile) {
		$data .= '<img src="/images/topbar/music.png" tooltip="Audios listened"> <span class="tb-plays">' . $uid['play_count'] . '</span> ';
		$data .= '<img src="/images/topbar/downloads.png" tooltip="Files downloaded"> <span class="tb-down">' . $uid['down_count'] . '</span> ';
		$data .= '<img src="/images/topbar/video.png" tooltip="Videos watched"> <span class="tb-watch">' . $uid['watch_count'] . '</span> ';
	}
	return $data;
}

function show_auth_new() {

	global $uid;

	$data = "";
	
	//$data .= show_lastfm_frame();
	
	if ($uid['uid'] > 0) {
		$data .= '<span class="topbar-style">';
		$data .= fill_topbar();
		$data .= '</span> ';
		$data .= "Hello, <b>${uid['login']}</b>&nbsp&nbsp<input type=\"button\" onclick=\"return logout()\" value=\"Logout\">";
	} else {
		$data .= 
<<<LOGIN
		<form action="/login.php" method="post" onsubmit="return login2()">
			<!--<input type="button" onclick="signin()" value="Sign up">-->
			<input class="send" name="login" maxlength="30" type="text" placeholder="username" required>
			<input class="send" name="passw" maxlength="30" type="password" placeholder="password" required>
			<input type="submit" onsubmit="return login2()" value="Login">
		</form>
LOGIN;
	}
	
	return $data;

}

function dbClose() {
    global $link;
    mysql_close($link);
}

function update_activity() {

	global $link, $uid;
	mysql_query("update `jfs_users_stats` set `login_last` = NOW(), `ip_last` = '${_SERVER['HTTP_X_REAL_IP']}' where `uid` = '${uid['uid']}'");

}

function sess_challenge($key) {

	$code = 0;
	$challenge = "";

	$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

	$raw = md5(md5($key . "-my-static-xor-string-" . date("d-m-Y", time())), true);
	
	foreach(unpack('V*', $raw) as $cint)
		$code += $cint;

	while($code > strlen($characters)) {
		$symbol = $code % strlen($characters);
		$code = $code / strlen($characters);
		$challenge .= substr($characters, $symbol, 1);
	}

	return $challenge;

}

function shutdown() {
/*
    if(count(array_keys($_SESSION)) == 0) {
		session_destroy();
		tlog(count(array_keys($_SESSION)));
    }
*/
}

register_shutdown_function('shutdown');

?>