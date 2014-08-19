<?php 
$version = $_GET["version"];

$dir = opendir("data/updates");
while (($filename = readdir($dir)) <> null) {
	if (is_dir("data/updates/$filename")) continue;
	$i = strrpos($filename, ".");
	if ($i === false) continue;
	$ext = substr($filename, $i+1);
	if ($ext == "checksum") {
		$ext = substr($filename, 0, $i);
		$i = strrpos($ext, ".");
		if ($i !== false) $ext = substr($ext, $i+1);
	}
	if ($ext <> "zip") continue;
	$name = substr($filename, 0, $i);
	if (substr($name,0,29) <> "Students_Management_Software_") continue;
	$v = substr($name,29);
	$i = strpos($v, "_to_");
	if ($i === false) {
		if ($v == $version) unlink("data/updates/$filename");
	} else {
		$v = substr($v,$i+4);
		if ($v == $version) unlink("data/updates/$filename");
	}
	if ($i !== false) array_push($paths, array(substr($v,0,$i),substr($v,$i+4)));
	else $updates[$v] = null;
}
closedir($dir);

header("Location: /maintenance?step=destroy_sessions");
?>