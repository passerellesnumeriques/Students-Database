<?php 
if (!file_exists("data/domains_synch")) mkdir("data/domains_synch");

require_once("component/application/RemoteAccess.inc");

function getDomainVersion($domain) {
	$remote_access = include("conf/$domain.remote");
	return RemoteAccess::getDomainVersion($domain, $remote_access["url"]);
}

function getLatestBackup($domain, $version, $app_version) {
	$remote_access = include("conf/$domain.remote");
	$backups = RemoteAccess::getBackupList($domain, $app_version, $remote_access["url"], $remote_access["password"]);
	$latest = null;
	global $pn_app_version;
	foreach ($backups as $backup) {
		if ($backup["version"] <> $pn_app_version) continue;
		$time = intval($backup["time"]);
		if ($latest == null || $latest < $time) $latest = $time;
	}
	return $latest;
}

function getBackupFileSize($domain, $version, $time, $file, $app_version) {
	$remote_access = include("conf/$domain.remote");
	return RemoteAccess::getBackupFileSize($domain, $app_version, $remote_access["url"], $remote_access["password"], $version, $time, $file);
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
	return RemoteAccess::downloadBackupFileRange($domain, $app_version, $remote_access["url"], $remote_access["password"], $version, $time, $file, $from, $to, $target, $max_time);
}

function downloadStorageFile($domain, $version, $time, $id, $size, $app_version) {
	$f = fopen("data/domains_synch/$domain/storage.progress/files/$id","w");
	$remote_access = include("conf/$domain.remote");
	$ok = RemoteAccess::downloadStorageFile($domain, $app_version, $remote_access["url"], $remote_access["password"], $id, $size, $f);
	fclose($f);
	return $ok;
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
			if (isset($download["files"]["storage.list"])) {
				// we still need to do a diff
				if (!file_exists("data/domains_synch/$domain/storage.progress")) {
					mkdir("data/domains_synch/$domain/storage.progress");
					mkdir("data/domains_synch/$domain/storage.progress/files");
					require_once 'component/application/service/deploy_utils.inc';
					unzipFile(realpath("data/domains_synch/$domain/storage.list.progress"), realpath("data/domains_synch/$domain/storage.progress"), true);
					$f = fopen("data/domains_synch/$domain/storage.progress/storage.list","r");
					$files = array();
					$first = -1;
					$last = -1;
					$f_removed = fopen("data/domains_synch/$domain/storage.progress/removed","w");
					$first_removed = true;
					$f_todownload = fopen("data/domains_synch/$domain/storage.progress/to_download","w");
					$first_todownload = true;
					fwrite($f_todownload, "<?php return array(");
					do {
						while (($line = fgets($f,200)) !== FALSE) {
							$line = trim($line);
							if ($line == "") continue;
							$s = explode(",",$line);
							$files[$s[0]] = array($s[1],$s[2]);
							if ($first == -1) $first = $s[0];
							$last = $s[0];
							if (count($files) > 200) break;
						}
						if (count($files) > 0) {
							$db = SQLQuery::getDataBaseAccessWithoutSecurity();
							global $db_config;
							$res = $db->execute("SELECT `id`,`revision` FROM `".$db_config["prefix"].$domain."`.`Storage` WHERE `id` >= $first AND `id` <= $last");
							while (($row = $db->nextRow($res)) <> null) {
								if (!isset($files[$row["id"]])) {
									if ($first_removed) $first_removed = false; else fwrite($f_removed, ",");
									fwrite($f_removed, $row["id"]);
								} else if ($files[$row["id"]][0] <> $row["revision"]) {
									if ($first_todownload) $first_todownload = false; else fwrite($f_todownload, ",");
									fwrite($f_todownload, "array(".$row["id"].",".$files[$row["id"]][1].")");
									unset($files[$row["id"]]);
								} else {
									unset($files[$row["id"]]);
								}
							}
							// remaining files are new, so to be downloaded
							foreach ($files as $file_id=>$file_info) {
								if ($first_todownload) $first_todownload = false; else fwrite($f_todownload, ",");
								fwrite($f_todownload, "array(".$file_id.",".$file_info[1].")");
							}
						}
						$first = -1;
						$last = -1;
					} while ($line !== FALSE);
					fclose($f_removed);
					fwrite($f_todownload, ");?>");
					fclose($f_todownload);
					fclose($f);
				}
				// download new/changed files
				$done = false;
				$to_download = include("data/domains_synch/$domain/storage.progress/to_download");
				$remaining = 90-(time()-$start);
				while ($remaining > 0 && count($to_download) > 0) {
					$file = $to_download[0];
					if (downloadStorageFile($domain, $download["version"], $download["time"], $file[0]. $file[1], $version))
						array_splice($to_download,0,1);
				}
				if (count($to_download) > 0) {
					$f = fopen("data/domains_synch/$domain/storage.progress/to_download","w");
					fwrite($f, var_export($to_download,true));
					fclose($f);
				} else
					$done = true;
			}
			if ($done) {
				require_once("component/application/Backup.inc");
				foreach (Backup::$possible_files as $filename)
					@unlink("data/domains_synch/$domain/$filename.zip");
				if (file_exists("data/domains_synch/$domain/storage"))
					Backup::removeDirectory("data/domains_synch/$domain/storage");
				if (is_dir("data/domains_synch/$domain/storage.progress"))
					rename("data/domains_synch/$domain/storage.progress", "data/domains_synch/$domain/storage");
				foreach ($download["files"] as $filename=>$filesize)
					if ($filesize >= 0)
						@rename("data/domains_synch/$domain/$filename.progress", "data/domains_synch/$domain/$filename.zip");
				@rename("data/domains_synch/$domain/in_progress", "data/domains_synch/$domain/latest_download");
			}
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
		$db = SQLQuery::getDataBaseAccessWithoutSecurity();
		global $db_config;
		$res = $db->execute("SELECT `id` FROM `".$db_config["prefix"].$domain."`.`Storage` LIMIT 0,1");
		$row = $db->nextRow($res);
		if ($files["storage"] < 200*1024*1024 || $row == null) {
			// first synch, or less than 200Mo, this is acceptable to download everything
			unset($files["storage.list"]);
		} else {
			// more than 200Mo, we should do a diff
			unset($files["storage"]);
		}
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