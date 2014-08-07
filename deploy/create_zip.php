<?php
set_time_limit(240);

global $zip;
function zip_directory($path, $zip_path) {
	set_time_limit(240);
	global $zip;
	if ($zip_path <> "")
		$zip->addEmptyDir($zip_path);
	$dir = opendir($path);
	if (!$dir) die("Unable to access to directory ".$path);
	while (($file = readdir($dir)) <> null) {
		if ($file == "." || $file == "..") continue;
		$p = $zip_path <> "" ? $zip_path."/".$file : $file;
		if (is_dir($path."/".$file))
			zip_directory($path."/".$file, $p);
		else
			$zip->addFile($path."/".$file, $p);
	}
	closedir($dir);
}

$filename = "Students_Management_Software_".$_POST["version"].".zip";
$zip = new ZipArchive();
if ($zip->open(realpath($_POST["path"])."/to_deploy/".$filename, ZipArchive::CREATE)!==TRUE) {
	die("cannot open $filename");
}
zip_directory(realpath($_POST["path"]."/www"), "");
$zip->close();

set_time_limit(240);
// create the checksum
$f = fopen(realpath($_POST["path"])."/to_deploy/".$filename,"r");
$f2 = fopen(realpath($_POST["path"])."/to_deploy/".$filename.".checksum","w");
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
if ($zip->open(realpath($_POST["path"])."/to_deploy/".$filename_migration, ZipArchive::CREATE)!==TRUE) {
	die("cannot open $filename_migration");
}
// TODO other files
$zip->addFile(realpath($_POST["path"])."/migration/datamodel_update.php", "datamodel_update.php");
$zip->close();

// create the checksum
$f = fopen(realpath($_POST["path"])."/to_deploy/".$filename_migration,"r");
$f2 = fopen(realpath($_POST["path"])."/to_deploy/".$filename_migration.".checksum","w");
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

// create datamodel zip
$filename_datamodel = "Students_Management_Software_".$_POST["version"]."_datamodel.zip";
$zip = new ZipArchive();
if ($zip->open(realpath($_POST["path"])."/to_deploy/".$filename_datamodel, ZipArchive::CREATE)!==TRUE) {
	die("cannot open $filename_datamodel");
}
$zip->addFile(realpath($_POST["path"])."/datamodel/datamodel.json", "datamodel.json");
$zip->close();

// create init data zip
$filename_init_data = "Students_Management_Software_".$_POST["version"]."_init_data.zip";
$zip = new ZipArchive();
if ($zip->open(realpath($_POST["path"])."/to_deploy/".$filename_init_data, ZipArchive::CREATE)!==TRUE) {
	die("cannot open $filename_init_data");
}
zip_directory(realpath($_POST["path"]."/init_data"), "");
$zip->close();

// generate new versions files
$f = fopen($_POST["path"]."/to_deploy/latest.txt","w");
fwrite($f,$_POST["version"]);
fclose($f);
copy($_POST["path"]."/latest/versions.txt", $_POST["path"]."/to_deploy/versions.txt");
$f = fopen($_POST["path"]."/to_deploy/versions.txt", "a");
fwrite($f,"\n".$_POST["version"]);
fclose($f);

?>
<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>

Version ready in directory <?php echo realpath($_POST["path"]."/to_deploy");?>. You can now:<ul>
	<li>Check the migration scripts in <code><i><?php echo $filename_migration;?></i></code></li>
	<li>Put the files in SourceForge (<a href='https://sourceforge.net/projects/studentsdatabase/files/updates/' target='_blank'>https://sourceforge.net/projects/studentsdatabase/files/updates/</a>)<ul>
		<li><code><i><?php echo $filename;?></i></code></li>
		<li><code><i><?php echo $filename.".checksum";?></i></code></li>
		<li><code><i><?php echo $filename_migration;?></i></code></li>
		<li><code><i><?php echo $filename_migration.".checksum";?></i></code></li>
		<li><code><i><?php echo $filename_datamodel;?></i></code></li>
		<li><code><i><?php echo $filename_init_data;?></i></code></li>
		<li><code><i>versions.txt</i></code></li>
		<li><code><i>latest.txt</i></code></li>
	</ul></li>
</ul>

</div>
<div class='footer' style='flex:none'>
	<button class='action' onclick="location.href='/deploy/';">Restart deployment</button>
</div>
<?php include("footer.inc");?>