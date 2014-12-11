<?php 
function download($url, $target = null, $from = null, $to = null, $resume_if_exists = true) {
	if ($target <> null && $from !== null) {
		if (file_exists($target)) {
			if ($resume_if_exists) {
				$size = filesize($target);
				if ($to < $size) {
					echo "cache:$size";
					return;
				}
				if ($from < $size) $from = $size;
			} else {
				unlink($target);
			}
		}
	}
	
	$c = curl_init($url);
	if (file_exists("conf/proxy")) include("conf/proxy");
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
	$timeout = 200;
	if ($from <> null) {
		$size = intval($to)-intval($from);
		if ($size >= 4*1024*1024) $timeout = 240;
		else if ($size >= 2*1024*1024) $timeout = 200;
		else if ($size >= 1*1024*1024) $timeout = 150;
		else if ($size >= 512*1024) $timeout = 60;
		else $timeout = 45;
		set_time_limit($timeout + 60);
	} else
		set_time_limit(240);
	curl_setopt($c, CURLOPT_TIMEOUT, $timeout);
	$headers = array(
		//"Connection: close"
	);
	if ($from !== null)
		array_push($headers, "Range: bytes=".$from."-".$to);
	curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
	$result = curl_exec($c);
	if ($result === false) {
		$error = curl_error($c)." (#".curl_errno($c).")";
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

function getURLFileSize($url, $expected_type = null, $mirror_id = null, $mirrors_provider = null) {
	if ($mirror_id <> null && $mirrors_provider <> null) {
		$provider = json_decode($mirrors_provider, true);
		if ($provider <> null)
			$url = getMirrorURL($url, $provider, $mirror_id);
	}
	$c = curl_init($url);
	if (file_exists("conf/proxy")) include("conf/proxy");
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
	$accept_ranges = "";
	$last_location = "";
	foreach ($lines as $line) {
		$line = trim($line);
		$i = strpos($line, ":");
		if ($i === false) continue;
		$name = strtolower(trim(substr($line,0,$i)));
		if ($name == "content-length") { $size = trim(substr($line,$i+1)); continue; }
		if ($name == "content-type") { $type = strtolower(trim(substr($line,$i+1))); continue; }
		if ($name == "accept-ranges") { $accept_ranges = strtolower(trim(substr($line,$i+1))); continue; }
		if ($name == "location") { $last_location = trim(substr($line,$i+1)); continue; }
	}
	$size = intval($size);
	if ($size > 0 && $expected_type <> null && $type == $expected_type) {
		$res = array(
			"size"=>$size,
			"accept_ranges"=>$accept_ranges,
			"final_url"=>$last_location,
			"mirrors_provider"=>getMirrorProviderInfo($url)
		);
		return json_encode($res);
	}
	return -1;
}

function getMirrorProviderInfo($url) {
	$i = strpos($url, "sourceforge.net/projects/");
	if ($i !== false) {
		// this is SourceForge
		$s = substr($url, $i+25);
		$i = strpos($s, "/files/");
		if ($i !== false) {
			$project_name = substr($s, 0, $i);
			$s = substr($s, $i+7);
			$i = strpos($s, "/download");
			if ($i !== false) {
				$filename = substr($s, 0, $i);
				return array(
					"provider"=>"sf.net",
					"project"=>$project_name,
					"file"=>$filename
				);
			}
		}
	}
	return null; // unknown
}
function getMirrorURL($url, $provider, $mirror_id) {
	if (@$provider["provider"] == "sf.net") {
		return $url.+"?use_mirror=".$mirror_id;
	}
	return $url;
}

function unzipFile($zip_path, $target_dir, $remove_after_unzip = false) {
	if (class_exists("ZipArchive")) {
		$zip = new ZipArchive();
		$zip->open($zip_path);
		$ok = @$zip->extractTo($target_dir);
		if (!$ok)
			throw new Exception("Error unzipping file ".$zip_path);
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
function getMirrorsList($provider) {
	if ($provider["provider"] == "sf.net") {
		$c = curl_init("https://sourceforge.net/settings/mirror_choices?projectname=".urlencode($provider["project"])."&filename=".urlencode($provider["file"]));
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 25);
		set_time_limit(45);
		$result = curl_exec($c);
		if ($result === false) return null;
		$i = strpos($result, "<ul id=\"mirrorList\">");
		$mirrors = array();
		if ($i !== false) {
			$result = substr($result, $i);
			$i = strpos($result, "</ul>");
			if ($i !== false) {
				$result = substr($result, 0, $i);
				while (($i = strpos($result, "<li id=\"")) !== false) {
					$result = substr($result, $i+8);
					$i = strpos($result, "\"");
					if ($i !== false) {
						$id = substr($result,0,$i);
						$i = strpos($result, "<label");
						if ($i !== false) {
							$j = strpos($result, ">", $i);
							if ($j !== false) {
								$k = strpos($result, "</li>");
								if ($k !== false) {
									$name = substr($result, $j+1, $k-$j-1);
									$name = trim(str_replace("</label>","",$name));
									$mirrors[$id] = $name;
									$result = substr($result, $k+5);
								}
							}
						}
					}
				}
			}
		}
		return $mirrors;
	}
	return null;
}
?>