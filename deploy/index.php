<?php
setcookie("test_deploy","",time()+365*24*60*60,"/");
setcookie(ini_get("session.name"),"",time()+365*24*60*60,"/dynamic/");
include("header.inc");
?>
<div style='flex:none;background-color:white;padding:10px' id='content'>
	<div style='font-size:14pt;padding-bottom:5px;border-bottom: 1px solid #808080;'>
		Welcome in this wizard, that will guide you to build a <i>deployed version</i>
	</div>
	<div style='margin-top:10px;'>
		The creation of the <i>deployed version</i> will be done in several steps:<ol>
			<li>Retrieve information about the latest deployed version</li>
			<li>Compare the data model with previous version, to help you creating a <i>migration script</i> for the data model</li>
			<li>Create the deployed version, by copying files and optimizing them a bit to improve performance</li>
			<li>Create the zip files to put in SourceForge</li>
		</ol>
		For which channel are you going to deploy a new version ?<br/>
		<form name='channel_choice' onsubmit='return false;'>
		<input type='radio' name='channel' value='stable' checked='checked' id='stable'/> Stable<br/>
		<input type='radio' name='channel' value='beta' id='beta'/> Beta<br/>
		</form>
	</div>
</div>
<div class='footer' style='flex:none' id='footer'>
	<button class='action' onclick="go();">Start</button>
</div>
<script type='text/javascript'>
function go() {
	var channel = document.getElementById('stable').checked ? 'stable' : 'beta';
	document.getElementById('content').innerHTML = "Retrieving latest deployed version...";
	document.getElementById('footer').style.display = 'none';
	location.href='version_and_path_form.php?channel='+channel;
}
</script>
<?php  include("footer.inc");?>