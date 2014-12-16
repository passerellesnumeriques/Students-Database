<?php
header("Access-Control-Allow-Origin: *");
$path = substr($_SERVER["PATH_INFO"],1);
switch ($path) {
case "check_install":
	echo @file_get_contents(realpath(dirname(__FILE__))."/../sms/version");
	die();
case "download_database":
	include('download_database.php');
	die();
}
?>