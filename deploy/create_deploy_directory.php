<?php 
global $has_errors;
$has_errors = false;
set_error_handler(function($severity, $message, $filename, $lineno) {
	if (error_reporting() == 0) return true;
	$has_errors = true;
	return true;
});

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

if (file_exists($_POST["path"]))
	remove_directory($_POST["path"]);
if (file_exists($_POST["path"])) die("Unable to remove directory ".$_POST["path"]);

if (!@mkdir($_POST["path"]))
	if (!mkdir($_POST["path"])) die("Unable to create directory ".$_POST["path"]);
if (!file_exists($_POST["path"])) die("Unable to create directory ".$_POST["path"]);

mkdir($_POST["path"]."/latest"); // here we will download information about latest version
mkdir($_POST["path"]."/www"); // here we will prepare the new version
mkdir($_POST["path"]."/to_deploy"); // here we will generate zip files and checksums
mkdir($_POST["path"]."/init_data"); // here we will prepare init data
mkdir($_POST["path"]."/datamodel"); // here we will prepare new datamodel
mkdir($_POST["path"]."/migration"); // here we will prepare migration scripts

if ($has_errors) die();
?>
<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>

Directory created.<br/>
Downloading information about latest version (<?php echo $_POST["latest"];?>)...
<form name='deploy' method="POST" action="get_latest.php">
<input type='hidden' name='version' value='<?php echo $_POST["version"];?>'/>
<input type='hidden' name='path' value='<?php echo $_POST["path"];?>'/>
<input type='hidden' name='latest' value='<?php echo $_POST["latest"];?>'/>
</form>

</div>
<script type='text/javascript'>
document.forms['deploy'].submit();
</script>
<?php include("footer.inc");?>