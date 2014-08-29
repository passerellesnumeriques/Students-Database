<?php 
return array(
	new AccessRightCategory("PN Staff",array(
		new BooleanRight("manage_staff", "Manage staff (create/remove/edit) and departments",array(true=>array("consult_staff_list"=>true)))
	))
);
?>