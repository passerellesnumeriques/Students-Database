<?php 
function progress($text, $pos = null, $total = null) {
	$f = fopen(dirname(__FILE__)."/update_sms_progress","w");
	fwrite($f, ($pos !== null ? "%$pos,$total%" : "").$text);
	fclose($f);
}
progress("Starting download...");

function removeDirectory($path) {
	$dir = opendir($path);
	while (($filename = readdir($dir)) <> null) {
		if ($filename == ".") continue;
		if ($filename == "..") continue;
		if (is_dir($path."/".$filename))
			removeDirectory($path."/".$filename);
		else
			unlink($path."/".$filename);
	}
	closedir($dir);
	if (!@rmdir($path))
		if (!@rmdir($path))
			if (!@rmdir($path))
				if (!@rmdir($path)) {
					sleep(1);
					if (!@rmdir($path))
						if (!@rmdir($path))
							if (!@rmdir($path)) {
								sleep(1);
								if (!@rmdir($path))
									if (!@rmdir($path))
										@rmdir($path);
							}
				}
}

function downloadByStep($server_url, $server_version, $download_version) {
	global $server;
	// initializing download: get filename, size, and id
	progress("Downloading version ".$download_version);
	$url = "http://$server_url/dynamic/selection/service/travel/download_update?version=".urlencode($download_version);
	$c = curl_init($url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$server_version","User-Agent: Students Management Software - Travel Version Update"));
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($c, CURLOPT_TIMEOUT, 250);
	set_time_limit(300);
	$result = curl_exec($c);
	if ($result === false) die("Error: unable to connect to the Students Management Software Server: ".curl_error($c));
	curl_close($c);
	$info = json_decode($result, true);
	if ($info == null) die("Error: we received unexpected data from the server: ".$result);
	if (isset($info["errors"]) && count($info["errors"]) > 0) {
		echo "Error retrieving download information:<ul>";
		foreach ($info["errors"] as $err) echo "<li>$err</li>";
		echo "</ul>";
		die();
	}
	if (!isset($info["result"])) die("Error: we received unexpected data from the server: ".$result);
	$info = $info["result"];
	$filename = $info["filename"];
	$filesize = $info["size"];
	$f = fopen(dirname(__FILE__)."/update/$filename","w");
	// ask the server to download
	$downloaded = 0;
	while ($downloaded < $filesize) {
		progress("Downloading version ".$download_version,$downloaded, $filesize);
		$url = "http://$server_url/dynamic/selection/service/travel/download_update?from=".$downloaded."&size=".$filesize."&version=".urlencode($download_version);
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$server_version","User-Agent: Students Management Software - Travel Version Update"));
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($c, CURLOPT_TIMEOUT, 330);
		set_time_limit(350);
		$result = curl_exec($c);
		if ($result === false) die("Error: unable to connect to the Students Management Software Server: ".curl_error($c));
		curl_close($c);
		$progress = strlen($result);
		if ($progress == 0) {
			die("An error occured while downloading the file. Please try again.");
		}
		fwrite($f, $result);
		$downloaded += $progress;
	}
	fclose($f);
	return $filename;
}

function downloadFile($server_url, $server_version, $filename, $target = null) {
	$c = curl_init("http://$server_url/dynamic/selection/service/travel/download_update?file=".$filename);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$server_version","User-Agent: Students Management Software - Travel Version Update"));
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($c, CURLOPT_TIMEOUT, 330);
	set_time_limit(350);
	$result = curl_exec($c);
	if ($result === false) die("Error: unable to connect to the Students Management Software Server: ".curl_error($c));
	curl_close($c);
	if ($target <> null) {
		$f = fopen($target, "w");
		fwrite($f, $result);
		fclose($f);
	}
	return $result;
}

function checkFile($server_url, $server_version, $filename) {
	// get the md5
	progress("Checking the downloaded file is valid");
	$md5 = downloadFile($server_url, $server_version, $filename.".md5");
	$md5_file = md5_file(dirname(__FILE__)."/update/$filename");
	if ($md5_file <> $md5) die("Error: the downloaded file is invalid, please try again");
}

function copyDirectory($src, $dst) {
	set_time_limit(240);
	$dir = opendir($src);
	if (!$dir) die("Unable to access to directory ".$src);
	while (($file = readdir($dir)) <> null) {
		if ($file == "." || $file == "..") continue;
		if (is_dir($src."/".$file)) {
			if (!mkdir($dst."/".$file)) die("Unable to create directory ".$dst."/".$file);
			copyDirectory($src."/".$file, $dst."/".$file);
		} else {
			if (!copy($src."/".$file, $dst."/".$file)) die("Unable to copy file ".$src."/".$file);
		}
	}
	closedir($dir);
}


if (file_exists(dirname(__FILE__)."/update")) removeDirectory(dirname(__FILE__)."/update");
if (file_exists(dirname(__FILE__)."/update")) {
	sleep(1);
	removeDirectory(dirname(__FILE__)."/update");
}
if (!@mkdir(dirname(__FILE__)."/update"))
	if (!@mkdir(dirname(__FILE__)."/update")) {
		sleep(1);
		if (!@mkdir(dirname(__FILE__)."/update"))
			if (!@mkdir(dirname(__FILE__)."/update"))
				if (!file_exists(dirname(__FILE__)."/update"))
					die("Error: we cannot create the directory ".dirname(__FILE__)."/update");
	}

$server = $_POST["server"];
$version = $_POST["version"];
$migrate_from = @$_POST["migrate_from"];

if ($migrate_from == null) {
	// only an update
	$filename = downloadByStep($server, $version, $version);
	checkFile($server, $version, $filename);
	progress("Installing Students Management Software on your computer");
	// extract the update
	$sms_path = realpath(dirname(__FILE__)."/..")."/sms";
	if (file_exists($sms_path)) removeDirectory($sms_path);
	if (file_exists($sms_path)) {
		sleep(1);
		removeDirectory($sms_path);
	}
	$zip = new ZipArchive();
	$zip->open(dirname(__FILE__)."/update/$filename");
	$ok = @$zip->extractTo($sms_path);
	if (!$ok)
		die("Error extracting files, the downloaded file is probably damaged. Please try again.");
	$zip->close();
	// upgrade server_comm
	$dir = opendir($sms_path."/server_comm");
	while (($filename = readdir($dir)) <> null) {
		if (is_dir($sms_path."/server_comm/$filename")) continue;
		copy($sms_path."/server_comm/$filename", dirname(__FILE__)."/$filename");
	}
	closedir($dir);
} else {
	// migration
	if (!file_exists(dirname(__FILE__)."/../sms/version")) die("Error: we cannot upgrade because there is no version installed");
	$cur = file_get_contents(dirname(__FILE__)."/../sms/version");
	if ($cur <> $migrate_from) die("Error: we cannot upgrade from version $migrate_from because your current version is $cur");
	$versions = downloadFile($server, $version, "versions.txt");
	$versions = explode("\n",$versions);
	$found = false;
	$to_version = null;
	foreach ($versions as $v) {
		if (!$found) {
			if ($v == $migrate_from) $found = true;
			continue;
		}
		$to_version = $v;
		break;
	}
	if (!$found) die("Error: we cannot find your version (".$migrate_from."). Please contact an administrator.");
	$from_version = $migrate_from;
	// download the new version
	$filename = downloadByStep($server, $version, $to_version);
	checkFile($server, $version, $filename);
	// download the migration script
	$migration_filename = "Students_Management_Software_".$from_version."_to_".$to_version.".zip";
	$target = dirname(__FILE__)."/update/$migration_filename";
	progress("Downloading necessary files to upgrade from version $from_version to $to_version");
	downloadFile($server, $version, $migration_filename, $target);
	checkFile($server, $version, $migration_filename);
	progress("Creation of a backup of your database");
	exec(realpath(dirname(__FILE__)."/../../server")."/mysqldump.exe --host=localhost --port=8889 --user=root --password= selectiontravel_PNC > ".realpath(dirname(__FILE__)."/..")."/backup_".time().".sql");
	progress("Upgrading your computer from version $from_version to $to_version");
	if (file_exists(dirname(__FILE__)."/migrate")) removeDirectory(dirname(__FILE__)."/migrate");
	if (file_exists(dirname(__FILE__)."/migrate")) {
		sleep(1);
		removeDirectory(dirname(__FILE__)."/migrate");
	}
	if (!@mkdir(dirname(__FILE__)."/migrate"))
		if (!@mkdir(dirname(__FILE__)."/migrate")) {
			sleep(1);
			if (!@mkdir(dirname(__FILE__)."/migrate"))
				if (!@mkdir(dirname(__FILE__)."/migrate"))
					if (!file_exists(dirname(__FILE__)."/migrate"))
						die("Error: we cannot create the directory ".dirname(__FILE__)."/migrate");
		}
	mkdir(dirname(__FILE__)."/migrate/www");
	mkdir(dirname(__FILE__)."/migrate/scripts");
	// extract the update
	$path = realpath(dirname(__FILE__)."/migrate/www");
	$zip = new ZipArchive();
	$zip->open(dirname(__FILE__)."/update/$filename");
	$ok = @$zip->extractTo($path);
	if (!$ok)
		die("Error extracting files, the downloaded file is probably damaged. Please try again.");
	$zip->close();
	//extract migration scripts
	$migration_path = realpath(dirname(__FILE__)."/migrate/scripts");
	$zip = new ZipArchive();
	$zip->open(dirname(__FILE__)."/update/$migration_filename");
	$ok = @$zip->extractTo($migration_path);
	if (!$ok)
		die("Error extracting files, the downloaded file is probably damaged. Please try again.");
	$zip->close();
	
	// apply migration scripts
	
	global $previous_version_path, $new_version_path;
	$previous_version_path = realpath(dirname(__FILE__)."/../sms");
	$new_version_path = $path;
	// setup migrated version
	copy($previous_version_path."/conf/selection_travel_username", $new_version_path."/conf/selection_travel_username");
	copy($previous_version_path."/conf/instance.uid", $new_version_path."/conf/instance.uid");
	copy($previous_version_path."/conf/domains", $new_version_path."/conf/domains");
	copy($previous_version_path."/install_config.inc", $new_version_path."/install_config.inc");
	
	// instantiate new PNApplication
	$include_path = get_include_path();
	set_include_path($new_version_path);
	chdir($new_version_path);
	require_once("component/PNApplication.inc");
	require_once("SQLQuery.inc");
	require_once("component/data_model/Model.inc");
	require_once("install_config.inc");
	PNApplication::$instance = new PNApplication();
	PNApplication::$instance->init();
	set_error_handler(function($severity, $message, $filename, $lineno) {
		if (error_reporting() == 0) return true;
		PNApplication::error("PHP Error: ".$message." in ".$filename.":".$lineno);
		return true;
	});
	
	// apply migration scripts
	$current_version = file_get_contents($previous_version_path."/version");
	if (file_exists($migration_path."/before_datamodel.php")) {
		include($migration_path."/before_datamodel.php");
	}
	if (file_exists($migration_path."/datamodel_update.php")) {
		include($migration_path."/datamodel_update.php");
	}
	if (file_exists($migration_path."/data.sql")) {
		require_once("component/data_model/DataBaseUtilities.inc");
		DataBaseUtilities::importSQL(SQLQuery::getDataBaseAccessWithoutSecurity(), $migration_path."/data.sql");
	}
	if (file_exists($migration_path."/after_datamodel.php")) {
		include($migration_path."/after_datamodel.php");
	}
	
	if (PNApplication::hasErrors()) {
		PNApplication::printErrors();
		die();
	}
	
	// install new update
	removeDirectory($previous_version_path);
	if (file_exists($previous_version_path)) {
		sleep(1);
		removeDirectory($previous_version_path);
	}
	if (!@mkdir($previous_version_path))
		if (!@mkdir($previous_version_path)) {
			sleep(1);
			if (!@mkdir($previous_version_path))
				if (!@mkdir($previous_version_path))
					if (!file_exists($previous_version_path))
						die("Error: we cannot create the directory ".$previous_version_path);
		}
	copyDirectory($new_version_path, $previous_version_path);
	// upgrade server_comm
	$dir = opendir($previous_version_path."/server_comm");
	while (($filename = readdir($dir)) <> null) {
		if (is_dir($previous_version_path."/server_comm/$filename")) continue;
		copy($previous_version_path."/server_comm/$filename", dirname(__FILE__)."/$filename");
	}
	closedir($dir);
	
	die("OK:".$to_version);
}

// deactivate the software
@unlink($sms_path."/index.php");
copy($sms_path."/index_deactivated.php", $sms_path."/index.php");

echo "OK";
?>