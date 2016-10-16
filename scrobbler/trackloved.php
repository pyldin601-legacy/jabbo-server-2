<?php

if(isset($_GET['artist'], $_GET['title'], $_GET['user'])) {

    $artist     = rawurlencode($_GET['artist']);
    $title      = rawurlencode($_GET['title']);
    $user       = rawurlencode($_GET['user']);

    $url = "http://ws.audioscrobbler.com/2.0/?method=track.getInfo&api_key=f7a8f639e4747490849e3bc33475b118&artist=$artist&track=$title&username=$user&format=json";

    $resp = file_get_contents($url);
    $result = json_decode($resp);


    if($result->track->userloved == 1) {
		header("Content-type: image/png");
        echo file_get_contents("../images/icons/love.png");
	}

}

?>