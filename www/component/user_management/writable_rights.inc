<?php 
return array(
	new AccessRightCategory("User Management",array(
		new BooleanRight("manage_roles", "Manage roles (create/remove/edit)"),
		new BooleanRight("assign_role", "Assign/Unassign roles to users",array(true=>array("consult_user_list"=>true,"consult_user_role"=>true))),
		new BooleanRight("edit_user_rights", "Assign/Unassign rights to users",array(true=>array("consult_user_list"=>true,"consult_user_rights"=>true))),
		new BooleanRight("manage_users", "Manage users",array(true=>array("consult_user_list"=>true,"consult_user_rights"=>true,"edit_user_rights"=>true,"assign_role"=>true))),
	))
);
?>