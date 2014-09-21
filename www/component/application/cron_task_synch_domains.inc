<?php 
if (!file_exists("data/domains_synch")) mkdir("data/domains_synch");

function getDomainVersion($domain) {
	$remote_access = include("conf/$domain.remote");
	$c = curl_init($remote_access["url"]."dynamic/application/service/get_backup");
	if (file_exists("conf/proxy")) include("conf/proxy");
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_HEADER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_POSTFIELDS, array("request"=>"get_list"));
	curl_setopt($c, CURLOPT_HTTPHEADER, array("User-Agent: Students Management Software - Domain Synchronization from ".PNApplication::$instance->local_domain));
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($c, CURLOPT_TIMEOUT, 30);
	$result = curl_exec($c);
	if ($result === false) {
		PNApplication::error("Connection to domain $domain: ".curl_error($c));
		curl_close($c);
		return null;
	}
	curl_close($c);
	
	$i = strpos($result, "pn_version_changed:");
	if ($i === false) {
		PNApplication::error("We can connect to the URL of domain $domain, but this is not Students Management Software");
		return null;
	}
	$result = substr($result,$i+19);
	$i = strpos($result,"\n");
	$version = trim(substr($result,0,$i));
	return $version;
}

function getLatestBackup($domain, $version, $app_version) {
	$remote_access = include("conf/$domain.remote");
	$c = curl_init($remote_access["url"]."dynamic/application/service/get_backup");
	if (file_exists("conf/proxy")) include("conf/proxy");
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_HEADER, FALSE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_POSTFIELDS, array("request"=>"get_list","password"=>$remote_access["password"]));
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($c, CURLOPT_TIMEOUT, 30);
	curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$app_version","User-Agent: Students Management Software - Domain Synchronization from ".PNApplication::$instance->local_domain));
	$result = curl_exec($c);
	if ($result === false) {
		PNApplication::error("Connection to domain $domain: ".curl_error($c));
		curl_close($c);
		return null;
	}
	curl_close($c);

	$backups = json_decode($result, true);
	if ($backups == null) {
		PNApplication::error("Invalid response of domain $domain");
		return null;
	}
	if ($backups["result"] == null) {
		PNApplication::error("Access denied for domain $domain");
		return null;
	}
	if (!is_array($backups["result"])) {
		PNApplication::error("Invalid response of domain $domain");
		return null;
	}
	$latest = null;
	global $pn_app_version;
	foreach ($backups["result"] as $backup) {
		if ($backup["version"] <> $pn_app_version) continue;
		$time = intval($backup["time"]);
		if ($latest == null || $latest < $time) $latest = $time;
	}
	return $latest;
}

function getBackupFileSize($domain, $version, $time, $file, $app_version) {
	$remote_access = include("conf/$domain.remote");
	$c = curl_init($remote_access["url"]."dynamic/application/service/get_backup?get_size=true");
	if (file_exists("conf/proxy")) include("conf/proxy");
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_HEADER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_POSTFIELDS, array("request"=>"get_backup","password"=>$remote_access["password"],"version"=>$version,"time"=>$time,"file"=>$file));
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($c, CURLOPT_TIMEOUT, 30);
	curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$app_version","User-Agent: Students Management Software - Domain Synchronization from ".PNApplication::$instance->local_domain));
	$result = curl_exec($c);
	if ($result === false) {
		PNApplication::error("Connection to domain $domain: ".curl_error($c));
		curl_close($c);
		return null;
	}
	curl_close($c);
	if (strpos($result, "HTTP/1.0 404") !== false)
		return -1;
	$i = strpos($result, "File-Size:");
	if ($i === false) {
		PNApplication::error("Invalid response of domain $domain");
		return null;
	}
	$result = substr($result, $i+10);
	$i = strpos($result, "\n");
	if ($i === false) {
		PNApplication::error("Invalid response of domain $domain");
		return null;
	}
	$result = trim(substr($result,0,$i));
	if (!is_numeric($result)) {
		PNApplication::error("Invalid response of domain $domain");
		return null;
	}
	return intval($result);
}

function getBackupFiles($domain, $version, $time, $app_version) {
	$files = array();
	require_once("component/application/Backup.inc");
	foreach (Backup::$possible_files as $filename) {
		$size = getBackupFileSize($domain, $version, $time, $filename, $app_version);
		if ($size == null) return null;
		$files[$filename] = $size;
	}
	return $files;
}

function downloadBackup($domain, $version, $time, $file, $from, $to, $target, $app_version, $max_time) {
	$remote_access = include("conf/$domain.remote");
	$c = curl_init($remote_access["url"]."dynamic/application/service/get_backup");
	if (file_exists("conf/proxy")) include("conf/proxy");
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_HEADER, FALSE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_POSTFIELDS, array("request"=>"get_backup","password"=>$remote_access["password"],"version"=>$version,"time"=>$time,"file"=>$file));
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($c, CURLOPT_TIMEOUT, $max_time);
	curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$app_version","Range: bytes=".$from."-".$to,"User-Agent: Students Management Software - Domain Synchronization from ".PNApplication::$instance->local_domain));
	$result = curl_exec($c);
	if ($result === false) {
		PNApplication::error("Download $file from domain $domain: ".curl_error($c));
		curl_close($c);
		return 0;
	}
	curl_close($c);
	$f = fopen($target, "a");
	fwrite($f, $result);
	fclose($f);
	return strlen($result);
}

$start = time();
foreach (PNApplication::$instance->getDomains() as $domain=>$descr) {
	if (!file_exists("conf/$domain.remote")) continue;
	if (!file_exists("data/domains_synch/$domain")) mkdir("data/domains_synch/$domain");
	global $pn_app_version;
	if (file_exists("data/domains_synch/$domain/in_progress")) {
		if (time()-$start > 90) continue; // we already spend more than 1.5 minute, we cannot spend more => skip this download
		$download = include("data/domains_synch/$domain/in_progress");
		touch("data/domains_synch/$domain/in_progress");
		if ($download["version"] <> $pn_app_version) {
			// we changed version, we need to cancel this download
			foreach ($download["files"] as $filename=>$filesize)
				@unlink("data/domains_synch/$domain/$filename.progress");
			@unlink("data/domains_synch/$domain/in_progress");
			continue;
		}
		$version = getDomainVersion($domain);
		if ($version == null) continue; // unable to connect to the domain
		$done = true;
		$speed = 128*1024;
		foreach ($download["files"] as $filename=>$filesize) {
			if ($filesize < 0) continue; // no file
			if (!file_exists("data/domains_synch/$domain/$filename.progress")) {
				$current_size = 0;
				$f = fopen("data/domains_synch/$domain/$filename.progress","w");
				fclose($f);
			} else {
				$current_size = filesize("data/domains_synch/$domain/$filename.progress");
			}
			if ($current_size < $filesize) {
				do {
					$remaining = 90-(time()-$start);
					$start_download = time();
					$to = $current_size + $speed;
					if ($to >= $filesize) $to = $filesize-1;
					$prev_size = $current_size;
					$current_size += downloadBackup($domain, $download["version"], $download["time"], $filename, $current_size, $to, "data/domains_synch/$domain/$filename.progress", $version, $remaining);
					$download_time = time()-$start_download;
					if ($download_time > 0) {
						$real_speed = ($current_size-$prev_size)/$download_time;
						$remaining = 60-(time()-$start);
						if ($remaining < 10) break;
						$speed = $real_speed * $remaining * 0.8;
						if ($speed < 65536) $speed = 65536;
						else if ($speed > 5*1024*1024) $speed = 5*1024*1024;
					}
				} while (time() - $start < 60 && $current_size < $filesize && $current_size > $prev_size);
				if ($current_size < $filesize)
					$done = false;
			}
		}
		if ($done) {
			// all files successfully downloaded !
			require_once("component/application/Backup.inc");
			foreach (Backup::$possible_files as $filename)
				@unlink("data/domains_synch/$domain/$filename.zip");
			foreach ($download["files"] as $filename=>$filesize)
				if ($filesize >= 0)
					rename("data/domains_synch/$domain/$filename.progress", "data/domains_synch/$domain/$filename.zip");
			rename("data/domains_synch/$domain/in_progress", "data/domains_synch/$domain/latest_download");
		}
	} else {
		if (!file_exists("data/domains_synch/$domain/last_check"))
			$last_check = 0;
		else {
			$info = stat("data/domains_synch/$domain/last_check");
			$last_check = $info["mtime"];
		}
		if ($start - $last_check < 60*60) {
			// put back previous errors
			$errors = json_decode(file_get_contents("data/domains_synch/$domain/last_check"),true);
			if ($errors <> null)
				foreach ($errors as $err) PNApplication::errorHTML($err);
			continue; // we already checked less than 1 hour ago
		}
		$f = fopen("data/domains_synch/$domain/last_check","w");
		$prev_errors = PNApplication::$errors;
		PNApplication::$errors = array();
		$version = getDomainVersion($domain);
		if ($version == null) {
			fwrite($f, json_encode(PNApplication::$errors));
			fclose($f);
			PNApplication::$errors = array_merge($prev_errors, PNApplication::$errors);
			continue; // no available backup
		}
		PNApplication::$errors = array_merge($prev_errors, PNApplication::$errors);
		$latest = getLatestBackup($domain, $pn_app_version, $version);
		if ($latest == null) {
			PNApplication::error("Cannot synchronize with domain $domain: no available backup for version $pn_app_version");
			fwrite($f, json_encode(array("Cannot synchronize with domain $domain: no available backup for version $pn_app_version")));
			fclose($f);
			continue; // no available backup
		}
		if (file_exists("data/domains_synch/$domain/latest_download")) {
			$latest_download = include("data/domains_synch/$domain/latest_download");
			if ($latest_download["version"] == $pn_app_version && $latest_download["time"] == $latest) {
				fclose($f);
				continue; // we already downloaded the latest, we are up to date !
			}
		}
		$prev_errors = PNApplication::$errors;
		PNApplication::$errors = array();
		$files = getBackupFiles($domain, $pn_app_version, $latest, $version);
		fwrite($f, json_encode(PNApplication::$errors));
		fclose($f);
		PNApplication::$errors = array_merge($prev_errors, PNApplication::$errors);
		if ($files == null) continue;
		$s = "<?php return array('version'=>'".$pn_app_version."','time'=>".$latest.",'files'=>array(";
		$first = true;
		foreach ($files as $filename=>$filesize) {
			if ($first) $first = false; else $s .= ",";
			$s .= "'$filename'=>$filesize";
		}
		$s .= ")); ?>";
		$f = fopen("data/domains_synch/$domain/in_progress", "w");
		fwrite($f, $s);
		fclose($f);
	}
}
?>