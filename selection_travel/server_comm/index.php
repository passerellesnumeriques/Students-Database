<?php
// only allow local connections, as it is supposed to be accessed only locally by JavaScript
if ($_SERVER["SERVER_ADDR"] <> "127.0.0.1") die("Access denied");
if ($_SERVER["REMOTE_ADDR"] <> "127.0.0.1") die("Access denied");
// allow cross-domain
header("Access-Control-Allow-Origin: *");
// process the request
$path = substr($_SERVER["PATH_INFO"],1);
switch ($path) {
case "check_install":
	echo @file_get_contents(realpath(dirname(__FILE__))."/../sms/version");
	die();
case "check_synch":
	echo @file_get_contents(realpath(dirname(__FILE__))."/synch.uid");
	die();
case "update_sms":
	include("update_sms.php");
	die();
case "update_sms_progress":
	if (!file_exists("update_sms_progress")) die();
	readfile("update_sms_progress");
	die();
case "download_database":
	include('download_database.php');
	die();
case "download_progress":
	if (!file_exists("download_progress")) die();
	readfile("download_progress");
	die();
case "database_diff":
	include('database_diff.php');
	die();
case "database_diff_progress":
	if (!file_exists("database_diff_progress")) die();
	readfile("database_diff_progress");
	die();
}
?>