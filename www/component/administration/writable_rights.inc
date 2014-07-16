<?php 
return array(
	new AccessRightCategory("Administration",array(
		new BooleanRight("manage_application", "Manage application settings, deploy new version..."),
	))
);
?>