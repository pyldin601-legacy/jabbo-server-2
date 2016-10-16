<?php

function my_bytes_to_human($integer) {
	$el = explode(" ", "B KiB MiB GiB TiB");
	for($pw=0; $integer>1024; $pw++) $integer /= 1024;
	return number_format($integer, 2, '.', '') . ' ' . $el[$pw]; 
}

function my_count_to_human($integer) {
	$el = explode(" ", " k M G T");
	for($pw=0; $integer>1000; $pw++) $integer /= 1000;
	return number_format($integer, 0, '.', '') . $el[$pw]; 
}

function my_format_size($integer) {
	return number_format($integer, 0, ".", " ");
}

function my_sec_to_time($seconds) {

	$seconds = (int) $seconds;

	$hur = (int) ($seconds / 3600);
	$min = (int) ($seconds / 60) % 60;
	$sec = (int) ($seconds) % 60;

	if($hur > 0)
		return sprintf("%02d:%02d:%02d", $hur, $min, $sec);
	else
		return sprintf("%02d:%02d", $min, $sec);
}

function my_human_date($indate) {
  $inunix = strtotime($indate);
  $delta = time() - $inunix;
  $ru_month = explode(' ', 'jan feb mar apr may jun jul aug sep oct nov dec');

  switch(true) {
    case ($delta < 60):			{ return $delta . " sec. ago"; }
    case ($delta < 3600):		{ return floor($delta / 60) . " min. ago"; }
    case ($delta < 86400):		{ return floor($delta / 3600) . " hr. ago"; }
    case ($delta < 604800):		{ return floor($delta / 86400) . " days ago"; }
    default:					{ return date("j", $inunix) . " " . $ru_month[date("m", $inunix)-1] . " " . date("Y, H:i:s", $inunix); }
  }
}

function get_cache_location($name, $hash, $ext) {
	global $cfg;
	return $cfg['jfs_stm'] . '/' . $name . '/' . substr($hash, 0, 1) . '/' . substr($hash, 0, 2) . '/' . $hash . '.' . $ext;
}

function create_cache_location($name, $hash) {
	global $cfg;
	if(! is_dir($cfg['jfs_stm'] . $name . '/' . substr($hash, 0, 1) . '/' . substr($hash, 0, 2)) )
		mkdir($cfg['jfs_stm'] . $name . '/' . substr($hash, 0, 1) . '/' . substr($hash, 0, 2), 0777, true);
}

function get_query_string() {
	$n = array();
	foreach(explode('&', $_SERVER['QUERY_STRING']) as $line) {
		if(strpos($line, '=') != false) {
			list($key, $val) = explode('=', $line, 2);
			$n[$key] = urldecode($val);
		}
	}
	return $n;
}

function xmlstr_to_array($xmlstr) {
	$doc = new DOMDocument();
	$doc->loadXML($xmlstr);
	return domnode_to_array($doc->documentElement);
}

function domnode_to_array($node) {
  $output = array();
  switch ($node->nodeType) {
   case XML_CDATA_SECTION_NODE:
   case XML_TEXT_NODE:
    $output = trim($node->textContent);
   break;
   case XML_ELEMENT_NODE:
    for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
     $child = $node->childNodes->item($i);
     $v = domnode_to_array($child);
     if(isset($child->tagName)) {
       $t = $child->tagName;
       if(!isset($output[$t])) {
        $output[$t] = array();
       }
       $output[$t][] = $v;
     }
     elseif($v) {
      $output = (string) $v;
     }
    }
    if(is_array($output)) {
     if($node->attributes->length) {
      $a = array();
      foreach($node->attributes as $attrName => $attrNode) {
       $a[$attrName] = (string) $attrNode->value;
      }
      $output['@attributes'] = $a;
     }
     foreach ($output as $t => $v) {
      if(is_array($v) && count($v)==1 && $t!='@attributes') {
       $output[$t] = $v[0];
      }
     }
    }
   break;
  }
  return $output;
}

function flv_path($hash) {
	global $cfg;
	return $cfg['cl'] . '/FLV/' . substr($hash, 0, 1) . "/" . substr($hash, 0, 2) . "/" . $hash . ".flv";
}

function get_challenge($key) {

	$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	
	$code = 0;
	$challenge = "";

	$raw = md5(md5($key . "-my-static-xor-string-" . date("d-m-Y", time())), true);
	
	foreach(unpack('V*', $raw) as $cint)
		$code += $cint;

	while($code > strlen($characters)) {
		$symbol = $code % strlen($characters);
		$code = $code / strlen($characters);
		$challenge .= substr($characters, $symbol, 1);
	}

	return $challenge;

}

function replace_extension($filename, $new_extension) {
    $info = pathinfo($filename);
    return $info['filename'] . '.' . $new_extension;
}

function increase_download_count($uid) {
	global $link;
	mysql_query("INSERT INTO `jfs_users_stats` (`uid`, `down_count`) VALUES ('${uid}', 1) ON DUPLICATE KEY UPDATE `down_count` = `down_count` + 1");
}

function increase_play_count($uid) {
	global $link;
	mysql_query("INSERT INTO `jfs_users_stats` (`uid`, `play_count`) VALUES ('${uid}', 1) ON DUPLICATE KEY UPDATE `play_count` = `play_count` + 1");
}

function increase_zip_count($uid) {
	global $link;
	mysql_query("INSERT INTO `jfs_users_stats` (`uid`, `zip_count`) VALUES ('${uid}', 1) ON DUPLICATE KEY UPDATE `zip_count` = `zip_count` + 1");
}

function increase_watch_count($uid) {
	global $link;
	mysql_query("INSERT INTO `jfs_users_stats` (`uid`, `watch_count`) VALUES ('${uid}', 1) ON DUPLICATE KEY UPDATE `watch_count` = `watch_count` + 1");
}

function save_current_did($uid, $did) {
	global $link;
	mysql_query("INSERT INTO `jfs_users_stats` (`uid`, `last_did`) VALUES ('${uid}', '${did}') ON DUPLICATE KEY UPDATE `last_did` = '${did}'");
}

function tlog($msg) {
	global $cfg, $uid;
	$file_name = $_SERVER['DOCUMENT_ROOT'] . "/logs/jabbo.log";
	file_put_contents($file_name, $_SERVER['REMOTE_ADDR'] . " " . date("Y.m.d H:i:s", time()) . " " . $_SERVER['SCRIPT_NAME'] . " " . $msg . "\n", FILE_APPEND);
}

function path_id_to_fullpath($id) {

    log_function_usage(__FUNCTION__);
    
	$result = mysql_query("SELECT CASE WHEN `parent` = '' THEN `root` ELSE CONCAT(`parent`, '/', `child`) END FROM `search_folders` WHERE `id` = '$id' LIMIT 1");

	if(mysql_num_rows($result) == 1)
		return mysql_result($result, 0, 0);
	else
		return false;

}

function log_function_usage($func) {

	global $link;
	mysql_query("INSERT INTO `jfs_log_functions` (`func`) VALUES ('${func}') ON DUPLICATE KEY UPDATE `times` = `times` + 1, `last` = NOW()");
    
}

function show_users_online() {

	global $link;
	
	$result = mysql_query("SELECT a.`login` FROM `search_users` a, `jfs_users_stats` b WHERE TIMESTAMPDIFF(MINUTE,b.`login_last`,NOW()) < 1 AND a.`uid` > 0 AND a.`uid` = b.`uid`");
	
	$users = array();
	
	while($row = mysql_fetch_assoc($result))
		$users[] = $row['login'];

	return implode(", ", $users);
	
}

function my_rawurlencode($a) {
    return str_replace('%2F', '/', rawurlencode($a));
}

function my_users_unique($action) {
    global $link, $uid;
    $ipl = ip2long($_SERVER['REMOTE_ADDR']);
    $query = mysql_query("SELECT `code` FROM `geo_ip` WHERE `r1` <= '${ipl}' AND `r2` >= '${ipl}' LIMIT 1");
    if(mysql_num_rows($query) == 1)
        $country = mysql_result($query, 0, 0);
    else
        $country = 'XX';

    mysql_query("INSERT INTO `jfs_users_unique` VALUES ('${_SERVER['REMOTE_ADDR']}', '${country}', '${uid['uid']}', '${action}', NOW(), 1) ON DUPLICATE KEY UPDATE `visits` = `visits` + 1, `date` = NOW()");
}

function get_file_class($ftype) {
	global $cfg;

	$ext = pathinfo($ftype, PATHINFO_EXTENSION);
	foreach($cfg['types'] as $tname => $type) {
		foreach($type as $ntype) {
			if(preg_match("/^${ntype}$/i", $ext))
				return $tname;
		}
	}
	return 'file';
}


?>