<?php 
return array(
	new AccessRightCategory("Contact Informations",array(
		new BooleanRight("can_access_selection_data","Right that must be valid to read any data about selection"),
		new BooleanRight("see_information_session_details","See Information Session Details",array(true=>array("can_access_selection_data"=>true))),
	)),
	
);
?>