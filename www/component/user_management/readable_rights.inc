<?php
return array(
	new AccessRightCategory("User Management",array(
		new BooleanRight("consult_user_list", "See the list of users"),
		new BooleanRight("consult_user_roles", "See roles of a user"),
		new BooleanRight("consult_user_rights", "See rights of a user",array(true=>array("consult_user_roles"=>true))),
	))
);
?>