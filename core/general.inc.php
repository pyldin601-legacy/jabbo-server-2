<?php

function seconds_to_time($sec) {
	return sprintf("%01d", floor($sec/60)) . ':' . sprintf("%02d",  $sec % 60) ;
}

/* filetype icon */
function file_icon($ftype) {
	global $cfg;
	foreach($cfg['types'] as $tname => $type) {
		foreach($type as $ntype) {
			if($ntype == strtolower($ftype['filetype']))
				return '<img class="icon-' . $tname . '" id="icon-id-' . $ftype['index'] . '" src="/images/ft_' . $tname . '.png" />';
		}
	}
	return '<img src="/images/ft_file.png" />';
}

function lazy($str) {
	return mysql_real_escape_string($str);
}

function human_size($number) {
		return number_format($number, 0, '.', ' ') . ' bytes';
}

/* common functions */
function human_date($indate) {
  $inunix = strtotime($indate);
  $delta = time() - $inunix;
  $ru_month = array('jan', 'feb', 'mar', 'apr', 
					'may', 'jun', 'jul', 'aug', 
					'sep', 'oct', 'nov', 'dec');

  switch(true) {
    case ($delta < 60):			{ return $delta . " sec. ago"; }
    case ($delta < 60*60):		{ return floor($delta/60) . " min. ago"; }
    case ($delta < 60*60*24):	{ return floor($delta/3600) . " hr. ago"; }
    case ($delta < 60*60*24*7):	{ return floor($delta/86400) . " days ago"; }
    default:					{ return date("j", $inunix) . " " . $ru_month[date("m", $inunix)-1] . " " . date("Y", $inunix); }
  }
}

function path_parse($inpath) {
	global $cfg, $directoryID;
	$sql_path = mysql_real_escape_string($inpath);
	$root = mysql_result(mysql_query("SELECT `root` FROM `search_folders` WHERE CONCAT(`parent`, '/', `child`) = '$sql_path' OR (`parent` = '' AND `root` = '$sql_path') LIMIT 1"), 0, 0);

	$root_exp = explode('/', $root);
	$root_name = end($root_exp);
	
	$work_path = $inpath;
	$outPath = "";
	
	$iters = 0;
	
	while($work_path != NULL) {
		$iters++;
		$work_exp = explode('/', $work_path);
		$work_name = end($work_exp);

		$path_id = pathNameToId($work_path);
	    if($work_path != $root) {
			if(strrpos($work_path, '/') > 0) {
				$work_path = substr($work_path, 0, strrpos($work_path, '/'));
			} else {
				$work_path = NULL;
			}
	    } else {
			$work_name = $root_name;
			$work_path = NULL;
	    }
		$tooltip = "/dirinfo.php?id=" . $path_id;
		$url = link_nav_url($path_id, $work_name);
		$outPath = ' <nobr>&raquo; <a class="ajlink popupfolder new" dir="' . $path_id . '" href="'.$url.'">' . $work_name . '</a></nobr>' . $outPath;
	}
	$outPath = ' <nobr>&raquo; <a class="ajlink popupfolder new" dir="0" href="'.$cfg['link_home'].'">Home</a></nobr>' . $outPath;
    return $outPath;
}

function pathRight($path) {
	if(substr($path, strlen($path) - 1, 1) != '/')
		return $path.'/';
	else
		return $path;
}

function pathLeft($path) {
	if(substr($path, strlen($path) - 1, 1) == '/')
		return substr($path, 0, strlen($path) - 1);
	else
		return $path;
}

function pathIdToName($id) {
	
	$result = mysql_query("select `child` from `search_folders` where `id` = '$id' LIMIT 1");

	if(mysql_num_rows($result) == 1)
		return mysql_result($result, 0, 0);

	return false;
}


function pathNameToId($name) {
	global $directoryID;

	if(isset($directoryID[$name]))
		return $directoryID[$name];
		
	$result = mysql_query(sprintf("
		SELECT `id` 
		FROM `search_folders` 
		WHERE (CONCAT(`parent`, '/', `child`) = '%s') OR (`root` = '%s' && `parent` = '') LIMIT 1", mysql_real_escape_string(pathLeft($name)), mysql_real_escape_string(pathLeft($name))));
	
	if(mysql_num_rows($result) == 1) {
		$directoryID[$name] = mysql_result($result, 0, 0);
		return $directoryID[$name];
	}
	
	return false;
}

function ae_detect_ie() {
    if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
		header("Content-type: text/html; charset=utf-8");
		echo "<html><head><title>Stop Internet Explorer!</title></head><body style=\"font-family:tahoma;font-size:14px\">
		<center>
			<img style=\"margin:50px\" src=\"/images/noie.jpg\" alt=\"Stop IE!\" width=\"350\" height=\"350\"><br>
			Sorry, but I have no time to make this site working in IE!
		</center></body></html>";
		die();
	}
}

function read_settings($file) {
	if ($fc = file_get_contents($file)) {
		foreach(explode("\n", $fc) as $line) {
			list($parm,$value) = explode(':', $line, 2);
			$settings[$parm] = $value;
		}
		return $settings;
	}
	return;
}


function mime_content_type_my($filename) {

        $mime_types = array(

			// other
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        }
        else {
            return 'application/octet-stream';
        }
}

function myenc($str) {
	return $str;
}

function mydec($str) {
	return $str;
}
?>