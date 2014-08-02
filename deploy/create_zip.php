<?php
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
		if ($file == "." || $file == ".." || $file == $filename) continue;
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
?>
<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>

Version ready: you can put the file <?php echo $_POST["path"]."/".$filename;?> in SourceForge.<br/>

</div>
<?php include("footer.inc");?>