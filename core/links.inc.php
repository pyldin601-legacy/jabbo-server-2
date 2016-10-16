<?php

// JABBO Links Module

global $cfg;

$cfg['link_home'] = '/home';

function link_nav_url($id, $name) {
	return '/go/' . $id . '/' . rawurlencode($name);
}

function link_download_url($id, $name) {
	return '/get/' . $id . '/' . rawurlencode($name);
}

function link_listen_url($id, $name) {
	return '/listen/' . $id . '/' . rawurlencode($name);
}

function link_archive_url($id, $name) {
	return '/zip/' . $id . '/' . rawurlencode($name);
}

?>