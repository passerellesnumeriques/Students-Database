<?php 
$sessions_path = ini_get("session.save_path");
$i = strrpos($sessions_path, ";");
if ($i !== false) $sessions_path = substr($sessions_path, $i+1);
$sessions_path = realpath($sessions_path);
$dir = opendir($sessions_path);
$remaining = 0;
while (($filename = readdir($dir)) <> null) {
	if (is_dir($sessions_path."/".$filename)) continue;
	$content = @file_get_contents($sessions_path."/".$filename);
	if (strpos($content,"PNApplication") !== false) {
		@unlink($sessions_path."/".$filename);
		if (file_exists($sessions_path."/".$filename)) $remaining++;
	}
}
closedir($dir);
if ($remaining > 0) {
	echo $remaining;
?>
 session(s) still open. Please wait...
<script type='text/javascript'>
setTimeout(function() { location.reload(); },1000);
</script>
<?php 	
} else {
?>
All openend sessions have been removed.<br/>
<br/>
<button onclick="location.href='?step=back_to_normal';">Leave maintenance mode and put back the software in normal mode</button>
<br/>
<br/>
<?php
if (file_exists("data/updates")) {
	global $pn_app_version;
	$versions = array();
	$paths = array();
	
	$dir = opendir("data/updates");
	while (($filename = readdir($dir)) <> null) {
		if (is_dir("data/updates/$filename")) continue;
		$i = strrpos($filename, ".");
		if ($i === false) continue;
		$ext = substr($filename, $i+1);
		if ($ext <> "zip") continue;
		$name = substr($filename, 0, $i);
		if (substr($name,0,29) <> "Students_Management_Software_") continue;
		$v = substr($name,29);
		$i = strpos($v, "_to_");
		if ($i !== false) array_push($paths, array(substr($v,0,$i),substr($v,$i+4)));
		else array_push($versions, $v);
	}
	closedir($dir);

	function find_updates($versions, $paths, $migrated_to, $current_path, &$updates) {
		foreach ($paths as $path) {
			if ($path[0] == $migrated_to) {
				// we have a way to migrate to a new version
				foreach ($versions as $ver) if ($ver == $path[1]) { $updates[$ver] = array_merge($current_path, array($ver)); break; }
				find_updates($versions, $paths, $path[1], array_merge($current_path, array($ver)), $updates);
			}
		}
	}
	$updates = array();
	find_updates($versions, $paths, $pn_app_version, array($pn_app_version), $updates);
	echo count($updates)." version(s) available to install:<ul>";
	foreach ($updates as $version=>$path) {
		echo "<li>";
		echo htmlentities($version);
		echo "<form method='POST' action='?step=backup'>";
		echo "<input type='hidden' name='version' value='".$version."'/>";
		echo "<input type='hidden' name='path' value='".json_encode($path)."'/>";
		echo " <input type='submit' value='Install it!'/>";
		echo "</form>";
		echo "</li>";
	}
	echo "</ul>";
}

?>
<?php 	
}
