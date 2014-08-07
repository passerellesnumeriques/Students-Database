<?php 
$here = realpath(dirname(__FILE__));
$www = realpath($here."/../www");
set_include_path($here . PATH_SEPARATOR . $www);

function download($url, $file) {
	$c = curl_init($url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($c, CURLOPT_TIMEOUT, 25);
	set_time_limit(45);
	$result = curl_exec($c);
	if ($result == false) die("<span style='color:red'>Error downloading ".$url.": ".curl_error($c)."</span>");
	curl_close($c);
	$f = fopen($file,"w");
	fwrite($f,$result);
	fclose($f);
}

$latest = $_POST["latest"];
require_once("update_urls.inc");
// download datamodel
download(getUpdateURL("Students_Management_Software_".$latest."_datamodel.zip"), $_POST["path"]."/latest/datamodel.zip");
// download list of versions
download(getVersionsListURL(), $_POST["path"]."/latest/versions.txt");

@unlink($_POST["path"]."/latest/datamodel.json");
if (class_exists("ZipArchive")) {
	$zip = new ZipArchive();
	$zip->open($_POST["path"]."/latest/datamodel.zip");
	$zip->extractTo($_POST["path"]."/tmp");
	$zip->close();
} else {
	$output = array();
	$ret = 0;
	exec("/usr/bin/unzip \"".$_POST["path"]."/latest/datamodel.zip"."\" -d \"".$$_POST["path"]."/latest"."\"", $output, $ret);
	if ($ret <> 0)
		die("Error unzipping datamodel file (".$ret.")");
}

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