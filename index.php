<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/layout.inc.php';
require 'core/general.inc.php';
require 'core/common.php';

require 'core/fs.inc.php';
require 'core/links.inc.php';


ae_detect_ie();


$page_contents = array();

$dirs_array = jfs_get_dirs_root();
$access_array = jfs_get_dirs_by_rights("*");
$bookmark_array = jfs_get_dirs_bookmark($uid['uid']);
$new_array = jfs_get_dirs_new();


//$page_contents[] = show_table_menu();
$page_contents[] = show_title("Local disks");

foreach($dirs_array as $row)
	$page_contents[] = show_directory_item_dir($row);

if(count($bookmark_array) > 0) {
	$page_contents[] = show_title("Your bookmarks");
	foreach($bookmark_array as $row)
		$page_contents[] = show_directory_item_dir($row);
}

if(count($new_array) > 0) {
	$page_contents[] = show_title("New indexed folders");
	foreach($new_array as $row)
		$page_contents[] = show_directory_item_dir($row);
}

$page_contents[] = show_title("Folders with free access");
foreach($access_array as $row)
	$page_contents[] = show_directory_item_dir($row);

	
$page_contents[] = show_title_g('Copyright &copy; 2012-' . date("Y") . ' by Roman Gemini &middot; Running on FreeBSD');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$json_data['title'] = 'Home @ Jabbo File Server';
	$json_data['body'] = implode('', $page_contents);
	$json_data['query'] = '';
	$json_data['reload'] = 1;
	$json_data['navmode'] = get_current_mode();
	echo json_encode($json_data);
} else {
	echo page_header("Home");
	echo page_banner('');
	echo show_table_head();
	echo implode('', $page_contents);
	echo show_table_footer();
	echo page_footer();
}

my_users_unique("index");
dbClose();

?>