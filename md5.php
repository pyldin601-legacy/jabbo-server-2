<?php
if(isset($_GET['f'])) {
	$file = $_GET['f'];
	if(is_file($file))
		echo md5($file);
}
?>