<?php
return array(
	new AccessRightCategory("In PN",array(
		new BooleanRight("consult_students_grades", "See the grades of students", array(true=>array("consult_students_list"=>true))),
	))
);
?>