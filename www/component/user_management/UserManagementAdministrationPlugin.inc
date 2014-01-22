<?php
require_once("component/administration/AdministrationPlugin.inc");
/**
 * Implementation of AdministrationPlugin for user_management: display user management section in the administration pages
 */ 
class UserManagementAdministartionPlugin implements AdministrationPlugin {
	
	public function getAdministrationPages() {
		return array(
			new AdministrationPage("Users", "/dynamic/user_management/page/home", "/static/user_management/user_management_16.png"),
		);
	}
	
}
?>