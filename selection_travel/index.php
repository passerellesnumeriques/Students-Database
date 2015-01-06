<?php
if (!isset($_SERVER["PATH_INFO"]) || strlen($_SERVER["PATH_INFO"]) == 0) $_SERVER["PATH_INFO"] = "/";
$path = substr($_SERVER["PATH_INFO"],1);
if (strpos($path, "..") !== FALSE) die("Access denied");
if (substr($path,0,12) == "server_comm/") {
	$_SERVER["PATH_INFO"] = substr($path,11);
	$sc_path = dirname(__FILE__)."/server_comm";
	set_include_path($sc_path);
	chdir($sc_path);
	include($sc_path."/index.php");
}
$sms_path = dirname(__FILE__)."/sms";
set_include_path($sms_path);
chdir($sms_path);
include($sms_path."/index.php");
?>