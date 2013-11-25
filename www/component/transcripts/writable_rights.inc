<?php 
return array(
	new AccessRightCategory("In PN",array(
		new BooleanRight("edit_students_grades", "Edit the grades of the students",array(true=>array("consult_students_grades"=>true))),
	))
);
?>