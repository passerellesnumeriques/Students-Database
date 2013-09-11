<?php 
return array(
	new AccessRightCategory("Personal Information",array(
		new BooleanRight("edit_people_details","Edit Personal Information",array(true=>array("see_other_people_details"=>true))),
	)),
);
?>