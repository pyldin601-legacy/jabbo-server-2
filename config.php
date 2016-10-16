<?php

global $cfg, $ismobile;

#if($_SERVER['REQUEST_METHOD'] != 'POST')
#	ob_start("my_ghandler");
	
//echo $_SERVER['SERVER_NAME'];
//die();

$ismobile = false;
	
$cfg['logging_level'] = 0;


/* Global site settings */
$cfg['site_name'] = $_SERVER['SERVER_NAME'];

$cfg['jfs_path'] = $_SERVER['DOCUMENT_ROOT'];
$cfg['audiocover_cache_lifetime'] = 604800;
$cfg['cl'] = '/media/#ETC/JABBO_CACHE';

$cfg['thumb'] = 'tmp/thumbs';

/* session configuration */
$cfg['session_expire'] = 86400;
$cfg['lock_session_ip'] = false;

/* Database configuration */
$cfg['db_host'] = 'localhost';
$cfg['db_user'] = 'root';
$cfg['db_pass'] = 'GDk4F/so';
$cfg['db_base'] = 'search';

/* Registered file formats */
$cfg['types'] = array(
	'audio' 	=> array('mp1', 'mp2', 'mp3', 'ogg', 'm4a', 'tta', 'ape', 'wma', 'flac', 'wav', 'mod', 'xm', 'stm', 'mid'),
	'video' 	=> array('avi', 'mkv', 'flv', 'mp4', 'vob', 'wmv', 'mpeg', 'mpg', '3gp'),
	'image' 	=> array('jpg', 'jpeg', 'png', 'bmp', 'gif', 'tif', 'ico'),
	'archive'	=> array('rar', 'r\d\d', 'zip', '7z', 'tar', 'gz'),
	'iso'     	=> array('iso', 'isz', 'mds', 'mdf', 'nrg', 'bin', 'cue'),
	'playlist' 	=> array('pls', 'm3u', 'm3u8'),
	'document'	=> array('doc', 'txt', 'nfo', 'pdf')
);

/* System tools */
$cfg['restreamer']['convert'] = "/usr/local/bin/ffmpeg";
$cfg['restreamer']['info'] = "/usr/local/bin/mediainfo";
$cfg['restreamer']['curl'] = "/usr/local/bin/curl --silent --globoff";
$cfg['restreamer']['preload'] = 60;
$cfg['download']['buffer_size'] = 8196;
$cfg['navigator']['inf_file'] = '.jabbo.db';
$cfg['cli_ip'] = $_SERVER['REMOTE_ADDR'];

date_default_timezone_set('Europe/Kiev');


function is_uaix($ip) {
	$fp = explode("\n", file_get_contents("prefixes.txt"));
    $ip_dec = ip2long($ip);
    foreach($fp as $f) {
		if(strpos($f, '/')) {
			list($range, $netmask) = explode('/', $f);
			$range_dec = ip2long($range);
			$netmask_dec = bindec( str_pad('', (int)$netmask, '1') . str_pad('', 32 - (int)$netmask, '0') );
			if(($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec)) return true;
		}
    }
    return false;
}

function my_ghandler($buffer, $mode) {
	$encoded = gzencode($buffer);
	$host = $_SERVER['HTTP_HOST'];
	$uri = $_SERVER['REQUEST_URI'];
	$redis = new Redis();
	$redis->connect('127.0.0.1', 6379);
	$redis->setex('uri_cache:' . $host . ':' . md5($uri), 2678400, $encoded);
	header("Content-Encoding: gzip");
	header('Vary: accept-encoding');
	return $encoded;
}


?>