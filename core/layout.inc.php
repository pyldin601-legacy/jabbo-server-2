<?php

/***************
| PAGE HEADERS |
***************/
function page_header($caption) {

global $uid, $ismobile;
	
$decodedTitle = urldecode(html_entity_decode($caption));

$css = show_css();

$tmp_pg = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">';
	
$tmp_pg .= 

<<<HEADER

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta name="viewport" content="initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=yes" />
<meta name="MobileOptimized" content="176" />
<meta name="description" content="Jabbo File Server">
<title>${decodedTitle} @ Jabbo File Server</title>

${css}

<link rel="icon" type="image/png" href="/images/search.png">

<script src="/js/jquery-1.7.1.min.js"></script>
<script src="/js/jquery-ui-1.8.17.custom.min.js.gz"></script>
<script src="/js/jquery.mousewheel.js.gz" type="text/javascript"></script>
<script src="/js/jquery.textchange.js.gz"></script>
<script src="/player/jquery.jplayer.min.js"></script>
	
<script src="/js/variables.js"></script>
<script src="/js/jabbo.js"></script>
<script src="/js/common.js"></script>
<script src="/player/player.js"></script>
<script src="/player/myajax.js"></script>
<script src="/player/playlist.js"></script>
<script src="/player/scrobble.js"></script>
<script src="/js/ajax.js"></script>
<script src="/js/playlist.js"></script>
<script src="/js/tooltip.js"></script>
<script src="/js/isearch.js"></script>
<script src="/js/popupfolder.js"></script>
<script type="text/javascript">var uid=${uid['uid']}</script>
<meta name="google-site-verification" content="1j5kM6k9ZLXz2ODst34rzy3AHaKLZadBm4zOeWYzduk" />

<script type="text/javascript">
var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-34158137-1']);
_gaq.push(['_trackPageview']);
(function() {
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
</script>

<link id="favicon" rel="icon" type="image/png" href="/images/favicon/jabbo-icon.png">
</head>
	
<body>
<div class="up-back"><img style="vertical-align:middle" src="/images/icons/icon_arrow_up.png">Up</div>
<div class="bg-image"></div>
<div class="ajax-busy"></div>
<div class="shadow popupMenu" onblur="hideMenu();"></div>
<div id="jquery_jplayer_1" class="jp-jplayer"></div>
<div class="tool-tip"></div>
<div class="login-frame fx-blur"></div>
<div class="body-warp contents shadow">
HEADER;
	
return $tmp_pg;
}

function show_expand($array) {

global $uid;
	
$href = '/dir-exp-' . $array['id'] . "-" . rawurlencode($array['name']);
$tooltip = "/dirinfo.php?id=" . $array['id'];

$tmp_pg = '
<tr class="ri">
	<td class="ac">
		<div class="file-icon icon-folder"></div>
	</td>
	<td class="al directory-item" tipurl="'.$tooltip.'">
		<a class="blink ajlink" dir="' . $array['id'] . '" href="' . $href . '">Show all files</a>
	</td>
	<td class="ac"></td>
</tr>
';
	
return $tmp_pg;

}

function show_collapse($array) {

global $uid;
	
$href = '/dir-' . $array['id'] . "-" . rawurlencode($array['name']);
$tooltip = "/dirinfo.php?id=" . $array['id'];

$tmp_pg = '
<tr class="ri">
	<td class="ac">
		<div class="file-icon icon-folder"></div>
	</td>
	<td class="al directory-item" tipurl="'.$tooltip.'">
		<a class="blink ajlink" dir="' . $array['id'] . '" href="' . $href . '">Back to folders</a>
	</td>
	<td class="ac"></td>
</tr>
';
	
return $tmp_pg;

}

function show_css() {

	global $ismobile;
	
	$sm = "";

	if($ismobile) {
		$sm .= '<link href="/css/fonts_m.css" rel="stylesheet" type="text/css">';
		$sm .= '<link href="/jabbo_m.css" rel="stylesheet" type="text/css">';
	} else {
		$sm .= '<link href="/css/fonts.css" rel="stylesheet" type="text/css">';
		$sm .= '<link href="/jabbo.css" rel="stylesheet" type="text/css">';
	}

	$sm .= '<link href="/css/common.css" rel="stylesheet" type="text/css">';
	$sm .= '<link href="/css/tooltip.css" rel="stylesheet" type="text/css">';
	$sm .= '<link href="/css/search.css" rel="stylesheet" type="text/css">';
	$sm .= '<link href="/css/aplayer.css" rel="stylesheet" type="text/css">';
	$sm .= '<link href="/css/playlist.css" rel="stylesheet" type="text/css">';
	$sm .= '<link href="/css/login.css" rel="stylesheet" type="text/css">';
	$sm .= '<link href="/css/popupfolder.css" rel="stylesheet" type="text/css">';

	return $sm;
	
}

function query_mini($query) {
	$lay = '<form class="query" action="/search.php" onsubmit="search_this(); return false;">
	<div class="icon-engine" tooltip="Добавить поисковую систему в браузер"></div>
	<input placeholder="Search music, videos and more..." class="subject" name="q" id="query_query" value="' . htmlspecialchars($query, ENT_QUOTES) . '" autocomplete="off" required>
	<input class="submit" type="submit" tooltip="Search" value=""></form>';
	return $lay;
} //		<div class="icon-help" tooltip="Инструкция"></div>


function get_current_mode() {
	return preg_match("/\/dir\-\d+/", $_SERVER['REQUEST_URI']) ? "Navigation mode" : "Search results";
}

function page_banner($query) {
	global $ismobile, $cfg;
	
	$wr_query = query_mini($query);
	$wr_icons = show_auth();

if(!$ismobile) {
$href = $cfg['link']['home'];
$wr_menus = <<<MENUS
<a class="ajlink" href="$href">
	<span class="j_title">Jabbo File Server 2.2</span>
</a>
MENUS;
} else {
	$wr_menus = "";
}

$wr_auth = show_auth_new();

$ip = $_SERVER['REMOTE_ADDR'];

$i_am = get_current_mode();
$i_stat = get_index_stats();

$tmp_pg = <<<HEADER
<div class="header"><div class="inheader">
<div class="menu-panel">
	<div class="fl menu-panel-items">$wr_menus</div>
	<div class="fr menu-panel-auth">$wr_auth</div>
</div>
<table class="htable">
	<tr>
		<td class="ll al index-stats-td">
			<span class="index-stats">$i_stat</span>
		</td>
		<td class="cc player-frame">
			<div class="fl_player corner"></div>
			<div class="playlist-wrap fx-blur">
				<div class="pl-corner"></div>
				<div class="pl-title"><span class="pl-t-text">Playlist</span><div tooltip="Return to playlist location" class="undo like-a" onclick="return_playlist()"></div></div>
				<div class="pl-contents"><div class="pl-contents-inner"></div></div>
				<!--<div class="pl-loc"></div>-->
				<div class="pl-footer"></div>
			</div>
		</td>
		<td class="rr">$wr_query<div class="your-ip">Your IP: $ip</div></td>
	</tr>
</table>
</div></div>
HEADER;

return $tmp_pg;
	
}

function get_index_stats() 
{

	$files = mysql_result(mysql_query("SELECT `table_rows` FROM information_schema.tables  WHERE `table_schema` = 'search' AND `table_name` = 'search_files'"), 0, 0);

	$db_delta = mysql_result(mysql_query("SELECT TIME_TO_SEC(TIMEDIFF(NOW(), `value`)) FROM `triggers` WHERE `parameter` = 'files_update'"), 0, 0);
	if($db_delta > 30) {
		$bytes = mysql_result(mysql_query("SELECT SUM(filesize) FROM search_files"), 0, 0);
		$res = 'Indexed <b>'.my_count_to_human($files).'</b> files (<b>' . my_bytes_to_human($bytes) . '</b>)';
	} else {
		$res = 'Indexing in progress...';
	}
	
	$res .= "<br>Users online: <b>" . show_users_online() . "</b>";
	 
	return $res;
}

function get_indexing_progress()
{
	$db_delta = mysql_result(mysql_query("SELECT TIME_TO_SEC(TIMEDIFF(NOW(), `value`)) FROM `triggers` WHERE `parameter` = 'files_update'"), 0, 0);
	return ($db_delta < 30) ? 1 : 0;
}

function page_footer() {
	global $ismobile;
	$tmp_pg = '';
	$tmp_pg .= '</div>';
	//if(!$ismobile) $tmp_pg .= '<div class="about-frame fx-round"></div>';
	$tmp_pg .= '<!-- Developed by Roman Gemini -->';
	$tmp_pg .= '</body>';
	$tmp_pg .= '</html>';
	return $tmp_pg;
}

/********************
| NAVIGATOR SECTION |
********************/
function show_table_head() {
	$tmp_pg = '';
	$tmp_pg .= '<div class="container">';
	$tmp_pg .= '<table class="results corner">';
	$tmp_pg .= '<colgroup>';
	$tmp_pg .= '<col style="width:40px; background:#eee;" />';
	$tmp_pg .= '<col style="width:auto; background:#eee;" />';
	$tmp_pg .= '<col style="width:24px; background:#ddd;" />';
	$tmp_pg .= '</colgroup>';
	$tmp_pg .= '<tbody>';
	return $tmp_pg;
}

function show_table_menu() {
	$tmp_pg = '<tr class="results_menu">';
	$tmp_pg .= '<th class="al" colspan="3">';
	$tmp_pg .= '<a class="like-a" tooltip="Shuffle files" onclick="shuffle_files()">Shuffle</a>';
	$tmp_pg .= '</th>';
	$tmp_pg .= '</tr>';
	return $tmp_pg;
}

function show_table_footer() {
	$tmp_pg = '';
	$tmp_pg .= '</tbody>';
	$tmp_pg .= '</table>';
	$tmp_pg .= '</div>';
	return $tmp_pg;
}


function show_directory_head($spath, $id, $allow) {
	global $uid;

	$disp = pathIdToName($id);
	
	$url = '/zip-' . $id . '-' . $uid['session'] . '/' . htmlspecialchars($disp) . '.zip';
	$urls = '/filelist-' . $uid['session'] . '/' . $id . '.urls';
	$m3u = '/playlist-' . $uid['session'] . '/' . $id . '.m3u8';
	$trk = '/tracklist.php?id=' . $id;
	
	$tmp_pg = '';
	$tmp_pg .= '<tr class="location">';
	$tmp_pg .= '
	<th class="al" colspan="3">
		<div class="fr">
			<a tooltip="Download contents of this folder in .ZIP archive" class="'.($allow ? '' : 'deny').'" href="'.$url.'">.zip</a>
			| <a tooltip="Download file list in .urls format" class="'.($allow ? '' : 'deny').'" href="'.$urls.'">.urls</a>
			| <a tooltip="Download playlist in .m3u format" class="'.($allow ? '' : 'deny').'" href="'.$m3u.'">.m3u8</a>
			| <a target="_blank" tooltip="Show tracks list" class="'.($allow ? '' : 'deny').'" href="'.$trk.'">tl</a>
		</div>
	' . path_parse($spath) . '</th>';
	$tmp_pg .= '</tr>';
	return $tmp_pg;
}

function show_directory_root() {
	global $uid, $cfg;
	$tmp_pg = '';
	$tmp_pg .= '<tr class="location">';
	$tmp_pg .= '<th class="al" colspan="3"> <nobr>&raquo; <a class="ajlink " dir="0" href="'.$cfg['link']['home'].'" tipurl="/diskinfo.php">Home</a></nobr></td>';
	$tmp_pg .= '</tr>';
	return $tmp_pg;
}


function show_directory_loc($spath) {
	global $uid;
	$tmp_pg = '';
	$tmp_pg .= '<tr class="location subloc">';
	$tmp_pg .= '<th class="al" colspan="3">' . path_parse($spath) . '</th>';
	$tmp_pg .= '</tr>';
	return $tmp_pg;
}


function show_directory_summary($files, $size, $directory) {
	$tmp_pg = '';
	$tmp_pg .= '<tr class="gradient">';
	$tmp_pg .= '<td class="ac sum" colspan="3">';
	$tmp_pg .= 'Folder contains <b>' . $files . '</b> files ';
	$tmp_pg .= '(<b>' . my_bytes_to_human($size) . '</b>)';

	if($directory) 
		$tmp_pg .= ', recursive size: <b><span class="like-a" onclick="folderSize(this, '.$directory.')">calculate</span></b>';

		$tmp_pg .= '</td>';
	$tmp_pg .= '</tr>';
	return $tmp_pg;
}

function show_results($text) {
	$tmp_pg = "<tr class=\"gradient\"><td class=\"ac sum\" colspan=\"3\">${text}</td></tr>";
	return $tmp_pg;
}

function show_directory_return($ret) {
	global $uid, $cfg;
	$name = '..';
	$href = link_nav_url($ret['id'], $ret['name']);
	$tmp_pg = "<tr class=\"ri\"><td class=\"ac\"><div class=\"file-icon icon-folder\"></div></td><td class=\"al directory-item\"><a class=\"ajx blink\" href=\"${href}\">${name}</a></td><td class=\"ac\"></td></tr>\n";
	return $tmp_pg;
}

function show_directory_return_root() {
	global $uid, $cfg;
	$name = '..';
	$href = $cfg['link_home'];
	$tmp_pg = "<tr class=\"ri\"><td class=\"ac\"><div class=\"file-icon icon-folder\"></div></td><td class=\"al directory-item\"><a class=\"ajx blink\" href=\"${href}\">${name}</a></td><td class=\"ac\"></td></tr>";
	return $tmp_pg;
}


function show_directory_item_dir($array) {

global $uid;
	
$name = $array['child'];

$mtime = strtotime($array['mtime']);
$href = link_nav_url($array['id'], $array['child']);
$accessed = mysql_result(mysql_query("SELECT IFNULL(SUM(`opened`), 0) FROM `jfs_dir_stats` WHERE `id` = ${array['id']}"), 0, 0);
$metainfo = human_date($array['mtime']) . ($accessed != 0 ? ", accessed <b>$accessed</b> times" : "");
$tooltip = "/dirinfo.php?id=" . $array['id'];

$bookmarked = mysql_num_rows(mysql_query("SELECT * FROM `jfs_folder_bookmark` WHERE `user_id` = ${uid['uid']} AND `folder_id` = ${array['id']}"));

$tmp_pg = '
<tr class="result-item ri">
	<td class="ac">
		<div class="file-icon icon-folder"></div>
	</td>
	<td class="al directory-item" tipurl="'.$tooltip.'">
		<a class="blink ajx dmenu" bm="'.$bookmarked.'" session="'.$uid['session'].'" dir="' . $array['id'] . '" href="' . $href . '"><div class="fl">' . $name . '</div><div class="bmicon' . ($bookmarked?' on':'').'"></div></a>
	</td>
	<td class="ac"></td>
</tr>
';
	
return $tmp_pg;

}

function layout_get_metainfo($array) {
	
	$metainfo = human_date($array['filemtime']) . ", " . human_size($array['filesize']) . ", " . strtoupper($array['filetype']);
	$fileclass = $array['filegroup'] ? $array['filegroup'] : 'file';

	if($fileclass == 'audio')
		$metainfo .= ', '  . seconds_to_time($array['avg_duration']/1000) . ', ' . (int)($array['avg_bitrate']/1000) . "kbps";
	else if($fileclass == 'image')
		$metainfo .= ', ' . $array['video_dimension'];
	else if($fileclass == 'video')
		$metainfo .= ', '  . seconds_to_time($array['avg_duration']/1000) . ', ' . (int)($array['avg_bitrate']/1000) . "kbps, " . $array['video_dimension'];
	
	if(isset($array['relev']))	$metainfo .= ', R:' . $array['relev'];
	if(isset($array['maximum']))	$metainfo .= ', M:' . $array['maximum'];

	return $metainfo;
	
}

function show_directory_item_file($array) {

	global $sum_size, $files, $filesbytype, $cfg, $uid, $ismobile, $ufm;
	$tmp_pg = '';
	$cover = '';
	
	
	// define variables;
	$fileclass = get_file_class($array['filename']);
	$metainfo = layout_get_metainfo($array);
    
	$href = jabbo_download_link($array);
    
	$tooltip = "/fileinfo.php?id=" . $array['index'];
	$denyclass = is_allowed($array['filepath'], $uid['uid']) ? '' : 'deny';

    $itunes = 0;

	if($fileclass == 'audio') {
		if(strlen($array['audio_artist']) > 0 and strlen($array['audio_title']) > 0) {
			$h_tip = $array['filename']; // ????
			$h_info = '<b>' . $array['audio_artist'] . '</b> - ' . $array['audio_title'] . ' [' . seconds_to_time($array['avg_duration']/1000) . ']';
		} else {
			$h_tip = $array['filename'];
			$h_info = $array['filename'] . ' [' . seconds_to_time($array['avg_duration']/1000) . ']';
		}
	} elseif($fileclass == 'image') {
		$h_tip = $array['filename'];
		$h_info = $array['filename'];
		$cover = 'style="background:url(/audiocover.php?id=' . $array['index'] . '&s=42) no-repeat center center"';
	} elseif($fileclass == 'video') {
		$h_tip = $array['filename'];
		$h_info = $array['filename'];

		$dim = explode('x', $array['video_dimension']);
		foreach($dim as &$val) $val = (int) $val;
		
		if($dim[0] > 0 && $dim[1] > 0)
			$aspect = $dim[1] / $dim[0];
		else
			$aspect = 0.56;

		$watched = (int) mysql_result(mysql_query(sprintf("select IFNULL(SUM(`pos`), 0) from `videoposition` where `uid` = '%d' and `index` = '%d'", $uid['uid'], $array['index'])), 0);
		$played_percent = (int)(100 / ($array['avg_duration'] / 1000) * $watched);

	} else {
		$h_tip = $array['filename'];
		$h_info = $array['filename'];
	}

	$sum_size += $array['filesize'];

	if(isset($filesbytype[$fileclass]))
		$filesbytype[$fileclass] ++;
	else
		$filesbytype[$fileclass] = 1;

	$tmp_pg .= '<tr class="result-item ri">';

	// icon
	$tmp_pg .= '<td '.$cover.' class="ac"><div class="file-icon icon-' . $fileclass . '" id="icon-' . $array['index'] . '"></div></td>';
			
	// shortcut
	$tmp_pg .= '<td class="al directory-item" id="file-' . $array['index'] . '">';
	$tmp_pg .= '<div class="fl noflow" tipurl="' . $tooltip . '">';
    $tmp_pg .= '<a class="' . $denyclass . ' blink cmenu" grp="'. $fileclass . '" id="link-' . $array['index'] . '" target="_blank" href="' . $href . '">' . $h_info;
	$tmp_pg .= '</a></div>';

	if($denyclass == '') {
		if($fileclass == 'audio')
			$tmp_pg .= '<input type="hidden" class="play-file" id="input-' . $array['index'] . '" value="' . $array['index'] . '" file="' . $array['filename'] . '" arti="' . $array['audio_artist'] . '" titl="' . $array['audio_title'] . '" durt="' . (int)($array['avg_duration']/1000) . '" md5="' . $array['md5'] . '">';
		if($fileclass == 'image')
			$tmp_pg .= '<input type="hidden" class="image-file" id="input-' . $array['index'] . '" value="' . $array['index'] . '" file="' . $array['filename'] . '">';
		else
			$tmp_pg .= '<input type="hidden" class="default-file" id="input-' . $array['index'] . '" value="' . $array['index'] . '" file="' . $array['filename'] . '" md5="' . $array['md5'] . '">';

		if($array['encoded'] == '1' || $array['filetype'] == 'flv') {
			$tmp_pg .= '<div class="fr" tooltip="Play video" style="width:100px;margin-top:3px">';
			$tmp_pg .= '<div class="sublink vc fr ac" onclick="';
			$tmp_pg .= 'watchVideo('.$array['index'].', \'' . get_challenge($array['index']) . '\');';
			$tmp_pg .= '"><div class="watched" id="watch-' . $array['index'] . '" style="width:' . $played_percent . '%"></div>watch online</div></div>';
		}
	}

	$tmp_pg .= '</td>';

	$tmp_pg .= '<td class="ar">';

	// audio dl
	if($fileclass == 'audio' && $denyclass == '' && strtolower($array['filetype']) != 'mp3') {
		$audioref = '/mp3-' . $array['index'] . '-' . $uid['session'] . '/' . rawurlencode(replace_extension($array['filename'], 'mp3'));
		$mp3Icon = "/images/icons/download_mp3.png";
		$tmp_pg .= "<a class=\"$denyclass\" target=\"_blank\" href=\"$audioref\" tooltip=\"Download encoded in MP3\"><img src=\"$mp3Icon\"></a>&nbsp;";
	}
		
	// dl
	$downIcon = ($denyclass == '') ? "/images/icons/download.png" : "/images/icons/lock.png";

	$tmp_pg .= "<a class=\"$denyclass\" target=\"_blank\" href=\"$href\" tooltip=\"Download file\"><img src=\"$downIcon\"></a>";

	$tmp_pg .= "</td>";

	$tmp_pg .= '</tr>';
	
	return $tmp_pg;

}

function show_next_page($page, $prev) {

	global $files;

	$prev = my_rawurlencode($prev);

	$tmp_pg = '';
	$tmp_pg .= '<tr class="cut_here">';
	$tmp_pg .= '<td class="ac" colspan="3"><div class="nav_next_page" prev="'.$prev.'" page="'.$page.'"><img src="/images/loader.gif"></div></td>';
	$tmp_pg .= '</tr>';

	return $tmp_pg;

}

function show_search_next($from, $query, $loc, $files) {
	global $files;
	$tmp_pg = '';
	$tmp_pg .= '<tr' . (($files % 2 != 1) ? ' class="cut_here"' : ' class="cut_here"') . '>';
	$tmp_pg .= '<td class="ac" colspan="3"><div class="next_page" from="' . $from . '" subj="' . htmlspecialchars($query) . '" loc="' . htmlspecialchars($loc) . '" files="' . $files . '"><img src="/images/loader.gif"></div></td>';
	$tmp_pg .= '</tr>';
	return $tmp_pg;
}

function show_info($info) {
	$tmp_pg = '';
	$tmp_pg .= '<tr class="info">';
	$tmp_pg .= '<td class="ac inform" colspan="3">' . $info . '</td>';
	$tmp_pg .= '</tr>';
	return $tmp_pg;
}

function show_warning($info) {
	$tmp_pg = '';
	$tmp_pg .= '<tr class="info">';
	$tmp_pg .= '<td class="ac inform red" colspan="3">' . $info . '</td>';
	$tmp_pg .= '</tr>';
	return $tmp_pg;
}

function show_alpha($info, $style = 'ac') {
	$tmp_pg = '';
	$tmp_pg .= '<tr class="location">';
	$tmp_pg .= '<td class="'.$style.'" colspan="3"><b>' . $info . '</b></td>';
	$tmp_pg .= '</tr>';
	return $tmp_pg;
}


function show_sub_head($info) {
	$tmp_pg = '';
	$tmp_pg .= '<tr class="subresult">';
	$tmp_pg .= '<td class="ac" colspan="3">' . $info . '</td>';
	$tmp_pg .= '</tr>';
	return $tmp_pg;
}

function show_title($text) {

	return "<tr class=\"subinfo\"><td class=\"ac\" colspan=\"3\">$text</td></tr>";

}

function show_title_g($text) {

	return "<tr class=\"gradient\"><td class=\"ac\" colspan=\"3\">$text</td></tr>";

}

?>