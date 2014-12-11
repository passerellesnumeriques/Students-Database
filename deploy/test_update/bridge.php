<?php
$type = $_GET["type"];
if ($type == "get_version") {
	$url = $_POST["url"];
	$c = curl_init("http://".$url."/dynamic/application/service/get_backup");
	curl_setopt($c, CURLOPT_HEADER, FALSE);
	curl_setopt($c, CURLOPT_POST, TRUE);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_POSTFIELDS, array());
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($c, CURLOPT_TIMEOUT, 25);
	curl_setopt($c, CURLOPT_HEADER, TRUE);
	$result = curl_exec($c);
	$lines = explode("\n",$result);
	foreach ($lines as $line) {
		$line = trim($line);
		if (strtolower(substr($line,0,19)) == "pn_version_changed:") {
			echo trim(substr($line,19));
			return;
		}
	}
	header("HTTP/1.0 400 Error");
	return;
}
if ($type == "get_list") {
	$url = $_POST["url"];
	$pass = $_POST["password"];
	$request = $_POST["request"];
	$version = $_POST["version"];
	$post = array("request"=>$request,"password"=>$pass);
	
	$c = curl_init("http://".$url."/dynamic/application/service/get_backup");
	curl_setopt($c, CURLOPT_HEADER, FALSE);
	curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=".$version));
	curl_setopt($c, CURLOPT_POST, TRUE);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_POSTFIELDS, $post);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($c, CURLOPT_TIMEOUT, 25);
	$result = curl_exec($c);
	$code = curl_getinfo($c, CURLINFO_HTTP_CODE);
	if ($code <> 200) {
		header("HTTP/1.0 ".$code." Error");
		return;
	}
	echo $result;
	return;
}
if ($type == "backup") {
	$file = $_GET["file"];
	$file_version = $_GET["file_version"];
	$file_time = $_GET["file_time"];
	$version = $_GET["version"];
	$url = $_POST["url"];
	$i = strpos($url, "*pass=");
	$pass = substr($url,$i+6);
	$url = substr($url,0,$i);
	
	if (isset($_POST["getsize"])) {
		$c = curl_init("http://".$url."/dynamic/application/service/get_backup?getsize=true");
		curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=".$version));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 25);
		curl_setopt($c, CURLOPT_HEADER, TRUE);
		curl_setopt($c, CURLOPT_POSTFIELDS, array("request"=>"get_backup","password"=>$pass,"file"=>$file,"version"=>$file_version,"time"=>$file_time));
		set_time_limit(240);
		$result = curl_exec($c);
		if ($result === false) {
			$error = curl_error($c);
			curl_close($c);
			throw new Exception($error);
		}
		curl_close($c);
		$lines = explode("\n",$result);
		$size = "-1";
		foreach ($lines as $line) {
			$line = trim($line);
			$i = strpos($line, ":");
			if ($i === false) continue;
			$name = strtolower(trim(substr($line,0,$i)));
			if ($name == "file-size") { $size = trim(substr($line,$i+1)); continue; }
		}
		$size = intval($size);
		if ($size > 0) {
			echo $size."/bytes";
			return;
		}
		echo -1;
		return;
	}

	if (!file_exists("download")) mkdir("download");
	$target = realpath("download")."/".$file.".zip";
	if (file_exists($target)) {
		$size = filesize($target);
		if ($to < $size) {
			echo "cache:$size";
			return;
		}
		if ($from < $size) $from = $size;
	}
	
	$c = curl_init("http://".$url."/dynamic/application/service/get_backup");
	curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=".$version));
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($c, CURLOPT_POST, TRUE);
	curl_setopt($c, CURLOPT_POSTFIELDS, array("request"=>"get_backup","password"=>$pass,"file"=>$file,"version"=>$file_version,"time"=>$file_time));
	$timeout = 1200;
	if ($from <> null) {
		$size = intval($to)-intval($from);
		if ($size >= 4*1024*1024) $timeout = 240;
		else if ($size >= 2*1024*1024) $timeout = 210;
		else if ($size >= 1*1024*1024) $timeout = 180;
		if ($size >= 512*1024) $timeout = 1500;
		set_time_limit($timeout + 60);
	} else
		set_time_limit(240);
	curl_setopt($c, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($c, CURLOPT_HTTPHEADER, array(
		"Range: bytes=".$from."-".$to
	));
	$result = curl_exec($c);
	if ($result === false) {
		$error = curl_error($c)." (#".curl_errno($c).")";
		curl_close($c);
		throw new Exception($error);
	}
	curl_close($c);
	$f = fopen($target,"a");
	fwrite($f,$result);
	fclose($f);
	return true;
}
if ($type == "software") {
	$version = $_GET["version"];
	$urls = file_get_contents("../../www/conf/update_urls");
	$channel = file_get_contents("../../www/conf/channel");
	$urls = str_replace("##CHANNEL##",$channel,$urls);
	$urls = explode("\n",$urls);
	$update_url = null;
	foreach ($urls as $url) if (substr($url,0,7) == "update=") { $update_url = substr($url,7); break; }
	if ($_POST["url"] == "software")
		$url = str_replace("##FILE##","Students_Management_Software_".$version.".zip",$update_url);
	else
		$url = str_replace("##FILE##","Students_Management_Software_".$version."_init_data.zip",$update_url);
	require_once("../../www/component/application/service/deploy_utils.inc");
	if (isset($_POST["getsize"])) {
		try {
			$res = getURLFileSize($url, "application/octet-stream");
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
		download($url, realpath("download")."/".$_POST["url"].".zip", $_POST["range_from"], $_POST["range_to"]);
	} catch (Exception $e) {
		header("HTTP/1.0 200 Error");
		die($e->getMessage());
	}
	return;
}
?>