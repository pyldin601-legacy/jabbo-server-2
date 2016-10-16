<?php
/*
	Jabbo File Server Core :: File System Functions
	
	* functions:
		* fs navigation :
			* jfs_get_files_dirid($id, $expand) !
			* jfs_get_files_count_dirid($id, $expand = false)
			* jfs_get_dirs_dirid($id) !
			* jfs_get_files_dirname($dirname, $expand)
			* jfs_get_dirs_dirname($dirname)
			* jfs_get_dirs_root()
		* fs search
			* jfs_get_files_query($query, $start, $max)
		* helpers :
			* jfs_path_id_to_name($id)
			* jfs_path_name_to_id($dirname)
			* jfs_explode_path($id)
			* jfs_get_parent_from_id($id)
			* jfs_is_dir_exists($id, $dirname)
*/

$opts = array(
	'sql_limit' => 1000
);

function jfs_get_files_dirid($id, $expand = false, &$cc, $ll, $ul) {

	$path = jfs_path_id_to_name($id);
	
	if(! $path) return false;
	
	return jfs_get_files_dirname($path, $expand, $cc, $ll, $ul);
	
}

function jfs_get_files_count_dirid($id, $expand = false) {

	$path = jfs_path_id_to_name($id);
	
	if(! $path) return false;
	
	return jfs_get_files_count_dirname($path, $expand);
	
}

function jfs_get_dirs_dirid($id, $expand = false) {

	if($expand) return array();
	
	$path = jfs_path_id_to_name($id);
	
	if(! $path) return false;
	
	return jfs_get_dirs_dirname($path);
	
}

function jfs_get_files_count_dirname($dirname, $expand) {

	global $link, $opts;

	$dirname_sql = mysql_real_escape_string($dirname);
	
	if($expand)
		$rs = mysql_query("
			SELECT COUNT(*)
			FROM `search_files` 
			WHERE 
				`filepath` = '${dirname_sql}' 
			OR 
				`filepath` LIKE '${dirname_sql}/%'
		");
	else
		$rs = mysql_query("
			SELECT COUNT(*) 
			FROM `search_files` 
			WHERE `filepath` = '${dirname_sql}'
		");
	
	if(!$rs) return false;

	$fc = mysql_result($rs, 0, 0);

	return $fc;

}

function jfs_get_files_dirname($dirname, $expand = false, &$cc, $ll, $ul) {

	global $link, $opts;
	
	//if($cc < $ll || $cc > $ul) return array();
	
	//$limit_u = 
	
	$dirname_sql = mysql_real_escape_string($dirname);
	
	if($expand)
		$rs = mysql_query("
			SELECT * 
			FROM `search_files` 
			WHERE `filepath` = '${dirname_sql}' OR `filepath` LIKE '${dirname_sql}/%'
			ORDER BY `filepath`, `filetype`, `filename` 
		");
	else
		$rs = mysql_query("
			SELECT * 
			FROM `search_files` 
			WHERE `filepath` = '${dirname_sql}'
			ORDER BY `filepath`, `filetype`, `filename` 
		");
	
	if(!$rs) return false;

	$f_rows = array();

	while($f = mysql_fetch_assoc($rs)) {
		$cc ++;
		if($cc >= $ll && $cc <= $ul)
			$f_rows[] = $f;
	}
	
	return $f_rows;
}

function jfs_get_dirs_dirname($dirname) {
	global $link, $opts;

	$dirname_sql = mysql_real_escape_string($dirname);

	$rs = mysql_query("
		SELECT * 
		FROM `search_folders` 
		WHERE `parent` = '${dirname_sql}' 
		ORDER BY `child`
	");

	if(!$rs) return false;
	
	$d_rows = array();
	
	while ($d = mysql_fetch_assoc($rs))
		$d_rows[] = $d;
	
	return $d_rows;
}

function jfs_get_dirs_by_rights($users) {

	global $link, $opts;

	$users_sql = mysql_real_escape_string($users);

	$rs = mysql_query("
		SELECT * 
		FROM `rights` 
		WHERE `users` = '${users_sql}' 
		ORDER BY `path`
	");

	if(!$rs) return false;
	
	$d_rows = array();
	
	while ($d = mysql_fetch_assoc($rs)) {
		$id = jfs_path_name_to_id(rtrim($d['path'], "/"));
		$sb = mysql_query("SELECT * FROM `search_folders` WHERE `id` = '$id' LIMIT 1");
		if(mysql_num_rows($sb) == 1)
			$d_rows[] = mysql_fetch_assoc($sb);
	}
	
	return $d_rows;

}

function jfs_get_dirs_bookmark($userid) {

	global $link, $opts;

	$userid_sql = mysql_real_escape_string($userid);

	$rs = mysql_query("
		SELECT * 
		FROM `jfs_folder_bookmark` 
		WHERE `user_id` = '${userid_sql}' 
	");

	if(!$rs) return false;
	
	$d_rows = array();
	
	while ($d = mysql_fetch_assoc($rs)) {
		$id = (int) $d['folder_id'];
		$sb = mysql_query("SELECT * FROM `search_folders` WHERE `id` = '$id' LIMIT 1");
		if(mysql_num_rows($sb) == 1)
			$d_rows[] = mysql_fetch_assoc($sb);
	}
	
	return $d_rows;

}

function jfs_get_dirs_new() {

	global $link, $opts;

	$rs = mysql_query("SELECT * FROM `search_folders` WHERE 1 ORDER BY `itime` DESC LIMIT 5");

	if(!$rs) return false; 
	$d_rows = array();
	
	while ($d = mysql_fetch_assoc($rs))
		$d_rows[] = $d;
	
	return $d_rows;

}

function jfs_get_dirs_root() {
	global $link, $opts;

	$rs = mysql_query("SELECT * FROM `search_folders` WHERE `parent` = '' ORDER BY `child`");

	if(!$rs) return false;
	
	$d_rows = array();
	
	while ($d = mysql_fetch_assoc($rs))
		$d_rows[] = $d;
	
	return $d_rows;
}

function jfs_path_id_to_name($id) {
	global $link;
	$rs = mysql_query("SELECT CASE WHEN `parent` = '' THEN `root` ELSE CONCAT(`parent`, '/', `child`) END FROM `search_folders` WHERE `id` = '$id' LIMIT 1");

	if( (!$rs) || (mysql_num_rows($rs)==0) ) return false;

	return mysql_result($rs, 0, 0);
}

function jfs_path_name_to_id($dirname) {
	global $link;
	
	$dirname_sql = mysql_real_escape_string($dirname);

	$rs = mysql_query("
		SELECT `id` 
		FROM `search_folders` 
		WHERE (CONCAT(`parent`, '/', `child`) = '${dirname_sql}') 
		OR (`root` = '${dirname_sql}' && `parent` = '') 
		LIMIT 1
	");
	
	if( (!$rs) || (mysql_num_rows($rs)==0) ) return false;

	return mysql_result($rs, 0, 0);
}

function jfs_explode_path($id) {
	global $link;

	$path = jfs_path_id_to_name($id);

	if(! $path) return false;

	$path_sql = mysql_real_escape_string($path);

	$rs = mysql_query("
		SELECT `root` 
		FROM `search_folders` 
		WHERE CONCAT(`parent`, '/', `child`) = '${path_sql}' 
		OR (`parent` = '' AND `root` = '${path_sql}') 
		LIMIT 1
	");

	if(!$rs || mysql_num_rows($rs)==0) return false;
	
	$root = mysql_result($rs, 0, 0);
	$work_path = $path;
	$dirs_hash = array();
	
	while(true) {
		$path_id = jfs_path_name_to_id($work_path);
		array_unshift($dirs_hash, array(
			'id' => $path_id,
			'name' => basename($work_path)
		));
	    if(($work_path != $root) && (strrpos($work_path, '/') > 0))
			$work_path = substr($work_path, 0, strrpos($work_path, '/'));
		else break;
	}
	
    return $dirs_hash;	
}

function jfs_get_parent_from_id($id) {
	global $link;
	
	$rs = mysql_query("
		SELECT `parent`
		FROM `search_folders`
		WHERE `id` = $id
		LIMIT 1
	");
	
	if( (!$rs) || (mysql_num_rows($rs)==0) ) return false;

	$parent = mysql_result($rs, 0, 0);
	
	if($parent == '') return false;
	
	if($parent_id = jfs_path_name_to_id($parent))
		return array(
			'id' => $parent_id,
			'name' => basename($parent)
		);

	return false;
}

function jfs_is_dir_exists($id, $dirname) {
	global $link;
	
	if($id == 0) return 1;
	
	$dirname_sql = mysql_real_escape_string($dirname);
	$rs = mysql_query("
		SELECT CASE WHEN `parent` = '' THEN `root` ELSE CONCAT(`parent`, '/', `child`) END 
		FROM `search_folders` 
		WHERE `id` = '${id}' AND `child` = '${dirname_sql}'
		LIMIT 1
	");

	if(mysql_num_rows($rs) == 1)
		return mysql_result($rs, 0, 0);
	else
		return false;
}

function jfs_get_files_query($query, $start, $max) {
	global $link, $opts;

	$rs = mysql_query($query);
	
	if($start > 0) mysql_data_seek($rs, $start);
	
	if(!$rs) return false;

	$f_rows = array();

	while($f = mysql_fetch_assoc($rs) && $max > 0) {
		$f_rows[] = $f;
		$max --;
	}
	
	return $f_rows;
}

?>