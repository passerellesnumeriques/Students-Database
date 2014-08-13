<?php
setcookie("test_deploy","",time()+365*24*60*60,"/");
setcookie(ini_get("session.name"),"",time()+365*24*60*60,"/dynamic/");
include("header.inc");
?>
<div style='flex:none;background-color:white;padding:10px'>
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
	</div>
</div>
<div class='footer' style='flex:none'>
	<button class='action' onclick="location.href='version_and_path_form.php';">Start</button>
</div>
<?php  include("footer.inc");?>