<?php

include('config.php');
session_start();

unset($_SESSION['jfs_login'], $_SESSION['jfs_password']);
session_write_close();
header("Location: /");

?>

