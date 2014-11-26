<?php
global $has_errors;
$has_errors = false;
set_error_handler(function($severity, $message, $filename, $lineno) {
	if (error_reporting() == 0) return true;
	$has_errors = true;
	return true;
});

$path = $_POST["path"]."/www/component";
$dir = opendir($path);
while (($file = readdir($dir)) <> null) {
	if ($file == "." || $file == "..") continue;
	if (!is_dir($path."/".$file)) continue;
	if (file_exists("$path/$file/init_data.inc")) {
		mkdir($_POST["path"]."/init_data/$file");
		rename("$path/$file/init_data.inc", $_POST["path"]."/init_data/$file/init_data.inc");
		if (file_exists("$path/$file/data") && is_dir("$path/$file/data")) {
			rename("$path/$file/data", $_POST["path"]."/init_data/$file/data");
		}
	}
}
closedir($dir);

if ($has_errors) die();
?>
<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>

Initial data prepared.<br/>
Analyzing initial data changes, and integrate migration scripts...
<form name='deploy' method="POST" action="compare_init_data.php">
<input type='hidden' name='version' value='<?php echo $_POST["version"];?>'/>
<input type='hidden' name='path' value='<?php echo $_POST["path"];?>'/>
<input type='hidden' name='latest' value='<?php echo $_POST["latest"];?>'/>
<input type='hidden' name='channel' value='<?php echo $_POST["channel"];?>'/>
</form>

</div>
<script type='text/javascript'>
document.forms['deploy'].submit();
</script>
<?php include("footer.inc");?>