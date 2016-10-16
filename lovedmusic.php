<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/layout.inc.php';
require 'core/general.inc.php';
require 'core/common.php';

ae_detect_ie();

ob_start("ob_gzhandler");

$files = 0;

$page_contents_b = '';
$page_contents_a = page_banner();
$page_contents_a .= show_table_head();

$fmuser = isset($_GET['user']) ? $_GET['user'] : (isset($ufm['fmuser']) ? $ufm['fmuser'] : false);

if($fmuser) {

	$page_contents_b .= show_info($fmuser . "'s loved tracks on Last.fm");
	$page_contents_b .= show_directory_root();

	$url = "http://ws.audioscrobbler.com/2.0/user/${fmuser}/lovedtracks.xml?limit=1000";
	$arrXml = xml2array($url);

	foreach($arrXml['lovedtracks'] as &$value) {
		foreach($value as &$track) {
			$res = mysql_query(sprintf("SELECT * FROM `search_files` WHERE `audio_artist` = '%s' AND `audio_title` = '%s' LIMIT 1", mysql_real_escape_string($track['artist']['name']), mysql_real_escape_string($track['name'])));
			if(mysql_num_rows($res) == 1) {
				$row = mysql_fetch_assoc($res);
				$page_contents_b .= show_directory_item_file($row);
				$files ++;
			}
		}
	}
} else {

	$page_contents_b .= show_info("You are not connected to Last.fm");

}

$page_contents_b .= show_results('Total files found: <b>' . $files . '</b>');

$page_contents_c = show_table_footer();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$json_data['title'] = 'Loved tracks on Last.fm';
	$json_data['body'] = $page_contents_b;
	$json_data['reload'] = 0;
	$json_data['navmode'] = get_current_mode();
	echo json_encode($json_data);
} else {
	echo page_header("Loved tracks on last.fm");
	echo $page_contents_a . $page_contents_b . $page_contents_c;
	echo page_footer();
}

	
dbClose();

function xml2array($url, $get_attributes = 1, $priority = 'tag') {
    $contents = "";
    if (!function_exists('xml_parser_create')) {
        return array ();
    }
    $parser = xml_parser_create('');
    if (!($fp = @ fopen($url, 'rb'))) {
        return array ();
    }
    while (!feof($fp)) {
        $contents .= fread($fp, 8192);
    }
    fclose($fp);
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents), $xml_values);
    xml_parser_free($parser);
    if (!$xml_values)
        return; //Hmm...
    $xml_array = array ();
    $parents = array ();
    $opened_tags = array ();
    $arr = array ();
    $current = & $xml_array;
    $repeated_tag_index = array ();
    foreach ($xml_values as $data) {
        unset ($attributes, $value);
        extract($data);
        $result = array ();
        $attributes_data = array ();
        if (isset ($value))
        {
            if ($priority == 'tag')
                $result = $value;
            else
                $result['value'] = $value;
        }
        if (isset ($attributes) and $get_attributes)
        {
            foreach ($attributes as $attr => $val)
            {
                if ($priority == 'tag')
                    $attributes_data[$attr] = $val;
                else
                    $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
            }
        }
        if ($type == "open")
        {
            $parent[$level -1] = & $current;
            if (!is_array($current) or (!in_array($tag, array_keys($current))))
            {
                $current[$tag] = $result;
                if ($attributes_data)
                    $current[$tag . '_attr'] = $attributes_data;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                $current = & $current[$tag];
            }
            else
            {
                if (isset ($current[$tag][0]))
                {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else
                {
                    $current[$tag] = array (
                        $current[$tag],
                        $result
                    );
                    $repeated_tag_index[$tag . '_' . $level] = 2;
                    if (isset ($current[$tag . '_attr']))
                    {
                        $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                        unset ($current[$tag . '_attr']);
                    }
                }
                $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                $current = & $current[$tag][$last_item_index];
            }
        }
        elseif ($type == "complete")
        {
            if (!isset ($current[$tag]))
            {
                $current[$tag] = $result;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                if ($priority == 'tag' and $attributes_data)
                    $current[$tag . '_attr'] = $attributes_data;
            }
            else
            {
                if (isset ($current[$tag][0]) and is_array($current[$tag]))
                {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    if ($priority == 'tag' and $get_attributes and $attributes_data)
                    {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else
                {
                    $current[$tag] = array (
                        $current[$tag],
                        $result
                    );
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $get_attributes)
                    {
                        if (isset ($current[$tag . '_attr']))
                        {
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset ($current[$tag . '_attr']);
                        }
                        if ($attributes_data)
                        {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                    }
                    $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                }
            }
        }
        elseif ($type == 'close')
        {
            $current = & $parent[$level -1];
        }
    }
    return ($xml_array);
}

?>