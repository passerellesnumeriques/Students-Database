<?php 
class user_management_Tests extends ComponentTests {
	
	public function getFunctionalitiesTests() {
		return array(
			"CreateUser",
			"LoginLogout",
			"UserRights",
			"RolesFunctionalities",
		);
	}
	public function getServicesTests() {
		return array(
			"RolesServices",
		);
	}
	public function getUITests() {
		return array(
			"LoginLogoutUI",
		);
	}
	
}
?>