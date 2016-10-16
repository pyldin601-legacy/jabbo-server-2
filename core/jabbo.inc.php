<?php
/*
	Jabbo File Server Core :: Base Functions
*/

function jabbo_go_root_cond($condition) {
	if($condition) {
		header("Location: /"); 
		die("Redirecting to <a href=\"/\">/</a>...");
	}
}

function jabbo_search_request($request) {
	$string = $request;
	$keys = array('hash','id','title','artist','band','album','genre','file','extension','path');
	foreach($keys as $key) {
		//preg_match("/($key)\:((\w+)|\"(.*?)\")/",$string,$arr);
		//error_log(print_r($arr,true));
	}
}

function jabbo_download_link($array) {
	global $uid;
	if( is_allowed($array['filepath'], $uid['uid']) )
		return '/file-' . $array['index'] . '--' . get_challenge($array['index']) . '/' . rawurlencode($array['filename']);
	else
		return '/file-' . $array['index'] . '-' . $uid['session'] . '/' . rawurlencode($array['filename']);
}

?>