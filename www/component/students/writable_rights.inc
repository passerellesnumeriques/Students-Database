<?php 
return array(
	new AccessRightCategory("In PN",array(
		new BooleanRight("manage_batches", "Manage batches of students",array(true=>array("consult_students_list"=>true))),
		new BooleanRight("edit_curriculum", "Edit the subjects per academic period",array(true=>array("consult_curriculum"=>true))),
	))
);
?>