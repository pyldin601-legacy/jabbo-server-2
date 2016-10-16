<?php
function test_304($mtime) {

	$abs_mtime = filemtime($_SERVER['SCRIPT_FILENAME']);
	if($abs_mtime > $mtime) $mtime = $abs_mtime;
	error_log($abs_mtime);
	
	header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mtime) . " GMT");
	header('Cache-Control: max-age=0');
	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) <= $mtime) {
		header('HTTP/1.1 304 Not Modified');
		die;
	}

}
?>