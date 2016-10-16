<?php

require 'tags/getid3.php';

global $cfg;

$getID3 = new getID3;
$getID3->encoding = 'UTF-8';

function get_file_tags($filename, $index = 0) {

	global $getID3;
	$cache = mysql_fetch_assoc(mysql_query("select * from `metadata` where `index` = '$index'"));

    if($cache) {
		$mtag['artist'] = $cache['artist'];
		$mtag['title'] = $cache['title'];
		$mtag['track'] = $cache['track'];
		$mtag['length'] = $cache['length'];
	} else {
		$id3 = $getID3->analyze($filename);
		getid3_lib::CopyTagsToComments($id3);
		if($id3) {
			$mtag['artist'] = $id3['comments']['artist'][0];
			$mtag['title'] = $id3['comments']['title'][0];
			$mtag['track'] = $id3['comments']['track'][0] ? $id3['comments']['track'][0] : $id3['comments']['tracknumber'][0];
			$mtag['length'] = (int)($id3['filesize'] / ($id3['audio']['bitrate'] / 8));
			mysql_query("insert into `metadata` values ('$index', '" . mysql_real_escape_string($mtag['artist']) . "', '" . mysql_real_escape_string($mtag['title']) . "', '" . mysql_real_escape_string($mtag['track']) . "', '".mysql_real_escape_string($mtag['length'])."');");
		} else {
			$mtag = false;
		}
	}

	return $mtag;

}

?>