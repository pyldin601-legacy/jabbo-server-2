<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/common.php';
require 'core/media.inc.php';

$cache_on = true;

ob_start("ob_gzhandler");

if(empty($_GET['id'])) my_404();

$id = (int) $_GET['id'];
$scale = isset($_GET['s']) ? (int) $_GET['s'] : 300;

$query = mysql_query("SELECT CONCAT(`filepath`, '/', `filename`), `filepath` FROM `search_files` WHERE `index` = $id LIMIT 1");

if(mysql_num_rows($query) == 0)	my_404();

list($filename, $path) = mysql_fetch_array($query);

$group = get_file_class($filename);

if(! is_allowed($path, $uid['uid'])) {
	header("HTTP/1.1 403 Forbidden");
	die();
}

$mtime = filemtime($filename);

header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mtime) . " GMT");
header('Cache-Control: max-age=0');

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mtime) {
	header('HTTP/1.1 304 Not Modified');
	die();
}

$md5 = md5($_SERVER['QUERY_STRING']);
$cache_file = get_cache_location("cover", $md5, "png");

if($cache_on && file_exists($cache_file)) {
	header("Content-type: image/png");
	header("Content-Disposition: filename=\"cover_${id}_${scale}.png\"");
	echo file_get_contents($cache_file);
	touch($cache_file);
	mysql_query("INSERT INTO `jfs_cache_stats` (`subject`, `uncached`, `cached`) VALUES ('cover', 0, 1) ON DUPLICATE KEY UPDATE `cached` = `cached` + 1");
	die();
}

if($group == 'audio') {
	$mediainfo = mediafile_info($filename);
	if(empty($mediainfo['General']['Cover_Data'][0])) my_404();
	$cover = $mediainfo['General']['Cover_Data'][0];
	$im = imagecreatefromstring(base64_decode($cover));
} else if($group == 'image') {
	$im = imagecreatefromstring(file_get_contents($filename));
}

if($im == false) my_404();

header("Content-type: image/png");
header("Content-Disposition: filename=\"cover_${id}.png\"");

$w = imagesx($im);
$h = imagesy($im);
$a = $h / $w;

if($w > $scale) {
	$dw = $scale;
	$dh = $scale * $a;
} else if($h > $scale) {
	$dh = $scale;
	$dw = $scale / $a;
} else {
	$dw = $scale;
	$dh = $scale;
}
	
$mini = imagecreatetruecolor($dw, $dh);

imagecopyresampled($mini, $im, 0, 0, 0, 0, $dw, $dh, $w, $h);

create_cache_location("cover", $md5);

imagepng($mini, $cache_file);
imagepng($mini);
mysql_query("INSERT INTO `jfs_cache_stats` (`subject`, `uncached`, `cached`) VALUES ('cover', 1, 0) ON DUPLICATE KEY UPDATE `uncached` = `uncached` + 1");

dbClose();

function my_404() {
	header("HTTP/1.1 404 Not found");
	echo '<h1>Image not found!</h1>';
	die();
}

?>