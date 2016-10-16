<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/layout.inc.php';
require 'core/general.inc.php';
require 'core/common.php';
require 'core/jabbo.inc.php';

$sr = '';

if(ae_detect_ie()) {
	header('HTTP/1.1 403 Forbidden');
	exit; 
}

$file_id = (int)$_GET['f'];

if($file_id > 0) {

	$result = mysql_query("select * from `search_files` where `index` = '$file_id' limit 1");
	if(mysql_num_rows($result)) {
		$row = mysql_fetch_assoc($result);
		$href = jabbo_download_link($row);
		$sr .= '<a class="item" target="_blank" href="'.$href.'">Download file</a>';
		$sr .= '<div class="split"></div>';
		$sr .= '<a class="item" href="" onclick="return nav(\'/search.php?q=@id%20'.$row['index'].'\');">Focus on this file</a>';
		$sr .= '<a class="item" href="" onclick="return nav(\'/search.php?q=@md5%20'.$row['md5'].'\');">Find files with same hash</a>';
		if($uid['uid'] == 1) {
			$sr .= '<div class="split"></div>';
			$sr .= '<a class="item" href="#" onclick="return rename_file('.$row['index'].');">Rename file...</a>';
			$sr .= '<a class="item" href="#" onclick="return delete_file('.$row['index'].');">Delete file</a>';
		}
		if($row['filegroup'] == 'audio') {
			if($row['audio_artist']) {
				$sr .= '<div class="split"></div>';
				$sr .= '<a class="item" href="/search.php?q=@artist '.urlencode($row['audio_artist']).'" onclick="return change_location(\'/search.php?q=@artist '.urlencode($row['audio_artist']).'\');">Find music by <b>'.$row['audio_artist'].'</b></a>';
			}
		}
		echo $sr;
	}
}

dbClose();

?>


