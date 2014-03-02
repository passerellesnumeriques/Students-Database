<?php
return array(
	new AccessRightCategory("In PN",array(
		new BooleanRight("consult_students_list", "See the list of students and batches",array(true=>array("consult_curriculum"=>true))),
	))
);
?>