<?php 
global $deploy_version;
if ($deploy_version == "selection_travel")
	unlink(dirname(__FILE__)."/setup.exe");
?>