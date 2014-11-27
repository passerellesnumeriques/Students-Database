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
			if ($mode <> null) die("Found tag #DEV while still in #".$mode." in ".$path);
			$mode = "DEV";
			continue;
		} else if ($line == "#PROD") {
			if ($mode <> null) die("Found tag #PROD while still in #".$mode." in ".$path);
			$mode = "PROD";
			continue;
		} else if ($line == "#CHANNEL_STABLE") {
			if ($mode <> null) die("Found tag #CHANNEL_STABLE while still in #".$mode." in ".$path);
			$mode = "CHANNEL_STABLE";
			continue;
		} else if ($line == "#CHANNEL_BETA") {
			if ($mode <> null) die("Found tag #CHANNEL_BETA while still in #".$mode." in ".$path);
			$mode = "CHANNEL_BETA";
			continue;
		} else if ($line == "#END") {
			if ($mode == null) die("Found tag #END without opening #DEV or #PROD in ".$path);
			$mode = null;
			continue;
		}
		if ($mode == "DEV") continue;
		if ($mode == "PROD" || ($mode == "CHANNEL_STABLE" && $_POST["channel"] == "stable") || ($mode == "CHANNEL_BETA" && $_POST["channel"] == "beta")) {
			if (substr($line,0,1) <> "#") die("Lines inside #".$mode." must start with a # in ".$path.": ".$line);
			$line = substr($line,1); // remove the leading #
			// replace strings
			$line = str_replace("##VERSION##", $_POST["version"], $line);
		}
		
		$line = trim($line);
		if (substr($line,0,2) == "//") continue;
		if ($line == "") continue;
		$s .= $line."\n";
	}
	if ($mode <> null) {
		die("Missing end of tag #".$mode." in ".$path);
	}
	$f = fopen($path, "w");
	if (!$f) die("Unable to write in file ".$path);
	fwrite($f, $s);
	fclose($f);
}
function optimize_js($path) {
	$s = file_get_contents($path);
	$i = 0;
	while (($i = strpos($s,"/*",$i)) !== false) {
		$j = strpos($s, "*/", $i+2);
		if ($j === false) break;
		$comment = substr($s, $i+2, $j-$i-2);
		if (strpos($comment, "#depends") !== false) {
			$i = $j+2;
			continue;
		}
		$s = substr($s, 0, $i).substr($s,$j+2);
	}
	$lines = explode("\n",$s);
	$s = "";
	foreach ($lines as $line) {
		$line = trim($line);
		if (substr($line,0,2) == "//" && strpos($line,"#depends") === false) continue; // TODO better, but we need to know if we are in a string or not...
		if ($line == "") continue;
		$s .= $line."\n";
	}
	$f = fopen($path, "w");
	if (!$f) die("Unable to write in file ".$path);
	fwrite($f, $s);
	fclose($f);
}
function optimize_sql($path) {
	$s = file_get_contents($path);
	$lines = explode("\n",$s);
	$s = "";
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line == "") continue;
		$s .= $line."\n";
	}
	$f = fopen($path, "w");
	if (!$f) die("Unable to write in file ".$path);
	fwrite($f, $s);
	fclose($f);
}
function optimize_directory($path) {
	$dir = opendir($path);
	if (!$dir) die("Unable to access to directory ".$path);
	while (($file = readdir($dir)) <> null) {
		if ($file == "." || $file == "..") continue;
		set_time_limit(300);
		if (is_dir($path."/".$file)) {
			if (substr($file,0,4) == "lib_") continue;
			optimize_directory($path."/".$file);
		} else {
			$i = strrpos($file, ".");
			if ($i === false) continue;
			$ext = substr($file, $i+1);
			switch ($ext) {
				case "inc": case "php": optimize_php($path."/".$file); break;
				case "js": optimize_js($path."/".$file); break;
				case "sql": optimize_sql($path."/".$file); break;
			}
		}
	}
	closedir($dir);
}
optimize_directory(realpath($_POST["path"]."/www"));
$f = fopen(realpath($_POST["path"])."/www/version","w");
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
<input type='hidden' name='channel' value='<?php echo $_POST["channel"];?>'/>
</form>

</div>
<script type='text/javascript'>
document.forms['deploy'].submit();
</script>
<?php include("footer.inc");?>