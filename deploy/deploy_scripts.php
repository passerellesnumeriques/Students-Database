<?php
function scripts_directory($directory_path) {
	set_time_limit(240);
	if (file_exists($directory_path."/deploy.script.php")) {
		include($directory_path."/deploy.script.php");
		unlink($directory_path."/deploy.script.php");
	}
	$dir = opendir($directory_path);
	if (!$dir) die("Unable to access to directory ".$directory_path);
	while (($file = readdir($dir)) <> null) {
		if ($file == "." || $file == "..") continue;
		if (is_dir($directory_path."/".$file))
			scripts_directory($directory_path."/".$file);
	}
	closedir($dir);
}
scripts_directory(realpath($_POST["path"]."/www"));
?>
<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>

Deployed version of files done and scripts executed.<br/>
Preparing initial data for fresh installations...
<form name='deploy' method="POST" action="create_init_data.php">
<input type='hidden' name='version' value='<?php echo $_POST["version"];?>'/>
<input type='hidden' name='path' value='<?php echo $_POST["path"];?>'/>
<input type='hidden' name='latest' value='<?php echo $_POST["latest"];?>'/>
</form>

</div>
<script type='text/javascript'>
document.forms['deploy'].submit();
</script>
<?php include("footer.inc");?>