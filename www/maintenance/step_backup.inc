<?php 
if (isset($_GET["backup"])) {
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
	
	if (file_exists("data/update_backup")) remove_directory("data/update_backup");
	mkdir("data/update_backup");
	
	global $db_config;
	require_once("component/PNApplication.inc");
	PNApplication::$instance = new PNApplication();
	PNApplication::$instance->init();
	require_once("DataBaseSystem_".$db_config["type"].".inc");
	$db_system_class = "DataBaseSystem_".$db_config["type"];
	$db_system = new $db_system_class;
	$res = $db_system->connect($db_config["server"], $db_config["user"], $db_config["password"]);
	if ($res <> DataBaseSystem::ERR_OK) die("Error: unable to create a backup because we cannot connect to the database");
	$domains = include("conf/domains");
	$path = realpath("data/update_backup");
	foreach ($domains as $domain=>$conf) {
		PNApplication::$instance->local_domain = $domain;
		PNApplication::$instance->current_domain = $domain;
		mkdir("data/update_backup/$domain");
		$res = $db_system->execute("SELECT concat('SELECT * INTO OUTFILE \\'".str_replace("\\","/",$path."/$domain/")."',table_name,'\\' FROM ',table_name,';') FROM information_schema.tables WHERE table_schema = 'students_$domain'");
		$db_system->execute("USE students_".$domain);
		while (($sql = $db_system->nextRowArray($res)) <> null) {
			set_time_limit(600);
			$db_system->execute($sql[0]);
		}
	}
	if (PNApplication::hasErrors())
		PNApplication::printErrors();
	else {
		include("header.inc");
		echo "<div style='flex:none;background-color:white;padding:10px'>";
		echo "Backup done.<br/>Installing new version for testing...";
		echo "<form name='install' method='POST' action='?step=install_test'>";
		echo "<input type='hidden' name='version' value='".$_POST["version"]."'/>";
		echo "<input type='hidden' name='path' value='".$_POST["path"]."'/>";
		echo "</form>";
		echo "<script type='text/javascript'>document.forms['install'].submit();</script>";
		echo "</div>";
		include("footer.inc");
	}
} else {
	include("header.inc");
	echo "<div style='flex:none;background-color:white;padding:10px'>";
	echo "Backuping database...";
	echo "<form name='install' method='POST' action='?step=backup&backup=true'>";
	echo "<input type='hidden' name='version' value='".$_POST["version"]."'/>";
	echo "<input type='hidden' name='path' value='".$_POST["path"]."'/>";
	echo "</form>";
	echo "<script type='text/javascript'>document.forms['install'].submit();</script>";
	echo "</div>";
	include("footer.inc");
}
?>