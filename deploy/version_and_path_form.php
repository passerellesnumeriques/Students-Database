<?php
$current_version = file_get_contents(dirname(__FILE__)."/../www/version");
setcookie("pnversion",$current_version,time()+365*24*60*60,"/"); 
include("header.inc");
?>
<div style='flex:none;background-color:white;padding:10px'>

<form name='deploy' method="POST" action="start_deploy.php">

Current development version: <?php echo $current_version;?><br/>

Latest deployed version: <?php 
global $www;
$here = realpath(dirname(__FILE__));
$www = realpath($here."/../www");
set_include_path($here . PATH_SEPARATOR . $www);
function getVersionsListURL() {
	global $www;
	$s = file_get_contents("$www/conf/update_urls");
	$channel = file_get_contents("$www/conf/channel");
	$s = str_replace("##CHANNEL##",$_GET["channel"],$s);
	$lines = explode("\n",$s);
	foreach ($lines as $line) {
		if (substr($line,0,9) == "versions=")
			return trim(substr($line,9));
	}
	return null;
}

$url = getVersionsListURL();
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
$versions = explode("\n",$resp->getBody());
echo $versions[count($versions)-1];
$current_index = array_search($current_version, $versions);
?>
<br/>Use version
<select name='latest'>
<?php
for ($i = 0; $i < count($versions); $i++) {
	echo "<option value='".$versions[$i]."'";
	if ($current_index === $i+1 || ($current_index === false && $i == count($versions)-1)) echo " selected='selected'";
	echo ">".$versions[$i]."</option>";
} 
?>
</select> 
as the previous one
<input type='hidden' name='channel' value='<?php echo $_GET["channel"];?>'/>
<br/>
<br/>
Enter the new version to deploy: <input type='text' name='version' value='<?php echo $current_version;?>'/><br/>
<br/>
Enter the location where to generate the deployed version:<br/>
<input type='text' name='path' size=50/>

</form>
</div>
<div class='footer' style='flex:none'>
	<button class='action' onclick="document.forms['deploy'].submit();">Start deployment</button>
</div>
<?php include("footer.inc");?>