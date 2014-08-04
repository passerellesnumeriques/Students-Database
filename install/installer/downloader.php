<?php
if (isset($_POST["range_from"])) {
	if (file_exists($_POST["target"])) {
		$size = filesize($_POST["target"]);
		if (intval($_POST["range_to"]) < $size) die();
		if (intval($_POST["range_from"]) < $size) {
			$_POST["range_from"] = $size;
		}
	}
}
$url = $_POST["url"];
if (isset($_POST["unzip"])) {
	@unlink(realpath(dirname(__FILE__)."/../index.php"));
	if (class_exists("ZipArchive")) {
		$zip = new ZipArchive();
		$zip->open($url);
		$zip->extractTo(realpath(dirname(__FILE__)."/../"));
		$zip->close();
	} else {
		$output = array();
		$ret = 0;
		exec("/usr/bin/unzip \"".realpath(dirname(__FILE__))."/".$url."\" -d \"".realpath(dirname(__FILE__)."/../")."\"", $output, $ret);
		if ($ret <> 0) {
			header("HTTP/1.0 200 Error");
			echo "Error unzipping installer (".$ret.")";
			die();
		}
	}
	@unlink($url);
	die();
}
$c = curl_init($url);
curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
curl_setopt($c, CURLOPT_TIMEOUT, 25);
if (isset($_POST["getsize"])) {
	curl_setopt($c, CURLOPT_CUSTOMREQUEST, "HEAD");
	curl_setopt($c, CURLOPT_HEADER, TRUE);
} else if (isset($_POST["range_from"])) {
	curl_setopt($c, CURLOPT_HTTPHEADER, array(
		"Range: bytes=".$_POST["range_from"]."-".$_POST["range_to"]
	));
}
set_time_limit(240);
$result = curl_exec($c);
if ($result === false) {
	header("HTTP/1.0 200 Error");
	die(curl_error($c));
}
curl_close($c);
if (isset($_POST["getsize"])) {
	$lines = explode("\n",$result);
	$size = "-1";
	$type = "";
	foreach ($lines as $line) {
		$line = trim($line);
		$i = strpos($line, ":");
		if ($i === false) continue;
		$name = strtolower(trim(substr($line,0,$i)));
		if ($name == "content-length") { $size = trim(substr($line,$i+1)); continue; }
		if ($name == "content-type") { $type = strtolower(trim(substr($line,$i+1))); continue; }
	}
	$size = intval($size);
	if ($size > 0 && $type == "application/octet-stream")
		$result = $size;
	else {
		header("HTTP/1.0 200 Error");
		die("Unable to find the file on SourceForge");
	}
} else if (isset($_POST["range_from"])) {
	if ($_POST["range_from"] == "0") @unlink($_POST["target"]);
	$f = fopen($_POST["target"],"a");
	fwrite($f,$result);
	fclose($f);
	$result = "";
}
echo $result;
?>