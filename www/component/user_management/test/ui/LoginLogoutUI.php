<?php 
class LoginLogoutUI extends TestUIScenario {
	
	public function getName() { return "Login / Logout"; }
	
	public function getUsers() {
		return array(
			new TestUser("test_user",array())
		);
	}
	
}
?>