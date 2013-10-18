<?php 
return array(
	new AccessRightCategory("Information Session",array(
		new BooleanRight("manage_information_session","Manage Information Session Details",array(true=>array("edit_information_session"=>true))),
		new BooleanRight("edit_information_session","Edit Information Session Details"),
		new BooleanRight("manage_selection_campaign","Edit, Add or Remove a selection campaign",array(true=>array("manage_information_session"=>true))),
	)),
);
?>