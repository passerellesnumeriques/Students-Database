<?php
function optimize_php($path) {
	$s = file_get_contents($path);
	while (($i = strpos($s,"/*")) !== false) {
		$j = strpos($s, "*/", $i+2);
		if ($j === false) break;
		$s = substr($s, 0, $i).substr($s,$j+2);
	}
	$lines = explode("\n",$s);
	$s = "";
	$mode = null;
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line == "") continue;
		if ($line == "#DEV") {
			$mode = "DEV";
			continue;
		} else if ($line == "#PROD") {
			$mode = "PROD";
			continue;
		} else if ($line == "#END") {
			$mode = null;
			continue;
		}
		if ($mode == "DEV") continue;
		if ($mode == "PROD") {
			$line = substr($line,1); // remove the leading #
			// replace strings
			$line = str_replace("##VERSION##", $_POST["version"], $line);
		}
		
		$line = trim($line);
		if (substr($line,0,2) == "//") continue; // TODO better, but we need to know if we are in a string or not...
		
		if ($line == "") continue;
		$s .= $line."\n";
	}
	$f = fopen($path, "w");
	if (!$f) die("Unable to write in file ".$path);
	fwrite($f, $s);
	fclose($f);
}
function optimize_js($path) {
	$s = file_get_contents($path);
	while (($i = strpos($s,"/*")) !== false) {
		$j = strpos($s, "*/", $i+2);
		if ($j === false) break;
		$s = substr($s, 0, $i).substr($s,$j+2);
	}
	$lines = explode("\n",$s);
	$s = "";
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line == "") continue;
		if (substr($line,0,2) == "//") continue; // TODO better, but we need to know if we are in a string or not...
		$s .= $line."\n";
	}
	$f = fopen($path, "w");
	if (!$f) die("Unable to write in file ".$path);
	fwrite($f, $s);
	fclose($f);
}
function optimize_directory($path) {
	set_time_limit(240);
	$dir = opendir($path);
	if (!$dir) die("Unable to access to directory ".$path);
	while (($file = readdir($dir)) <> null) {
		if ($file == "." || $file == "..") continue;
		if (is_dir($path."/".$file))
			optimize_directory($path."/".$file);
		else {
			$i = strrpos($file, ".");
			if ($i === false) continue;
			$ext = substr($file, $i+1);
			switch ($ext) {
				case "inc": case "php": optimize_php($path."/".$file); break;
				case "js": optimize_js($path."/".$file); break;
			}
		}
	}
	closedir($dir);
}
optimize_directory(realpath($_POST["path"]));
$f = fopen(realpath($_POST["path"])."/version","w");
fwrite($f,$_POST["version"]);
fclose($f);
?>
<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>

Deployed version of files done.<br/>
Applying deployment scripts...
<form name='deploy' method="POST" action="deploy_scripts.php">
<input type='hidden' name='version' value='<?php echo $_POST["version"];?>'/>
<input type='hidden' name='path' value='<?php echo $_POST["path"];?>'/>
<input type='hidden' name='latest' value='<?php echo $_POST["latest"];?>'/>
</form>

</div>
<script type='text/javascript'>
document.forms['deploy'].submit();
</script>
<?php include("footer.inc");?>