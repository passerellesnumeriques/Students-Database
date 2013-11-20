<?php
return array(
	new AccessRightCategory("In PN",array(
		new BooleanRight("consult_students_list", "See the list of students and batches"),
		new BooleanRight("consult_curriculum", "See the subjects per academic period"),
	))
);
?>