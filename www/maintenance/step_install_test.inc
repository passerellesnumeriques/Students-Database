<?php
if (isset($_GET["install"])) { 
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
			PNApplication::error("Error unzipping software (".$ret.")");
	}
	
	copy("install_config.inc","maintenance/update/www/install_config.inc");
	
	// instantiate new PNApplication
	global $previous_version_path, $new_version_path;
	$previous_version_path = realpath(dirname(__FILE__)."/..");
	$new_version_path = realpath("maintenance/update/www");
	
	$include_path = get_include_path();
	set_include_path($new_version_path);
	chdir($new_version_path);
	require_once("component/PNApplication.inc");
	PNApplication::$instance = new PNApplication();
	PNApplication::$instance->init();
	set_error_handler(function($severity, $message, $filename, $lineno) {
		if (error_reporting() == 0) return true;
		PNApplication::error("PHP Error: ".$message." in ".$filename.":".$lineno);
		return true;
	});
	require_once("SQLQuery.inc");
	require_once("component/data_model/Model.inc");
	
	// apply migration scripts
	$path = json_decode($_POST["path"], true);
	$index = 1;
	while ($index < count($path)) {
		if (file_exists($previous_version_path."/maintenance/update/migrate")) remove_directory($previous_version_path."/maintenance/update/migrate");
		mkdir($previous_version_path."/maintenance/update/migrate");
		if (class_exists("ZipArchive")) {
			$zip = new ZipArchive();
			$zip->open($previous_version_path."/data/updates/Students_Management_Software_".$path[$index-1]."_to_".$path[$index].".zip");
			$zip->extractTo($previous_version_path."/maintenance/update/migrate");
			$zip->close();
		} else {
			$output = array();
			$ret = 0;
			exec("/usr/bin/unzip \"".$previous_version_path."/data/updates/Students_Management_Software_".$path[$index-1]."_to_".$path[$index].".zip"."\" -d \"".$previous_version_path."/maintenance/update/migrate"."\"", $output, $ret);
			if ($ret <> 0) {
				PNApplication::error("Error unzipping migration scripts (".$ret.")");
				break;
			}
		}
		if (file_exists($previous_version_path."/maintenance/update/migrate/before_datamodel.php")) {
			include($previous_version_path."/maintenance/update/migrate/before_datamodel.php");
		}
		if (file_exists($previous_version_path."/maintenance/update/migrate/datamodel_update.php")) {
			include($previous_version_path."/maintenance/update/migrate/datamodel_update.php");
		}
		if (file_exists($previous_version_path."/maintenance/update/migrate/after_datamodel.php")) {
			include($previous_version_path."/maintenance/update/migrate/after_datamodel.php");
		}
		$index++;
	}
	
	if (PNApplication::hasErrors())
		PNApplication::printErrors();
	else {
		include($previous_version_path."/maintenance/header.inc");
		?>
		<div style='flex:none;background-color:white;padding:10px'>
			<?php 
			if (count($path) < 2)
				echo "Warning: no migration scripts found.";
			else {
				echo "Migration done: ";
				for ($i = 1; $i < count($path); $i++) {
					if ($i > 1) echo ", ";
					echo "from ".$path[$i-1]." to ".$path[$i];
				}
			}
			?>
			<br/>
			<br/>
			Software ready to be tested<br/>
			<a href='/?request_to_test_update=true' target='_blank'>Test it</a><br/>
			Once ready:
			<a href='?step=install'>Install it</a><br/>
			Or
			<a href='?step=recover&time=<?php echo $_POST["backup_time"];?>&ondone=destroy_sessions'>Cancel (put back previous database)</a>
		</div>
		<?php
		include($previous_version_path."/maintenance/footer.inc"); 
	}
} else {
	include("header.inc");
	echo "<div style='flex:none;background-color:white;padding:10px'>";
	echo "Installing new version for testing...";
	echo "<form name='install' method='POST' action='?step=install_test&install=true'>";
	echo "<input type='hidden' name='version' value='".$_POST["version"]."'/>";
	echo "<input type='hidden' name='path' value='".$_POST["path"]."'/>";
	echo "<input type='hidden' name='backup_time' value='".$_POST["backup_time"]."'/>";
	echo "</form>";
	echo "<script type='text/javascript'>document.forms['install'].submit();</script>";
	echo "</div>";
	include("footer.inc");
} 
?>