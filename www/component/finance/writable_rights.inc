<?php 
return array(
	new AccessRightCategory("In PN",array(
		new BooleanRight("edit_student_finance", "Edit finance information of students",array(true=>array("consult_student_finance"=>true))),
		new BooleanRight("manage_finance", "Manage finance (Create/Edit/Remove general payments for batches of students)",array(true=>array("edit_student_finance"=>true))),
	))
);
?>