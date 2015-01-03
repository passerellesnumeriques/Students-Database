<?php
return array(
	new AccessRightCategory("In PN",array(
		new BooleanRight("consult_student_finance", "See finance/balance of students", array(true=>array("consult_students_list"=>true)))
	))
);
?>