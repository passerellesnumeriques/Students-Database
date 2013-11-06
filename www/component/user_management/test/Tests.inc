<?php 
class user_management_Tests extends ComponentTests {
	
	public function getFunctionalitiesTests() {
		return array(
			"CreateUser",
			"LoginLogout",
		);
	}
	public function getServicesTests() {
		return array(
			
		);
	}
	public function getUITests() {
		return array(
			"LoginLogout",
		);
	}
	
}
?>