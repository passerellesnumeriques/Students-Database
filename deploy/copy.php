<?php 
global $has_errors;
$has_errors = false;
set_error_handler(function($severity, $message, $filename, $lineno) {
	if (error_reporting() == 0) return true;
	$has_errors = true;
	return true;
});

function copy_directory($src, $dst) {
	set_time_limit(240);
	$exclude = array(".","..","deploy.files",".gitignore");
	$include = null;
	if (file_exists($src."/deploy.files")) {
		$content = file_get_contents($src."/deploy.files");
		$lines = explode("\n",$content);
		foreach ($lines as $line) {
			$line = trim($line);
			$i = strpos($line,":");
			if ($i === false) continue;
			$directive = substr($line, 0, $i);
			$list = explode(",",substr($line,$i+1));
			if ($directive == "exclude")
				$exclude = array_merge($exclude,$list);
			else if ($directive == "include") {
				if ($include == null) $include = $list;
				else $include = array_merge($include,$list);
			} 
		}
	}
	$dir = opendir($src);
	if (!$dir) die("Unable to access to directory ".$src);
	while (($file = readdir($dir)) <> null) {
		if ($include <> null && !in_array($file,$include)) continue;
		if (in_array($file,$exclude)) continue;
		if (strpos($src,"/page/") !== false || strpos($src,"/service/") !== false || strpos($src,"/static/") !== false) {
			if (strpos($file,".inc") === false) {
				if (strtolower($file) <> $file) {
					die("Error: file $src/$file contains capital letters, but it is supposed to be accessed by URL, meaning no capital letter are allowed. This will not work under Unix systems which have case sensitive file systems.");
				}
			}
		}
		if (is_dir($src."/".$file)) {
			if (!mkdir($dst."/".$file)) die("Unable to create directory ".$dst."/".$file);
			copy_directory($src."/".$file, $dst."/".$file);
		} else {
			if (!copy($src."/".$file, $dst."/".$file)) die("Unable to copy file ".$src."/".$file);
		}
	}
	closedir($dir);
}
copy_directory(realpath(dirname(__FILE__)."/../www"), realpath($_POST["path"]."/www"));
copy_directory(realpath(dirname(__FILE__)."/../www"), realpath($_POST["path"]."/www_selection_travel"));
mkdir(realpath($_POST["path"]."/www_selection_travel")."/server_comm");
copy_directory(realpath(dirname(__FILE__)."/../selection_travel/server_comm"), realpath($_POST["path"]."/www_selection_travel/server_comm"));

if ($has_errors) die();
?>
<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>

Files copied.<br/>
Optimizing and creating deployed version of the files...
<form name='deploy' method="POST" action="optimize.php">
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