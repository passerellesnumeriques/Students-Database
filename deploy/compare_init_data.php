<?php
global $has_errors;
$has_errors = false;
set_error_handler(function($severity, $message, $filename, $lineno) {
	if (error_reporting() == 0) return true;
	$has_errors = true;
	return true;
});

$previous_path = realpath($_POST["path"])."/latest/init_data";
$new_path = realpath($_POST["path"])."/init_data";

$previous_files = array();
$new_files = array();

function searchFiles($path, $rel, &$files) {
	$dir = opendir($path);
	while (($file = readdir($dir)) <> null) {
		if ($file == "." || $file == "..") continue;
		if (is_dir($path."/".$file))
			searchFiles($path."/".$file, $rel.$file."/", $files);
		else 
			array_push($files, $rel.$file);
	}
	closedir($dir);
}

set_time_limit(60);
searchFiles($previous_path, "/", $previous_files);
searchFiles($new_path, "/", $new_files);

$change = array();
$remove = array();
for ($i = 0; $i < count($previous_files); $i++) {
	set_time_limit(60);
	$file = $previous_files[$i];
	$found = false;
	for ($j = 0; $j < count($new_files); ++$j)
		if ($new_files[$j] == $file) { 
			$found = true;
			array_splice($new_files,$j,1);
			break;
		}
	if (!$found) { array_push($remove, $file); continue; }
	$s1 = file_get_contents($previous_path.$file);
	$s2 = file_get_contents($new_path.$file);
	if ($s1 <> $s2) array_push($change, $file);
}
$add = $new_files;

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

global $migration;
$migration = array();
function checkMigrationScripts($component) {
	global $migration;
	if (!file_exists(realpath($_POST["path"])."/www/component/$component/updates")) return;
	$path = realpath($_POST["path"])."/www/component/$component/updates/".$_POST["version"];
	if (file_exists($path)) {
		if (file_exists(realpath($_POST["path"])."/www/component/$component/updates/".$_POST["version"]."/before_datamodel.php") || file_exists(realpath($_POST["path"])."/www/component/$component/updates/".$_POST["version"]."/after_datamodel.php")) {
			$migration[$component] = array("","");
			if (file_exists(realpath($_POST["path"])."/www/component/$component/updates/".$_POST["version"]."/before_datamodel.php"))
				$migration[$component][0] = file_get_contents(realpath($_POST["path"])."/www/component/$component/updates/".$_POST["version"]."/before_datamodel.php");
			if (file_exists(realpath($_POST["path"])."/www/component/$component/updates/".$_POST["version"]."/after_datamodel.php"))
				$migration[$component][1] = file_get_contents(realpath($_POST["path"])."/www/component/$component/updates/".$_POST["version"]."/after_datamodel.php");
		}
	}
	remove_directory(realpath($_POST["path"])."/www/component/$component/updates");
}
$dir = opendir(realpath($_POST["path"])."/www/component");
while (($file = readdir($dir)) <> null) {
	if ($file == "." || $file == "..") continue;
	if (!is_dir(realpath($_POST["path"])."/www/component/$file")) continue;
	checkMigrationScripts($file);
}
closedir($dir);

$before = "";
$after = "";
foreach ($migration as $comp=>$content) {
	$before .= $content[0];
	$after .= $content[1];
}
if ($before <> "") {
	$f = fopen(realpath($_POST["path"])."/migration/before_datamodel.php","w");
	fwrite($f,$before);
	fclose($f);
}
if ($after <> "") {
	$f = fopen(realpath($_POST["path"])."/migration/after_datamodel.php","w");
	fwrite($f,$after);
	fclose($f);
}

if ($has_errors) die();

include("header.inc");
?>
<div style='flex:none;background-color:white;padding:10px'>
<form name='deploy' method="POST" action="create_zip.php" style='display:none'>
<input type='hidden' name='version' value='<?php echo $_POST["version"];?>'/>
<input type='hidden' name='path' value='<?php echo $_POST["path"];?>'/>
<input type='hidden' name='latest' value='<?php echo $_POST["latest"];?>'/>
</form>
<?php 
if (count($migration) > 0) {
	echo "We found migration scripts in the following components:<ul>";
	foreach ($migration as $comp=>$content) {
		echo "<li>".$comp."</li>";
	}
	echo "</ul>";
}
if (count($change) == 0 && count($add) == 0 && count($remove) == 0) {
	// no change
	?>
	No change in the initial data.<br/>
	Generating zip files and checksums...
	</div>
	<script type='text/javascript'>
	document.forms['deploy'].submit();
	</script>
<?php 
	include("footer.inc");
	return;
}
if (count($change) > 0) {
	echo "The following files changed:<ul>";
	foreach ($change as $file) echo "<li>".htmlentities($file)."</li>";
	echo "</ul>";
}
if (count($add) > 0) {
	echo "The following files have been added:<ul>";
	foreach ($add as $file) echo "<li>".htmlentities($file)."</li>";
	echo "</ul>";
}
if (count($remove) > 0) {
	echo "The following files have been removed:<ul>";
	foreach ($remove as $file) echo "<li>".htmlentities($file)."</li>";
	echo "</ul>";
}
?>
Are you sure you made the necessary migration scripts ?<br/>
<button onclick="this.parentNode.insertBefore(document.createTextNode('Generating zip files and checksums...'),this);this.parentNode.removeChild(this);document.forms['deploy'].submit();">Yes</button>
</div>
<?php include("footer.inc"); ?>