<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/layout.inc.php';
require 'core/general.inc.php';
require 'core/common.php';
require 'core/jabbo.inc.php';
require 'core/smartsearch.inc.php';
require 'core/links.inc.php';

ae_detect_ie();


$queryPlain = isset($_GET['q']) ? $_GET['q'] : '';

$needed = 100;

if($queryPlain) {
	$query = mysql_real_escape_string($queryPlain);
	$loc = isset($_GET['loc']) ? $_GET['loc'] : '';
	$from = isset($_GET['from']) ? (int)$_GET['from'] : 0;
	$files = isset($_GET['files']) ? (int)$_GET['files'] : 0;

	$dquery = detransliterate($query);
	
	jabbo_search_request($dquery);
	
	$queryDefault = "
        SELECT *, MATCH(tags) AGAINST('$dquery' IN BOOLEAN MODE) as relev
        FROM `search_files`
        WHERE MATCH(tags) AGAINST('$dquery' IN BOOLEAN MODE)
        ORDER BY `relev` DESC
        LIMIT 1001
    ";

	if(preg_match("/\@\w+\s.+/", $query)) {
		list($param, $subq) = explode(' ', $query, 2);
		switch(strtolower($param)) {
			case '@md5' 	: { $sql = "SELECT * FROM `search_files` WHERE `md5` = '$subq' limit 1001"; break; }
			case '@id' 		: { $sql = "SELECT * FROM `search_files` WHERE `index` = '$subq'"; break; }
			case '@song' 	: { $sql = "SELECT * FROM `search_files` WHERE `audio_title` LIKE '$subq' limit 1001"; break; }
			case '@artist' 	: { $sql = "SELECT * FROM `search_files` WHERE `audio_artist` LIKE '$subq' limit 1001"; break; }
			case '@band' 	: { $sql = "SELECT * FROM `search_files` WHERE `audio_band` LIKE '$subq' limit 1001"; break; }
			case '@album' 	: { $sql = "SELECT * FROM `search_files` WHERE `audio_album` LIKE '$subq' limit 1001"; break; }
			case '@genre' 	: { $sql = "SELECT * FROM `search_files` WHERE `audio_genre` = '$subq' limit 1001"; break; }
			case '@file' 	: { $sql = "SELECT * FROM `search_files` WHERE `filename` LIKE '$subq' limit 1001"; break; }
			case '@ext' 	: { $sql = "SELECT * FROM `search_files` WHERE `filetype` = '$subq' limit 1001"; break; }
			case '@path' 	: { $sql = "SELECT * FROM `search_files` WHERE `filepath` LIKE '%/$subq' limit 1001"; break; }
			case '@radio' 	: { $sql = "SELECT * FROM `search_files` WHERE `audio_genre` LIKE '$subq' ORDER BY RAND() limit 1001"; break; }
			default 		: { $sql = $queryDefault; break; }
		}
	} else {
		$sql = $queryDefault;
	}

	$tn = microtime(true);
	$result = mysql_query($sql);
	$dt = sprintf("%1.5f", microtime(true) - $tn);


	$count = mysql_num_rows($result);

	tlog("SEARCH QUERY=\"$queryPlain\" RESULTS=$count DELTA=$dt");

	if($from > 0) mysql_data_seek($result, $from);

}

$page_contents_b = "";

$page_contents_a = page_banner($queryPlain);
$page_contents_a .= show_table_head();

if(mysql_num_rows($result) && $from == 0) {
	//$page_contents_b .= show_table_menu();
	$page_contents_b .= show_info("Search results for <b>" . htmlspecialchars($queryPlain, ENT_QUOTES) . "</b> (<b>" . (mysql_num_rows($result) <= 1000 ? (string)mysql_num_rows($result) : "1000+") . "</b> res/<b>$dt</b> sec)");
}
    

while (($row = mysql_fetch_assoc($result)) && $needed > 0) {
	$from ++;
	if(md5($row['filepath']) != $loc) {
		$page_contents_b .= show_directory_loc($row['filepath']);
		$loc = md5($row['filepath']);
	}
	$needed --;
	$files ++;
	$page_contents_b .= show_directory_item_file($row);
}

if($count > $from && $count > 0) 
{
	$page_contents_b .= show_search_next($from, $queryPlain, $loc, $files);
} 
else 
{
	if($count == 0) $page_contents_b = show_info("No results for your request <b>" . htmlspecialchars($queryPlain, ENT_QUOTES) . "</b> found!");
	$page_contents_b .= show_results('Total files found: <b>' . $files . '</b>');
}

$page_contents_c = show_table_footer();

//show_directory_summary($files, $sum_size, $dirid);
if($_SERVER['REQUEST_METHOD'] == 'POST') {

	$json_data['title'] = urldecode(html_entity_decode("Results for '" . $queryPlain . "' ($dt sec) @ Jabbo File Server"));
	if(isset($_GET['add'])) {
		$json_data['append'] = $page_contents_b;
	} else {
		$json_data['body'] = $page_contents_b;
		if(empty($_POST['smart']) || $_POST['smart'] != "1") {
			$json_data['query'] = $queryPlain;
			increment_phrase($queryPlain);
		}
	}
	$json_data['reload'] = 0;
	$json_data['navmode'] = get_current_mode();
	echo json_encode($json_data);
} else {
	echo page_header("Results for '" . $queryPlain . "' ($dt sec)");
	echo $page_contents_a . $page_contents_b . $page_contents_c;
	echo page_footer();
}

increment_phrase($queryPlain);

my_users_unique("search");
dbClose();

function detransliterate($input) {
    $input = mb_strtolower($input,'utf-8');
    $replace =  array('а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
                    'е' => 'e', 'ё' => 'yo', 'ж' => 'j', 'з' => 'z', 'и' => 'i',
                    'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
                    'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
                    'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
                    'ш' => 'sh', 'щ' => 'sch', 'ь' =>  '', 'ы' => 'y', 'ъ' => '',
                    'э' => 'e', 'ю' => 'yu', 'я' => 'ya');

    foreach ($replace as $key=>$val) {
        $input = str_replace($key, $val, $input);
    }
    return $input;
}

?>