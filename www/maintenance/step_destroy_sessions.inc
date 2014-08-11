<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>
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
</div>
<?php include("footer.inc"); ?>
<?php 	
} else {
?>
All openend sessions have been removed.<br/>
<br/>
<button onclick="location.href='?step=back_to_normal';">Leave maintenance mode and put back the software in normal mode</button><br/>
</div>
</div>
<div class='section' style='flex:none;height:300px;width:600px;display:flex;flex-direction:column;'>
	<div class='header' style='flex:none;display:flex;flex-direction:column;justify-content:center;align-items:center;'>
		<div>Actions</div>
	</div>
	<div style='flex:1 1 auto;background-color:white;padding:10px;overflow:auto;'>
		<style type='text/css'>
		.maintenance_table {
			border: 1px solid black;
			border-collapse: collapse;
			border-spacing: 0px;
		}
		.maintenance_table th {
			border: 1px solid black;
			background-color: #22bbea;
			color:white;
		}
		.maintenance_table td {
			border: 1px solid black;
			padding: 3px;
		}
		.maintenance_table tr>td:nth-child(2), .maintenance_table tr:first-child>th:first-child, .maintenance_table tr:nth-child(2)>th:nth-child(2) {
			border-right: 2px solid black;
		}
		</style>
		<table class='maintenance_table'>
			<tr><th colspan=2>Updates</th><th colspan=4>Backups</th></tr>
			<tr>
				<th>Version</th><th></th>
				<th>Version</th><th>Date</th><th>Size</th><th></th>
			</tr>
<?php
$updates = array();
if (file_exists("data/updates")) {
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
		else $updates[$v] = null;
	}
	closedir($dir);

	foreach ($paths as $path)
		if (array_key_exists($path[1], $updates)) $updates[$path[1]] = $path[0];
}
$backups = array();
if (file_exists("data/backups")) {
	$versions = array();
	$dir = opendir("data/backups");
	while (($file = readdir($dir)) <> null) {
		if ($file == "." || $file == "..") continue;
		if (!is_dir("data/backups/$file")) continue;
		array_push($versions, $file);
	}
	closedir($dir);
	foreach ($versions as $version) {
		$dir = opendir("data/backups/$version");
		while (($file = readdir($dir)) <> null) {
			if ($file == "." || $file == "..") continue;
			if (!is_dir("data/backups/$version/$file")) continue;
			array_push($backups, array("version"=>$version,"time"=>$file));
		}
		closedir($dir);
	}
}

date_default_timezone_set("GMT");

$current_version = file_get_contents("version");

for ($i = 0; $i < count($updates) || $i < count($backups) || $i == 0; $i++) {
	echo "<tr>";
	if ($i >= count($updates)) {
		echo "<td>";
		if ($i == 0) echo "No available update";
		echo "</td>";
		echo "<td></td>";
	} else {
		$version = array_keys($updates);
		$version = $version[$i];
		$path = $updates[$version];
		echo "<td>".htmlentities($version)."</td>";
		if ($path == $current_version) {
			echo "<td>";
			echo "<form method='POST' action='?step=backup&ondone=install_test'>";
			echo "<input type='hidden' name='version' value='".$version."'/>";
			echo " <input type='submit' value='Install it!'/>";
			echo "</form>";
			echo "</td>";
		} else {
			echo "<td>";
			// TODO remove it
			echo "</td>";
		}
	}
	if ($i >= count($backups)) {
		echo "<td>";
		if ($i == 0) echo "No available backup";
		echo "</td>";
		echo "<td></td><td></td><td></td>";
	} else {
		$backup = $backups[$i];
		$version = $backup["version"];
		$time = $backup["time"];
		echo "<td>".htmlentities($version)."</td>";
		echo "<td>".date("Y-m-d H:i", intval($time))." GMT</td>";
		echo "<td>";
		$size = @filesize("data/backups/$version/$time/db.zip");
		if ($size == null) $size = 0;
		$size2 = @filesize("data/backups/$version/$time/storage.zip");
		if ($size2 == null) $size2 = 0;
		$size += $size2;
		$size2 = @filesize("data/backups/$version/$time/custom_tables.zip");
		if ($size2 == null) $size2 = 0;
		$size += $size2;
		if ($size >= 1024*1024*1024)
			echo number_format($size/(1024*1024*1024),2)."G";
		else if ($size >= 1024*1024)
			echo number_format($size/(1024*1024),2)."M";
		else if ($size >= 1024)
			echo number_format($size/(1024),2)."K";
		else
			echo $size;
		echo "</td>";
		echo "<td>";
		if (!file_exists("data/backups/$version/$time/db.zip") || 
			!file_exists("data/backups/$version/$time/storage.zip")) {
			echo "<i style='color:red'>Invalid</i>";
			echo " <button onclick=\"removeBackup('$version','$time');\">Remove</button>";
		} else {
			if ($version <> $current_version) {
				echo "<i>Different version</i>";
				echo " <button onclick=\"removeBackup('$version','$time');\">Remove</button>";
			} else {
				echo "<button onclick=\"recoverBackup('$time');\">Recover</button>";
				echo " <button onclick=\"removeBackup('$version','$time');\">Remove</button>";
			}
		}
		echo "</td>";
	}
	echo "</tr>";
}
?>
		</table>
	</div>
	<div style='flex:none;border-top:1px solid #808080;background-color:white;padding:2px 5px;'>
		<button class='action' onclick="location.href='?step=backup&ondone=destroy_sessions';">Create New Backup</button>
	</div>
	<script type='text/javascript'>
	function removeBackup(version, time) {
		if (confirm("Are you sure you want to remove this backup ?")) {
			location.href = "?step=remove_backup&version="+encodeURIComponent(version)+"&time="+time;
		}
	}
	function recoverBackup(time) {
		if (confirm("Are you sure you want to remove all actual data and recover data from this backup ?")) {
			location.href = "?step=recover&time="+time+"&ondone=destroy_sessions";
		}
	}
	</script>
<?php include("footer.inc"); ?>
<?php } ?>