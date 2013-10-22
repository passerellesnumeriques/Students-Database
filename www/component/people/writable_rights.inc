<?php 
return array(
	new AccessRightCategory("Personal Information",array(
		new BooleanRight("edit_people_details","Edit Personal Information",array(true=>array("see_other_people_details"=>true))),
		new BooleanRight("create_people","Create people",array(true=>array("see_other_people_details"=>true,"edit_people_details"=>true))),
		new BooleanRight("remove_people","Remove people",array(true=>array("see_other_people_details"=>true,"edit_people_details"=>true))),
	)),
);
?>