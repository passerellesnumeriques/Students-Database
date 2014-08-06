<?php 
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
	if (!@rmdir($path))
		rmdir($path);
}

if (file_exists("maintenance/update")) remove_directory("maintenance/update");
mkdir("maintenance/update");
mkdir("maintenance/update/www");

// unzip software
if (class_exists("ZipArchive")) {
	$zip = new ZipArchive();
	$zip->open("data/updates/Students_Management_Software_".$_POST["version"].".zip");
	$zip->extractTo("maintenance/update/www");
	$zip->close();
} else {
	$output = array();
	$ret = 0;
	exec("/usr/bin/unzip \"".realpath("data/updates/Students_Management_Software_".$_POST["version"].".zip")."\" -d \"".realpath("maintenance/update/www")."\"", $output, $ret);
	if ($ret <> 0)
		throw new Exception("Error unzipping software (".$ret.")");
}

copy("install_config.inc","maintenance/update/www/install_config.inc");

// apply migration scripts
$path = json_decode($_POST["path"], true);
$index = 1;
while ($index < count($path)) {
	if (file_exists("maintenance/update/migrate")) remove_directory("maintenance/update/migrate");
	mkdir("maintenance/update/migrate");
	if (class_exists("ZipArchive")) {
		$zip = new ZipArchive();
		$zip->open("data/updates/Students_Management_Software_".$path[$index-1]."_to_".$path[$index].".zip");
		$zip->extractTo("maintenance/update/migrate");
		$zip->close();
	} else {
		$output = array();
		$ret = 0;
		exec("/usr/bin/unzip \"".realpath("data/updates/Students_Management_Software_".$path[$index-1]."_to_".$path[$index].".zip")."\" -d \"".realpath("maintenance/update/migrate")."\"", $output, $ret);
		if ($ret <> 0) {
			echo("Error unzipping software (".$ret.")");
			break;
		}
	}
	if (file_exists("maintenance/update/migrate/before_datamodel.php")) {
		include("maintenance/update/migrate/before_datamodel.php");
	}
	if (file_exists("maintenance/update/migrate/datamodel_change.php")) {
		global $db_config;
		require_once("component/PNApplication.inc");
		PNApplication::$instance = new PNApplication();
		PNApplication::$instance->init();
		require_once("DataBaseSystem_".$db_config["type"].".inc");
		$db_system_class = "DataBaseSystem_".$db_config["type"];
		$db_system = new $db_system_class;
		$res = $db_system->connect($db_config["server"], $db_config["user"], $db_config["password"]);
		if ($res <> DataBaseSystem::ERR_OK) {
			echo("Error: unable to migrate because we cannot connect to the database");
			break;
		}
		include("maintenance/update/migrate/datamodel_change.php");
	}
	if (file_exists("maintenance/update/migrate/after_datamodel.php")) {
		include("maintenance/update/migrate/after_datamodel.php");
	}
	$index++;
}

?>
Software ready to be tested<br/>
<a href='/?request_to_test_update=true' target='_blank'>Test it</a><br/>
Once ready:
<a href='?step=install'>Install it</a><br/>
Or
<a href='?step=cancel'>Cancel (put back previous database)</a>
