<?php

if($url = 'http://' . $_GET['url']) {
    echo file_get_contents($url);
}

?>