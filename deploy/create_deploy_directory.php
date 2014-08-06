<?php 
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
mkdir($_POST["path"]."/tmp");

?>
<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>

Directory created.<br/>
Retrieving datamodel information...
<form name='deploy' method="POST" action="datamodel.php">
<input type='hidden' name='version' value='<?php echo $_POST["version"];?>'/>
<input type='hidden' name='path' value='<?php echo $_POST["path"];?>'/>
<input type='hidden' name='latest' value='<?php echo $_POST["latest"];?>'/>
</form>

</div>
<script type='text/javascript'>
document.forms['deploy'].submit();
</script>
<?php include("footer.inc");?>