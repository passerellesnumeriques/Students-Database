<?php 
return array(
	new AccessRightCategory("In PN",array(
		new BooleanRight("edit_curriculum", "Edit the subjects per academic period",array(true=>array("consult_curriculum"=>true))),
	))
);
?>