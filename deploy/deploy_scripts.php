<?php
function scripts_directory($path) {
	set_time_limit(240);
	if (file_exists($path."/deploy.script.php")) {
		include($path."/deploy.script.php");
		unlink($path."/deploy.script.php");
	}
	$dir = opendir($path);
	if (!$dir) die("Unable to access to directory ".$path);
	while (($file = readdir($dir)) <> null) {
		if ($file == "." || $file == "..") continue;
		if (is_dir($path."/".$file))
			scripts_directory($path."/".$file);
	}
	closedir($dir);
}
scripts_directory(realpath($_POST["path"]));
?>
<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>

Deployed version of files done and scripts executed.<br/>
Generating zip file...
<form name='deploy' method="POST" action="create_zip.php">
<input type='hidden' name='version' value='<?php echo $_POST["version"];?>'/>
<input type='hidden' name='path' value='<?php echo $_POST["path"];?>'/>
</form>

</div>
<script type='text/javascript'>
document.forms['deploy'].submit();
</script>
<?php include("footer.inc");?>