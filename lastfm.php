<?php

header("Access-Control-Allow-Origin: *");

require 'config.php';
require 'core/auth.inc.php';
require 'core/general.inc.php';
require 'core/common.php';



session_write_close();

if ((isset($_POST['submission']) || isset($_POST['nowplaying'])) && $uid['uid'] == 0) {
	echo 'OK (Ignored)';
	die();
}

if(isset($_POST['submission']))
	increase_play_count($uid['uid']);

if(isset($ufm))	$_TMPFM = $ufm;

/*
Coded by Isis © 2010, vpleer@gmail.com
*/
	 
define('API_KEY', 'f7a8f639e4747490849e3bc33475b118');
define('API_SECRET_KEY', '201a5cfc808c607724bf181474160773');
define('CLIENT_ID', 'tst');
define('CLIENT_VERSION', '1.0');
    
    function xml2arr($xml, $recursive = false)
    {
        if(!$recursive)    $array = simplexml_load_string($xml); else $array = $xml ;
 
        $newArray    = array() ;
        $array         = (array)$array ;
        foreach($array as $key =>$value)
        {
            $value    = (array)$value ;
            if(isset($value[0]))    $newArray[$key] = trim($value[0]); else $newArray[$key] = xml2arr($value,true);
        }
        return $newArray ;
    }
    
	function getTrackInfo($artist, $song) 
	{
	}
	
    function loginLastFM($url, $type, $post = null)
    {
        if($ch    = curl_init($url))
        {
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Jabbo Audio Player');
            $type = $type == 'get'    ?    curl_setopt($ch, CURLOPT_POST, 0)    :    curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_REFERER, 'http://www.lastfm.ru/api/');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $content    = curl_exec($ch);
            curl_close($ch);
            return $content;
        }
        else
        {
            return 'notconnect';
        }
    }
    
    //1st step. Get token from $_GET['token']
    function getKey($token, $API_KEY, $API_SECRET_KEY)
    {
        $api_sig    = md5('api_key'.$API_KEY.'methodauth.getSessiontoken'.$token.$API_SECRET_KEY);
        $get        = 'method=auth.getSession&api_key='.$API_KEY.'&token='.$token.'&api_sig='.$api_sig;    
        $return        = xml2arr(loginLastFM('http://ws.audioscrobbler.com/2.0/', 'get', $get));
        return $return;
    }
    
    //handShake. Рукопожатие. Вызывается каждый раз когда что-либо не сработало
    function handShake($user, $key, $time, $CLIENT_ID, $CLIENT_VERSION, $API_KEY, $API_SECRET_KEY)
    {
        $handtoken    = md5($API_SECRET_KEY.$time);    
        $handget    = 'hs=true&p=1.2.1&c='.$CLIENT_ID.'&v='.$CLIENT_VERSION.'&u='.$user.'&t='.$time.'&a='.$handtoken.'&api_key='.$API_KEY.'&sk='.$key;
        $handshake    = loginLastFM('http://post.audioscrobbler.com/', 'get', $handget);
        $handecho    = explode("\n", $handshake);
        return $handecho;
    }
    
    //Посылаем на last.fm все данные песни на момент начала проигрывания
    function nowPlaying($session, $artist, $song, $duration)
    {
        $playget    = 's='.$session.'&a='.$artist.'&t='.$song.'&b=&l='.$duration.'&n=&m=';
        $playnow    = loginLastFM('http://post.audioscrobbler.com:80/np_1.2', 'post', $playget);
        return $playnow;
    }

    
    //Посылаем на last.fm все данные песни на момент окончания проигрывания или спустя 50% проигрывания трека
    function submission($session, $artist, $song, $duration, $starttime)
    {
        $subget        = 's='.$session.'&a[0]='.$artist.'&t[0]='.$song.'&i[0]='.$starttime.'&o[0]=P&r[0]=&l[0]='.$duration.'&b[0]=&n[0]=&m[0]=';
        $submission    = loginLastFM('http://post2.audioscrobbler.com:80/protocol_1.2', 'post', $subget);
        return $submission;
    }

    function love($session, $artist, $song)
    {
        $subget        = 's='.$session.'&a[0]='.$artist.'&t[0]='.$song.'&o[0]=P&r[0]=&b[0]=&n[0]=&m[0]=';
        $submission    = loginLastFM('http://post2.audioscrobbler.com:80/protocol_1.2', 'post', $subget);
        return $submission;
    }
    
    function doShake($fmuser, $fmkey, $time, $CLIENT_ID, $CLIENT_VERSION, $API_KEY, $API_SECRET_KEY)
    {
		global $_TMPFM;
		
        $handshake    = handShake($fmuser, $fmkey, $time, $CLIENT_ID, $CLIENT_VERSION, $API_KEY, $API_SECRET_KEY);
        $handerror    = trim($handshake[0]);
        $session      = trim($handshake[1]);
        if($handerror == 'OK' && isset($session))
        {
            //setcookie('fmsess', $session, time() + 3600 * 24 * 730, '/', '.'.$_SERVER['HTTP_HOST']);
			$_TMPFM['fmsess'] = $session;
            return 'OK';
        }
        else
        {
            return "Error : $handerror\n";
        }
    }
    
	function stopAnything() 
	{
		global $uid;
		mysql_query("DELETE FROM `jfs_scrobbler_keys` WHERE `uid` = '${uid['uid']}'");
	}
	
	function addToQueueOnError($artist, $title, $duration, $starttime) {
		global $uid, $time;
		mysql_query(sprintf("INSERT INTO `jfs_scrobb_queue` VALUES (NULL, '%d', '%d', '%s', '%s', '%d')",
			$uid['uid'], 
			$time, 
			mysql_real_escape_string($title), 
			mysql_real_escape_string($artist), 
			$duration
		));
		$queued = mysql_result(mysql_query("SELECT COUNT(id) FROM `jfs_scrobb_queue` WHERE `uid` = '${uid['uid']}'"), 0, 0);
		echo "QUEUED : $queued\n";
	}

	function submitQueued() {
		global $uid, $time, $_TMPFM;
		$res = mysql_query("SELECT * FROM `jfs_scrobb_queue` WHERE `uid` = '${uid['uid']}' LIMIT 25");
		while($row = mysql_fetch_assoc($res)) {
			echo $submiss = submission($_TMPFM['fmsess'], $row['artist'], $row['title'], $row['duration'], $row['time']);
			if(!strstr($submiss, 'OK')) return false;
			mysql_query("DELETE FROM `jfs_scrobb_queue` WHERE `id` = '${row['id']}'");
		}
	}
	
    $time        = time();

	
	//Remove connection
	if(isset($_GET['disable']) && ($_GET['disable'] == '1')) {
		stopAnything();
		echo 'OK';
		die();
	}
	
    //Если к нам пришли первый раз с last.fm, то ставим куки с необходимыми данными
    if(isset($_GET['token']))
    {
        $return        = getKey($_GET['token'], API_KEY, API_SECRET_KEY);
        $error        = isset($return['error'])            ?    $return['error']            :    null;
        $key        = isset($return['session']['key'])    ?    $return['session']['key']    :    null;
        $user        = isset($return['session']['name'])    ?    $return['session']['name']    :    null;
        if(!isset($error) && isset($key) && isset($user))
        {
			$_TMPFM['uid'] = $uid['uid'];
			$_TMPFM['fmkey'] = $key;
			$_TMPFM['fmuser'] = $user;
			$_TMPFM['scrobb'] = 'on';
            $a    = doShake($user, $key, $time, CLIENT_ID, CLIENT_VERSION, API_KEY, API_SECRET_KEY);
            header('Location: /html/scrobb.html');
        }
        else
        {
            echo $error;
        }
    }
    
    //1й раз? Надо пожать ручку
    if(isset($_TMPFM['fmkey'], $_TMPFM['fmuser']) && (empty($_TMPFM['fmsess']) || ($_TMPFM['fmsess'] == "")) && (isset($_POST['nowplaying']) || isset($_POST['submission'])))
    {
        $a    = doShake($_TMPFM['fmuser'], $_TMPFM['fmkey'], $time, CLIENT_ID, CLIENT_VERSION, API_KEY, API_SECRET_KEY);
		error_log($a);
        echo $a;
    }
    
	if(isset($_TMPFM['fmkey'], $_TMPFM['fmuser'], $_TMPFM['fmsess']))
	{	
		submitQueued();
	}

    if(isset($_POST['nowplaying'], $_TMPFM['fmkey'], $_TMPFM['fmuser'], $_TMPFM['fmsess']))
    {
        $artist        = isset($_POST['artist'])      ?    urlencode($_POST['artist'])      :    'Undefined';
        $song          = isset($_POST['song'])        ?    urlencode($_POST['song'])        :    'Undefined';
        $duration      = isset($_POST['duration'])    ?    urlencode($_POST['duration'])    :    'Undefined';
		$_TMPFM['fmtime'] = $time;

		if($artist != '' && $song != '') {
			echo $playnow    = nowPlaying($_TMPFM['fmsess'], $artist, $song, $duration);
			if(!strstr($playnow, 'OK')) {
				echo doShake($_TMPFM['fmuser'], $_TMPFM['fmkey'], $time, CLIENT_ID, CLIENT_VERSION, API_KEY, API_SECRET_KEY);
				$playnow    = nowPlaying($_TMPFM['fmsess'], $artist, $song, $duration);
			}
		} else {
			echo 'OK Skipped: no tags';
		}
    }
	
    if(isset($_POST['submission'], $_TMPFM['fmkey'], $_TMPFM['fmuser'], $_TMPFM['fmsess'])) {
        $artist          = isset($_POST['artist'])      ?    urlencode($_POST['artist'])      :    'Undefined';
        $song            = isset($_POST['song'])        ?    urlencode($_POST['song'])        :    'Undefined';
        $duration        = isset($_POST['duration'])    ?    urlencode($_POST['duration'])    :    'Undefined';
        $starttime       = isset($_TMPFM['fmtime'])     ?    $_TMPFM['fmtime']                :     time();
		
		if($artist != '' && $song != '') {
			echo $submiss = submission($_TMPFM['fmsess'], $artist, $song, $duration, $starttime);
			if(!strstr($submiss, 'OK')) {
				addToQueueOnError($artist, $song, $duration, $starttime);
				echo doShake($_TMPFM['fmuser'], $_TMPFM['fmkey'], $time, CLIENT_ID, CLIENT_VERSION, API_KEY, API_SECRET_KEY);
			}
		} else {
			echo 'OK Skipped: no tags';
		}
		
    }

	if(isset($_TMPFM) && is_array($_TMPFM) && ($_TMPFM != $ufm)) update_scrobbler_profile($_TMPFM);
	
	dbClose();

?>