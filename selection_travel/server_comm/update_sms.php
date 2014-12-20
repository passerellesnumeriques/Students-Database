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
		rmdir($path);
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

function checkFile($server_url, $server_version, $filename) {
	// get the md5
	progress("Checking the downloaded file is valid");
	$c = curl_init("http://$server_url/dynamic/selection/service/travel/download_update?file=".$filename.".md5");
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$server_version","User-Agent: Students Management Software - Travel Version Update"));
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($c, CURLOPT_TIMEOUT, 330);
	set_time_limit(350);
	$result = curl_exec($c);
	if ($result === false) die("Error: unable to connect to the Students Management Software Server: ".curl_error($c));
	curl_close($c);
	$md5 = md5_file(dirname(__FILE__)."/update/$filename");
	if ($result <> $md5) die("Error: the downloaded file is invalid, please try again");
}

if (file_exists(dirname(__FILE__)."/update")) removeDirectory(dirname(__FILE__)."/update");
mkdir(dirname(__FILE__)."/update");

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
}

// deactivate the software
@unlink($sms_path."/index.php");
copy($sms_path."/index_deactivated.php", $sms_path."/index.php");

echo "OK";
?>