<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/general.inc.php';

$w = 48;

if(isset($_GET['size']) && ($_GET['size'] == 'normal')) $w = 380;

if(isset($_GET['id'])) $index = (int)$_GET['id'];

if($index == 0) die();

if($result = mysql_query("select * from `search_files` where `index`='$index' limit 1")) {
	$row = mysql_fetch_assoc($result);
	$filename = $row['filepath'] . '/' . $row['filename'];

	if(! is_allowed($row['filepath'], $uid['uid'])) {
		header('Content-Type: image/png');
		echo file_get_contents("images/ad.png");
		exit;
	}
	
	if(file_class($row['filetype']) == 'image') {
		if(list($width, $height) = getimagesize($filename)) {
			if($width > $w) {
				$newheight = $height / $width * $w;
				$newwidth = $w;
			} else {
				$newheight = $height;
				$newwidth = $width;
			}

			switch(strtolower($row['filetype'])) {
				case 'jpg' : 
				case 'jpeg': { $im = @imagecreatefromjpeg($row['filepath'] . '/' . $row['filename']); break; }
				case 'png' : { $im = @imagecreatefrompng($row['filepath'] . '/' . $row['filename']); break; }
//				case 'bmp' : { $im = @imagecreatefromwbmp($row['filepath'] . '/' . $row['filename']); break; }
				case 'gif' : { $im = @imagecreatefromgif($row['filepath'] . '/' . $row['filename']); break; }
			}
			
			if($width < 380) {
				$newwidth = 380;
				$pad_x = ($newwidth - $width) / 2;
				$dest_width = $width;
			} else {
				$pad_x = 0;
				$dest_width = $newwidth;
			}

			$thumb = imagecreatetruecolor($newwidth, $newheight);

			$background = imagecolorallocate($thumb, 255, 255, 255);
			imagefill($thumb, 0, 0, $background);

			imagecopyresampled($thumb, $im, $pad_x, 0, 0, 0, $dest_width, $newheight, $width, $height);

			header('Content-Type: image/png');
			header('Cache-Control: public,max-age=3600');

			imagepng($thumb, '', 9);
			imagedestroy($thumb);
		}
	}
	
}

dbClose();

?>