<?php 
return array(
	new AccessRightCategory("PN Staff",array(
		new BooleanRight("edit_anybody", "Can edit information of any person",array(true=>"see_anybody"))
	))
);
?>