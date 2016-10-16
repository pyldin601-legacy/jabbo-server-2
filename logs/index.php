<?php

//$log_file = "jabbo.log";
$log_file = "/var/log/mpd.log";

$data = array();
$wait_time = 10;
$pre_load = 20000;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$fs = filesize($log_file);

	$from = isset($_POST['f']) ? (int) $_POST['f'] : -1;
	if($from == -1)
		$from = ($fs <= $pre_load ? 0 : $fs - $pre_load );

	$start = time();
	while($start+$wait_time>time()) {
		if(file_exists($log_file)) {
			$fh = fopen($log_file, 'r');
			fseek($fh, $from);
			while($bytes = fread($fh, 4096)) {
				array_push($data, $bytes);
			}
			$end = ftell($fh);
			fclose($fh);
		}
		if(count($data) > 0) {
			$result = array(
				'status' => 'OK',
				'size' => $end,
				'data' => implode('', $data)
			);
			die(json_encode($result));
		}
		usleep(250000);
	}
	die(json_encode(array('status' => 'null')));
}

?>
<!DOCTYPE HTML>
<html>
<head>
	<title>JFS Logger</title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<link href="style.css" rel="stylesheet" />
	<script type="text/javascript" src="jquery-1.7.1.min.js"></script>
	<script type="text/javascript" src="errors.js"></script>
</head>
<body>
	<div class="data-log"></div>
</body>
</html>
