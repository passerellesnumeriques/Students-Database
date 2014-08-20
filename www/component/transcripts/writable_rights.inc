<?php 
return array(
	new AccessRightCategory("In PN",array(
		new BooleanRight("edit_students_grades", "Edit the grades of the students",array(true=>array("consult_students_grades"=>true))),
		new BooleanRight("edit_transcripts_design", "Edit the design of the transcripts, and publish them",array(true=>array("consult_students_grades"=>true))),
	))
);
?>