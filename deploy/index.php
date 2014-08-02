<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>

<form name='deploy' method="POST" action="start_deploy.php">

Current development version: <?php echo file_get_contents(dirname(__FILE__)."/../www/version");?><br/>

Latest deployed version: <?php 
$url = "http://sourceforge.net/projects/studentsdatabase/files/latest.txt/download";
$c = curl_init($url);
curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
$result = curl_exec($c);
if ($result == false) echo "Error: ".curl_error($c);
else echo $result;
curl_close($c);
?><br/>
<br/>
Enter the new version to deploy: <input type='text' name='version' required/><br/>
<br/>
Enter the location where to generate the deployed version:<br/>
<input type='text' name='path' size=50 required/>

</form>
</div>
<div class='footer' style='flex:none'>
	<button class='action' onclick="document.forms['deploy'].submit();">Start deployment</button>
</div>
<?php include("footer.inc");?>