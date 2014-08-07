<?php 
function download($url, $target = null, $from = null, $to = null, $resume_if_exists = true) {
	if ($target <> null && $from !== null) {
		if (file_exists($target)) {
			if ($resume_if_exists) {
				$size = filesize($target);
				if ($to < $size) {
					echo "cache";
					return;
				}
				if ($from < $size) $from = $size;
			} else {
				unlink($target);
			}
		}
	}
	
	$c = curl_init($url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($c, CURLOPT_TIMEOUT, 60);
	if ($from !== null) {
		curl_setopt($c, CURLOPT_HTTPHEADER, array(
		"Range: bytes=".$from."-".$to
		));
	}
	set_time_limit(240);
	$result = curl_exec($c);
	if ($result === false) {
		$error = curl_error($c);
		curl_close($c);
		throw new Exception($error);
	}
	curl_close($c);
	if ($target == null) return $result;
	$f = fopen($target,"a");
	fwrite($f,$result);
	fclose($f);
	return true;
}

function getURLFileSize($url, $expected_type = null) {
	$c = curl_init($url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($c, CURLOPT_TIMEOUT, 25);
	curl_setopt($c, CURLOPT_NOBODY, TRUE);
	curl_setopt($c, CURLOPT_CUSTOMREQUEST, "HEAD");
	curl_setopt($c, CURLOPT_HEADER, TRUE);
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
	if ($size > 0 && $expected_type <> null && $type == $expected_type)
		return $size;
	return -1;
}

function unzipFile($zip_path, $target_dir, $remove_after_unzip = false) {
	if (class_exists("ZipArchive")) {
		$zip = new ZipArchive();
		$zip->open($zip_path);
		$zip->extractTo($target_dir);
		$zip->close();
	} else {
		$output = array();
		$ret = 0;
		exec("/usr/bin/unzip \"".$zip_path."\" -d \"".$target_dir."\"", $output, $ret);
		if ($ret <> 0)
			throw new Exception("Error unzipping file ".$zip_path." (".$ret.")");
	}
	if ($remove_after_unzip)
		@unlink($url);
	return true;
}
?>