<?php 
return array(
	new AccessRightCategory("People",array(
		new BooleanRight("edit_anybody", "Can edit information of any person",array(true=>array("see_anybody"=>true)))
	))
);
?>