<?php
include("deploy_utils.inc");
$url = $_POST["url"];
if (isset($_POST["unzip"])) {
	@unlink(realpath(dirname(__FILE__)."/../index.php"));
	try {
		unzipFile(realpath(dirname(__FILE__))."/".$url, realpath(dirname(__FILE__)."/../"));
	} catch (Exception $e) {
		header("HTTP/1.0 200 Error");
		die($e->getMessage());
	}
	die();
}
if (isset($_POST["getsize"])) {
	$size = getURLFileSize($url, "application/octet-stream");
	if ($size <= 0) {
		header("HTTP/1.0 200 Error");
		die("Unable to find the file on SourceForge");
	}
	die("".$size);
}

try {
	$result = download($url, @$_POST["target"], @$_POST["range_from"], @$_POST["range_to"], true);
	if (!isset($_POST["target"]))
		die($result);
} catch (Exception $e) {
	header("HTTP/1.0 200 Error");
	die($e->getMessage());
}
?>