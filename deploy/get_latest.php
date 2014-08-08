<?php 
global $has_errors;
$has_errors = false;
set_error_handler(function($severity, $message, $filename, $lineno) {
	if (error_reporting() == 0) return true;
	$has_errors = true;
	return true;
});

$here = realpath(dirname(__FILE__));
$www = realpath($here."/../www");
set_include_path($here . PATH_SEPARATOR . $www);

function download($url, $file) {
	$c = curl_init($url);
	if (file_exists("$www/conf/proxy")) include("$www/conf/proxy");
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($c, CURLOPT_TIMEOUT, 5*60);
	set_time_limit(6*60);
	$result = curl_exec($c);
	if ($result == false) die("<span style='color:red'>Error downloading ".$url.": ".curl_error($c)."</span>");
	curl_close($c);
	$f = fopen($file,"w");
	fwrite($f,$result);
	fclose($f);
}
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

$latest = $_POST["latest"];
require_once("update_urls.inc");
// download datamodel
download(getUpdateURL("Students_Management_Software_".$latest."_datamodel.zip"), $_POST["path"]."/latest/datamodel.zip");
// download list of versions
download(getVersionsListURL(), $_POST["path"]."/latest/versions.txt");
// download init data
download(getUpdateURL("Students_Management_Software_".$latest."_init_data.zip"), $_POST["path"]."/latest/init_data.zip");

// unzip init data
if (file_exists($_POST["path"]."/latest/init_data"))
	remove_directory($_POST["path"]."/latest/init_data");
mkdir($_POST["path"]."/latest/init_data");
if (class_exists("ZipArchive")) {
	$zip = new ZipArchive();
	$zip->open($_POST["path"]."/latest/init_data.zip");
	$zip->extractTo($_POST["path"]."/init_data");
	$zip->close();
} else {
	$output = array();
	$ret = 0;
	exec("/usr/bin/unzip \"".$_POST["path"]."/latest/init_data.zip"."\" -d \"".$$_POST["path"]."/latest/init_data"."\"", $output, $ret);
	if ($ret <> 0)
		die("Error unzipping initial data file (".$ret.")");
}

if ($has_errors) die();
?>
<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>

Latest version downloaded.<br/>
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