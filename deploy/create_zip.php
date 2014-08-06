<?php
set_time_limit(240);
$filename = "Students_Management_Software_".$_POST["version"].".zip";
global $zip;
$zip = new ZipArchive();
if ($zip->open(realpath($_POST["path"])."/".$filename, ZipArchive::CREATE)!==TRUE) {
	die("cannot open $filename");
}

function zip_directory($path, $zip_path) {
	set_time_limit(240);
	global $zip, $filename;
	if ($zip_path <> "")
		$zip->addEmptyDir($zip_path);
	$dir = opendir($path);
	if (!$dir) die("Unable to access to directory ".$path);
	while (($file = readdir($dir)) <> null) {
		if ($file == "." || $file == ".." || $file == $filename || $file == "tmp") continue;
		$p = $zip_path <> "" ? $zip_path."/".$file : $file;
		if (is_dir($path."/".$file))
			zip_directory($path."/".$file, $p);
		else
			$zip->addFile($path."/".$file, $p);
	}
	closedir($dir);
}
zip_directory(realpath($_POST["path"]), "");

$zip->close();

set_time_limit(240);
// create the checksum
$f = fopen(realpath($_POST["path"])."/".$filename,"r");
$f2 = fopen(realpath($_POST["path"])."/".$filename.".checksum","w");
do {
	$s = fread($f, 1024);
	while (strlen($s) < 1024) {
		$s2 = fread($f, 1024-strlen($s));
		if (strlen($s2) == 0) break;
		$s .= $s2;
	} 
	if (strlen($s) == 0) break;
	$bytes = unpack("C*",$s);
	$cs = 0;
	for ($i = 1; $i <= count($bytes); $i++) $cs += $bytes[$i];
	$cs %= 256;
	$byte = pack("C",$cs);
	fwrite($f2, $byte);
} while (true);
fclose($f);
fclose($f2);

set_time_limit(240);
$filename_migration = "Students_Management_Software_".$_POST["latest"]."_to_".$_POST["version"].".zip";
global $zip;
$zip = new ZipArchive();
if ($zip->open(realpath($_POST["path"])."/".$filename_migration, ZipArchive::CREATE)!==TRUE) {
	die("cannot open $filename_migration");
}
$zip->addFile(realpath($_POST["path"])."/tmp/datamodel_update.php", "datamodel_update.php");
$zip->close();

// create the checksum
$f = fopen(realpath($_POST["path"])."/".$filename_migration,"r");
$f2 = fopen(realpath($_POST["path"])."/".$filename_migration.".checksum","w");
do {
	$s = fread($f, 1024);
	while (strlen($s) < 1024) {
		$s2 = fread($f, 1024-strlen($s));
		if (strlen($s2) == 0) break;
		$s .= $s2;
	}
	if (strlen($s) == 0) break;
	$bytes = unpack("C*",$s);
	$cs = 0;
	for ($i = 1; $i <= count($bytes); $i++) $cs += $bytes[$i];
	$cs %= 256;
	$byte = pack("C",$cs);
	fwrite($f2, $byte);
} while (true);
fclose($f);
fclose($f2);

?>
<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>

Version ready in directory <?php echo $_POST["path"];?>. You can now:<ul>
	<li>Check the migration scripts in <code><i><?php echo $filename_migration;?></i></code></li>
	<li>Put the files in SourceForge (<a href='https://sourceforge.net/projects/studentsdatabase/files/updates/' target='_blank'>https://sourceforge.net/projects/studentsdatabase/files/updates/</a>)<ul>
		<li><code><i><?php echo $filename;?></i></code></li>
		<li><code><i><?php echo $filename.".checksum";?></i></code></li>
		<li><code><i><?php echo $filename_migration;?></i></code></li>
		<li><code><i><?php echo $filename_migration.".checksum";?></i></code></li>
	</ul></li>
	<li>Update the files in SourceForge to signal the new version (<a href='https://sourceforge.net/projects/studentsdatabase/files/' target='_blank'>https://sourceforge.net/projects/studentsdatabase/files/</a>)<ul>
		<li><code><i>versions.txt</i></code></li>
		<li><code><i>latest.txt</i></code></li>
	</ul></li>
</ul>

</div>
<div class='footer' style='flex:none'>
	<button class='action' onclick="location.href='/deploy/';">Restart deployment</button>
</div>
<?php include("footer.inc");?>