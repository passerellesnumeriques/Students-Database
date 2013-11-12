<?php 
class LoginLogout extends TestFunctionalitiesScenario {
	
	public function getName() { return "Login / Logout"; }
	
	public function getCoveredFunctions() {
		return array("login","logout");
	}
	
	protected function getUsers() {
		return array(
			new TestUser("test", array()),
		);
	}
	
	public function getSteps() {
		return array(new LoginLogout_Login(), new LoginLogout_Logout(), new LoginLogout_Invalid());
	}
	
}

class LoginLogout_Login extends TestStep {
	public function getName() { return "Login"; }
	public function run(&$scenario_data) {
		if (PNApplication::$instance->user_management->domain <> null ||
			PNApplication::$instance->user_management->username <> null)
			return "A user is already logged in";
		$err = PNApplication::$instance->user_management->login("Test","test","test");
		if ($err <> null) return $err;
		if (PNApplication::$instance->user_management->domain <> "Test" ||
			PNApplication::$instance->user_management->username <> "test")
			return "Login operation failed.";
		return null;
	}
}
class LoginLogout_Logout extends TestStep {
	public function getName() { return "Logout"; }
	public function run(&$scenario_data) {
		if (PNApplication::$instance->user_management->domain == null ||
			PNApplication::$instance->user_management->username == null)
			return "Not logged in";
		PNApplication::$instance->user_management->logout();
		if (PNApplication::$instance->user_management->domain <> null ||
			PNApplication::$instance->user_management->username <> null)
			return "Logout failed: user still logged in";
		return null;
	}
}
class LoginLogout_Invalid extends TestStep {
	public function getName() { return "Invalid login"; }
	public function run(&$scenario_data) {
		if (PNApplication::$instance->user_management->domain <> null ||
			PNApplication::$instance->user_management->username <> null)
			return "A user is already logged in";
		$err = PNApplication::$instance->user_management->login("Test","invalid","test");
		if ($err == null) return "Login succeed with invalid login";
		if (PNApplication::$instance->user_management->domain <> null ||
			PNApplication::$instance->user_management->username <> null)
			return "After failure of login, a user is logged in!";
		return null;
	}
}
?>