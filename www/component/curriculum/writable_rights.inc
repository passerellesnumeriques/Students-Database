<?php 
return array(
	new AccessRightCategory("In PN",array(
		new BooleanRight("edit_curriculum", "Edit batches, academic periods, specializatons, and subjects per academic period",array(true=>array("consult_curriculum"=>true))),
	))
);
?>