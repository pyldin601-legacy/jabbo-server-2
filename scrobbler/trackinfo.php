<?php

if(isset($_GET['artist'], $_GET['title'], $_GET['user'])) {

	$artist     = rawurlencode("Jia Peng Fang");
	$title      = rawurlencode("Light Dance");
	$user       = rawurlencode("TedIrens");

	$url = "http://ws.audioscrobbler.com/2.0/?method=track.getInfo&api_key=f7a8f639e4747490849e3bc33475b118&artist=$artist&track=$title&username=$user&format=json";

	$resp = file_get_contents($url);

	if(isJSON($resp)) {
		echo $resp;
	} else {
		echo json_encode(array('error' => 'not_json'));
	}

}

function isJSON($string) {
	json_decode($string);
	return (json_last_error() == JSON_ERROR_NONE);
}

?>