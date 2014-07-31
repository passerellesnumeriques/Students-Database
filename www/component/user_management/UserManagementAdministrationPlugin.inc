<?php
require_once("component/administration/AdministrationPlugin.inc");
/**
 * Implementation of AdministrationPlugin for user_management: display user management section in the administration pages
 */ 
class UserManagementAdministartionPlugin implements AdministrationPlugin {
	
	public function getAdministrationPages() {
		return array(
			new AdministrationPage("User Management", "/dynamic/user_management/page/home", "/static/user_management/user_management_16.png", "/static/user_management/user_management_32.png", "Manage users, roles and access rights", function() { return PNApplication::$instance->user_management->has_right("consult_user_list"); }),
		);
	}
	
}
?>