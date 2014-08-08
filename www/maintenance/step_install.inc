<?php 
if (isset($_GET["copy"])) {
	require_once("component/PNApplication.inc");
	PNApplication::$instance = new PNApplication();
	PNApplication::$instance->init();
	
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
	
	function installDirectory($src,$dst,$keep) {
		set_time_limit(240);
		$dir_src = opendir($src);
		if (!$dir_src) die("Unable to access to directory ".$src);
		$dir_dst = opendir($dst);
		if (!$dir_dst) die("Unable to access to directory ".$src);
		$files_src = array();
		while (($file = readdir($dir_src)) <> null) {
			if ($file == "." || $file == "..") continue;
			array_push($files_src, $file);
		}
		$files_dst = array();
		while (($file = readdir($dir_dst)) <> null) {
			if ($file == "." || $file == "..") continue;
			if (in_array(realpath($dst."/".$file),$keep)) continue;
			array_push($files_dst, $file);
		}
		closedir($dir_src);
		closedir($dir_dst);
		foreach ($files_src as $s) {
			$found = false;
			for ($i = 0; $i < count($files_dst); $i++)
				if ($files_dst[$i] == $s) {
					$found = true;
					array_splice($files_dst,$i,1);
					break;
				}
			if (is_dir("$src/$s")) {
				if (!$found)
					mkdir("$dst/$s");
				else if (!is_dir("$dst/$s"))
						unlink("$dst/$s");
				installDirectory("$src/$s","$dst/$s",$keep);
			} else {
				if ($found) {
					if (is_dir("$dst/$s")) remove_directory("$dst/$s");
					else unlink("$dst/$s");
				}
				copy("$src/$s","$dst/$s");
			}
		}
		foreach ($files_dst as $s) {
			if (is_dir("$dst/$s"))
				remove_directory("$dst/$s");
			else
				unlink("$dst/$s");
		}
	}
	installDirectory(realpath("maintenance/update/www"),realpath("maintenance/.."),array(realpath("data"),realpath("maintenance/update"),realpath("maintenance/password"),realpath("maintenance_in_progress")));
	
	if (PNApplication::hasErrors())
		PNApplication::printErrors();
	else {
		remove_directory(realpath("maintenance/update"));
		include("header.inc");
		echo "<div style='flex:none;background-color:white;padding:10px'>";
		echo "New version installed.<br/>";
		echo "<a href='?step=destroy_sessions'>Ok, go back to main page of maintenance mode</a>";
		echo "</div>";
		include("footer.inc");
	}
} else {
	include("header.inc");
	echo "<div style='flex:none;background-color:white;padding:10px'>";
	echo "Copying files...";
	echo "<script type='text/javascript'>location.href='?step=install&copy=true';</script>";
	echo "</div>";
	include("footer.inc");
}
?>