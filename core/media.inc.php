<?php

/* mediainfo php module */

function mediafile_info($path) {

	$section = 'Default';
	$params = array();
	
	$stream = shell_exec("/usr/local/bin/mediainfo -f " . escapeshellarg($path));
	foreach(explode("\n", $stream) as $value) {
		if(strpos($value, ':') != false) {
			list($key, $val) = explode(":", $value, 2);
			$key = trim($key);
			$val = trim($val);
			if(empty($params[$section][$key])) $params[$section][$key] = array();
			array_push($params[$section][$key], $val);
		} else {
			$section = $value;
		}
	}
	return $params;
}

?>