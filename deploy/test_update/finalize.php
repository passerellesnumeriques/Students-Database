<?php 
$path = realpath($_GET["path"]);

function remove_directory($path) {
	$dir = opendir($path);
	while (($filename = readdir($dir)) <> null) {
		if ($filename == ".") continue;
		if ($filename == "..") continue;
		if (is_dir($path."/".$filename))
			remove_directory($path."/".$filename);
		else
			unlink($path."/".$filename);
	}
	closedir($dir);
	rmdir($path);
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

if (file_exists($path."/test_deploy"))
	remove_directory($path."/test_deploy");
mkdir($path."/test_deploy");

unzipFile("download/software.zip", $path."/test_deploy");
@mkdir($path."/test_deploy/data");
@mkdir($path."/test_deploy/data/init");
unzipFile("download/init_data.zip", $path."/test_deploy/data/init");
@mkdir($path."/test_deploy/data/backups");
@mkdir($path."/test_deploy/data/backups/".$_GET["version"]);
@mkdir($path."/test_deploy/data/backups/".$_GET["version"]."/".$_GET["time"]);
copy("download/db.zip", $path."/test_deploy/data/backups/".$_GET["version"]."/".$_GET["time"]."/db.zip");
copy("download/storage.zip", $path."/test_deploy/data/backups/".$_GET["version"]."/".$_GET["time"]."/storage.zip");
if (file_exists("download/custom_tables.zip"))
	copy("download/custom_tables.zip", $path."/test_deploy/data/backups/".$_GET["version"]."/".$_GET["time"]."/custom_tables.zip");
@mkdir($path."/test_deploy/data/updates");
$dir = opendir($_GET["path"]."/to_deploy");
while (($file = readdir($dir)) <> null) {
	if ($file == "." || $file == "..") continue;
	copy($_GET["path"]."/to_deploy/$file", $path."/test_deploy/data/updates/$file");
}

remove_directory("download");
remove_directory("conf");

echo "The software is ready to test in directory ".$_GET["path"]."/test_deploy<br/>";
echo "You can launch a web server to this directory, it already contains the software version ".$_GET["version"]." with the backup that you can install through the maintenance mode, it also contains the update ready to be installed.<br/>";
?>