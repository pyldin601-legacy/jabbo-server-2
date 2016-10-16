<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/layout.inc.php';
require 'core/general.inc.php';
require 'core/common.php';

require 'core/fs.inc.php';
require 'core/jabbo.inc.php';
require 'core/links.inc.php';

$_GET = get_query_string();

ae_detect_ie();

jabbo_go_root_cond(! isset($_GET['id'], $_GET['name']) );

$current_page = isset($_POST['page']) ? (int)$_POST['page'] : 1; // 1
$items_per_page = 100;

$show_begin = (($current_page - 1) * $items_per_page) + 1;
$show_end	= ($show_begin + $items_per_page) - 1;

$files_count = ($show_begin - 1);
$files_size = 0;

$items_count = 0;
$fc = 0;
$dirs_count = 0;

$page_contents = array();

$expand = isset($_GET['expand']) ? (bool)$_GET['expand'] : false;

// if($current_page == 1) $page_contents[] = show_table_menu();

// GET
$id  	 = $_GET['id'];
$dirname = $_GET['name'];
$current = array('id' => $id, 'name' => $dirname);


// TEST EXISTS
$exists  = jfs_is_dir_exists($id, $dirname);

if($exists) {

	$parent = jfs_get_parent_from_id($id);
	$dirlist = jfs_explode_path($id);
	$allow = is_allowed($exists, $uid['uid']);

	// FS
	$dirs_array = jfs_get_dirs_dirid($id, $expand);

	$fc = jfs_get_files_count_dirid($id, $expand);
	$files_array = jfs_get_files_dirid($id, $expand, $items_count, $show_begin, $show_end);

	$total_files = count($dirs_array) + $fc;
	$total_pages = ceil($total_files / $items_per_page);
	
	save_current_did($uid['uid'], $id);

	if($current_page == 1) {
		$page_contents[] = show_directory_head($exists, $id, $allow);

		if(! $allow)
			$page_contents[] = show_warning('You have no access to the files in this directory!');

		// $page_contents[] = $expand ? show_collapse($current) : show_expand($current);
		
		if($parent) 
			$page_contents[] = show_directory_return($parent);
		else
			$page_contents[] = show_directory_return_root();
	}
	
	foreach ($dirs_array as $row) {
		$items_count ++;
		if( $items_count >= $show_begin && $items_count <= $show_end ) {
			$page_contents[] = show_directory_item_dir($row); 
			$files_count ++;
		}
	}

    $prev_head = isset($_POST['prev']) ? $_POST['prev'] : basename($exists);
    foreach($files_array as $row) {
		$current_head = basename($row['filepath']);
		if($prev_head != $current_head) {
			$prev_head = $current_head;
			$page_contents[] = show_directory_loc($row['filepath']);
		}
			$page_contents[] = show_directory_item_file($row);
			$files_count ++;
			$files_size += $row['filesize'];
	}
		
	if($current_page == 1) mysql_query("INSERT INTO `jfs_dir_stats` VALUES(${id}, 1, 0) ON DUPLICATE KEY UPDATE `opened` = `opened` + 1");

	if($total_pages > $current_page) 
		$page_contents[] = show_next_page($current_page + 1, $prev_head);
	else
		$page_contents[] = show_directory_summary($files_count, $files_size, $id);

} else {

	$page_contents[] = show_directory_root();
	$page_contents[] = show_info("Directory not found!");
	$page_contents[] = show_title_g("Directory not found!");

}


if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$json_data['title'] = urldecode(html_entity_decode($current['name'])) . ' @ Jabbo File Server';
	
	if($current_page == 1)
		$json_data['body'] = implode("\n",$page_contents);
	else
		$json_data['append'] = implode("\n",$page_contents);
	
	$json_data['query'] = '';
	$json_data['reload'] = 1;
	$json_data['navmode'] = get_current_mode();
	echo json_encode($json_data);
} else {
	echo page_header($current['name']);
	echo page_banner('');
	echo show_table_head();
	echo implode("\n",$page_contents);
	echo show_table_footer();
	echo page_footer();
}

my_users_unique("navigate");
tlog("Navigation to $exists");
dbClose();

?>