<?php
header("Access-Control-Allow-Origin: *");
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