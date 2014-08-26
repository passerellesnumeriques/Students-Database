<?php
$current_version = file_get_contents(dirname(__FILE__)."/../www/version");
setcookie("pnversion",$current_version,time()+365*24*60*60,"/"); 
include("header.inc");
?>
<div style='flex:none;background-color:white;padding:10px'>

<form name='deploy' method="POST" action="start_deploy.php">

Current development version: <?php echo $current_version;?><br/>

Latest deployed version: <?php 
$here = realpath(dirname(__FILE__));
$www = realpath($here."/../www");
set_include_path($here . PATH_SEPARATOR . $www);
require_once("update_urls.inc");
$url = getLatestVersionURL();
$c = curl_init($url);
if (file_exists("$www/conf/proxy")) include("$www/conf/proxy");
curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
curl_setopt($c, CURLOPT_TIMEOUT, 25);
set_time_limit(90);
$result = curl_exec($c);
if ($result == false) die("<span style='color:red'>Error downloading ".$url.": ".curl_error($c)."</span>");
echo $result;
curl_close($c);
?>
<input type='hidden' name='latest' value='<?php echo $result;?>'/>
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