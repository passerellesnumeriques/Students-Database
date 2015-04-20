<?php 
global $has_errors;
$has_errors = false;
set_error_handler(function($severity, $message, $filename, $lineno) {
	if (error_reporting() == 0) return true;
	$has_errors = true;
	return true;
});

global $www;
$here = realpath(dirname(__FILE__));
$www = realpath($here."/../www");
set_include_path($here . PATH_SEPARATOR . $www);

function download($url, $file) {
	global $www;
	require_once "$www/HTTPClient.inc";
	$c = new HTTPClient();
	$c->setProxyConfLocation("$www/conf/proxy");
	$req = new HTTPRequest();
	$req->setURL($url);
	try {
		$responses = $c->send($req);
		$resp = $responses[count($responses)-1];
		if ($resp->getStatus() < 200 || $resp->getStatus() >= 300)
			throw new Exception("Server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
	} catch (Exception $e) {
		die("<span style='color:red'>Error downloading ".$url.": ".toHTML($e->getMessage())."</span>");
	}
	$f = fopen($file,"w");
	fwrite($f,$resp->getBody());
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

function getVersionsListURL() {
	global $www;
	$s = file_get_contents("$www/conf/update_urls");
	$channel = $_POST["channel"];
	$s = str_replace("##CHANNEL##",$channel,$s);
	$lines = explode("\n",$s);
	foreach ($lines as $line) {
		if (substr($line,0,9) == "versions=")
			return trim(substr($line,9));
	}
	return null;
}
function getUpdateURL($filename) {
	global $www;
	$s = file_get_contents("$www/conf/update_urls");
	$channel = $_POST["channel"];
	$s = str_replace("##CHANNEL##",$channel,$s);
	$lines = explode("\n",$s);
	foreach ($lines as $line) {
		if (substr($line,0,7) == "update=")
			return str_replace("##FILE##", $filename, trim(substr($line,7)));
	}
	return null;
}
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
	$zip->extractTo($_POST["path"]."/latest/init_data");
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
<input type='hidden' name='channel' value='<?php echo $_POST["channel"];?>'/>
</form>

</div>
<script type='text/javascript'>
document.forms['deploy'].submit();
</script>
<?php include("footer.inc");?>