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
	
	require_once 'HTTPClient.inc';
	$c = new HTTPClient();
	$req = new HTTPRequest();
	$req->setURL($url);
	if ($from !== null)
		$req->downloadRange($from, $to);
	$responses = $c->send($req);
	$resp = $responses[count($responses)-1];
	if ($resp->getStatus() < 200 || $resp->getStatus() >= 300)
		throw new Exception("Error downloading file, server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
	if ($target == null) return $resp->getBody();
	$f = fopen($target,"a");
	fwrite($f,$resp->getBody());
	fclose($f);
	return true;
}

function getURLFileSize($url, $expected_type = null, $mirror_id = null, $mirrors_provider = null) {
	if ($mirror_id <> null && $mirrors_provider <> null) {
		$provider = json_decode($mirrors_provider, true);
		if ($provider <> null)
			$url = getMirrorURL($url, $provider, $mirror_id);
	}
	require_once 'HTTPClient.inc';
	$c = new HTTPClient();
	$req = new HTTPRequest();
	$req->setURL($url);
	$req->setMethod("HEAD");
	$responses = $c->send($req);
	$resp = $responses[count($responses)-1];
	if ($resp->getStatus() < 200 || $resp->getStatus() >= 300)
		throw new Exception("Error locating file, server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
	$size = intval($resp->getHeader("content-length"));
	$type = $resp->getHeader("content-type");
	$accept_ranges = $resp->getHeader("accept-ranges");
	$last_location = $req->getURL();
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
		require_once 'HTTPClient.inc';
		$c = new HTTPClient();
		$req = new HTTPRequest();
		$req->setURL("https://sourceforge.net/settings/mirror_choices?projectname=".urlencode($provider["project"])."&filename=".urlencode($provider["file"]));
		try {
			$responses = $c->send($req);
			$resp = $responses[count($responses)-1];
			if ($resp->getStatus() < 200 || $resp->getStatus() >= 300) {
				throw new Exception("Server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
			}
		} catch (Exception $e) {
			return null;
		}
		$result = $resp->getBody();
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