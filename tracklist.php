<?php

require 'config.php';
require 'core/general.inc.php';
require 'core/auth.inc.php';
require 'core/common.php';

header("Content-type:text/plain; charset=utf-8");

if(empty($_GET['id'])) {
	header("HTTP/1.1 404 Not Found");
	die("404 Not Found");
}

$id = (int) $_GET['id'];

$query = mysql_query("SELECT * FROM `search_folders` WHERE `id` = '$id' LIMIT 1");

if(mysql_num_rows($query) == 0) {
	header("HTTP/1.1 404 Not Found");
	die("404 Not Found");
}

$row = mysql_fetch_assoc($query);

$location = mysql_real_escape_string( $row['parent'] . '/' . $row['child'] );

$query = mysql_query("SELECT * FROM `search_files` WHERE `filepath` = '$location' AND `filegroup` = 'audio' ORDER BY `filepath`, `filename`");

$tracks 	= array();
$artists 	= array();
$albums 	= array();
$band 		= array();
$year 		= array();

$time 		= 0;

if(mysql_num_rows($query) > 0) {
    while($row = mysql_fetch_assoc($query)) {
        $time += ($row['avg_duration'] / 1000);
        $tracks[] = $row;
        $artists[$row['audio_artist']] = $row['audio_title'];
        $albums[$row['audio_album']] = $row['audio_title'];
        $band[$row['audio_band']] = $row['audio_title'];
    }

	if(count($band) == 1) {
		$art = array_keys($band);
		$art = $art[0];
		if($art != '') 
			echo "Artist : $art\r\n";
		else {
			if(count($artists) == 1) {
				$art = array_keys($artists);
				$art = $art[0];
				if($art != '') 
					echo "Artist : $art\r\n";
			}
		}
	}
	
	if(count($albums) == 1) {
		$alb = array_keys($albums); 
		$alb = $alb[0];
		if($alb != '') echo "Album  : $alb\r\n";
	}
		
    echo "\r\nTracklist:\r\n";
    foreach($tracks as $row) {
        if(count($artists) > 1)
            echo sprintf("%02d. %s - %s [%s]\r\n", $row['audio_tracknum'], $row['audio_artist'], $row['audio_title'], my_sec_to_time($row['avg_duration'] / 1000));
        else
            echo sprintf("%02d. %s [%s]\r\n", $row['audio_tracknum'], $row['audio_title'], my_sec_to_time($row['avg_duration'] / 1000));
    }
} else {
    echo 'No tracks in this folder';
}

echo "\r\n---\r\nTotal tracks : " . count($tracks);
echo "\r\nTotal time : " . my_sec_to_time($time);
dbClose();

?>