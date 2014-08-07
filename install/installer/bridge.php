<?php
include("deploy_utils.inc");
$url = $_POST["url"];
if (isset($_POST["unzip"])) {
	@unlink(realpath(dirname(__FILE__)."/../index.php"));
	try {
		unzipFile(realpath(dirname(__FILE__)."/".$url.".zip"), realpath(dirname(__FILE__)."/.."));
		unzipFile(realpath(dirname(__FILE__)."/".$url."_init_data.zip"), realpath(dirname(__FILE__)."/../data/init"));
	} catch (Exception $e) {
		header("HTTP/1.0 200 Error");
		die($e->getMessage());
	}
	die();
}
if (isset($_POST["getsize"])) {
	try {
		$size = getURLFileSize($url, "application/octet-stream");
		if ($size <= 0) {
			header("HTTP/1.0 200 Error");
			die("Unable to find the file on SourceForge");
		}
		die("".$size);
	} catch (Exception $e) {
		header("HTTP/1.0 200 Error");
		die($e->getMessage());
	}
}

try {
	$from = isset($_POST["range_from"]) ? intval($_POST["range_from"]) : null;
	$to = isset($_POST["range_to"]) ? intval($_POST["range_to"]) : null;
	$target = null;
	if (isset($_POST["target"])) $target = realpath(dirname(__FILE__))."/".$_POST["target"];
	$result = download($url, $target, $from, $to, true);
	if (!isset($_POST["target"]))
		die($result);
} catch (Exception $e) {
	header("HTTP/1.0 200 Error");
	die($e->getMessage());
}
?>