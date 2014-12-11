<?php
include("deploy_utils.inc");
if (isset($_POST["getmirrors"])) {
	try {
		$list = getMirrorsList(json_decode($_POST["provider_info"],true));
		if ($list == null) {
			header("HTTP/1.0 200 Error");
			die("Unable to get mirrors list");
		}
		echo json_encode($list);
		die();
	} catch (Exception $e) {
		header("HTTP/1.0 200 Error");
		die($e->getMessage());
	}
}

$url = $_POST["url"];
if (isset($_POST["unzip"])) {
	@unlink(realpath(dirname(__FILE__)."/../index.php"));
	try {
		unzipFile(realpath(dirname(__FILE__)."/".$url.".zip"), realpath(dirname(__FILE__)."/.."));
		unzipFile(realpath(dirname(__FILE__)."/".$url."_init_data.zip"), realpath(dirname(__FILE__)."/../data/init"));
		if (file_exists("conf/proxy"))
			copy(realpath("conf/proxy"), realpath(dirname(__FILE__)."/../conf/proxy"));
	} catch (Exception $e) {
		header("HTTP/1.0 200 Error");
		die($e->getMessage());
	}
	die();
}
if (isset($_POST["getsize"])) {
	try {
		$mirror_id = @$_POST["mirror_id"];
		$mirrors_provider = @$_POST["mirrors_provider"];
		$res = getURLFileSize($url, "application/octet-stream", $mirror_id, $mirrors_provider);
		$info = json_decode($res, true);
		if ($info == null || $info["size"] <= 0) {
			header("HTTP/1.0 200 Error");
			die("Unable to find the file on SourceForge".$size);
		}
		die($res);
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