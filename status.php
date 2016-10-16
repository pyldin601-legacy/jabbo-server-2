<?php

require 'config.php';
require 'core/auth.inc.php';
require 'core/layout.inc.php';
require 'core/general.inc.php';
require 'core/common.php';

$timeout = time() + 5;

$data_hash = isset($_GET['hash']) ? $_GET['hash'] : md5('');
$new_hash = "";

$indexStats = get_index_stats();
$indexProg = get_indexing_progress();
$lastfmFrame = show_lastfm_frame();
$usersOnline = show_users_online();

$json_data['index'] = $indexStats;
$json_data['refresh'] = $indexProg;

    if($uid['uid'] != 0) {
        $json_data['playcount']  = $uid['play_count'];
        $json_data['downcount']  = $uid['down_count'];
        $json_data['watchcount'] = $uid['watch_count'];
        $json_data['lastfm'] = $lastfmFrame;
    }
    $new_hash = md5(json_encode($json_data));


$json_data['hash'] = $new_hash;
$output = json_encode($json_data);
echo $output;

dbClose();

?>