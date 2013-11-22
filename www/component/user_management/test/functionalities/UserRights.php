<?php 
class UserRights extends TestFunctionalitiesScenario {
	
	public function getName() { return "User's rights"; }
	
	public function getCoveredFunctions() {
		return array("has_right", "compute_rights_implications");
	}
	
	public function getUsers() {
		return array(
			new TestUser("no_right",array()),
			new TestUser("user_consult_user_list", array("consult_user_list"=>true)),
			new TestUser("user_manage_users", array("manage_users"=>true)),
			new TestUser("user_manage_selection", array("manage_selection_campaign"=>true))
		);
	}
	
	public function getSteps() {
		return array(
			new UserRights_TestNoRight(),
			new UserRights_TestOneRight(),
			new UserRights_TestRightPlusImplication(),
			new UserRights_TestNonExistingRight(),
			new UserRights_TestRightPlusMultipleImplications(),
		);
	}
	
}

class UserRights_TestNoRight extends TestFunctionalitiesStep {
	public function getName() { return "Test a user who has no right"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","no_right","");
		if ($err <> null) return "Cannot login: ".$err;
		if (PNApplication::$instance->user_management->has_right("consult_user_list"))
			return "The user has the right to consult user list";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class UserRights_TestOneRight extends TestFunctionalitiesStep {
	public function getName() { return "Test a user who has one right"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","user_consult_user_list","");
		if ($err <> null) return "Cannot login: ".$err;
		if (!PNApplication::$instance->user_management->has_right("consult_user_list"))
			return "The user hasn't the right to consult user list";
		if (PNApplication::$instance->user_management->has_right("manage_users"))
			return "The user has the right to manage the users";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class UserRights_TestRightPlusImplication extends TestFunctionalitiesStep {
	public function getName() { return "Test a user who can manage users implying the right to consult the users"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","user_manage_users","");
		if ($err <> null) return "Cannot login: ".$err;
		if (!PNApplication::$instance->user_management->has_right("consult_user_list"))
			return "The user hasn't the right to consult user list";
		if (!PNApplication::$instance->user_management->has_right("manage_users"))
			return "The user hasn't the right to manage the users";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class UserRights_TestNonExistingRight extends TestFunctionalitiesStep {
	public function getName() { return "Test to check a non-existing right"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","user_manage_users","");
		if ($err <> null) return "Cannot login: ".$err;
		if (PNApplication::has_errors()) return "Errors before checking the right";
		PNApplication::$instance->user_management->has_right("toto");
		if (!PNApplication::has_errors())
			return "No error when checking a non-existing right";
		PNApplication::clear_errors();
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class UserRights_TestRightPlusMultipleImplications extends TestFunctionalitiesStep {
	public function getName() { return "Test a user with 1 right implying several"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","user_manage_selection","");
		if ($err <> null) return "Cannot login: ".$err;
		if (!PNApplication::$instance->user_management->has_right("manage_selection_campaign"))
			return "The user hasn't the right manage_selection_campaign";
		if (!PNApplication::$instance->user_management->has_right("manage_information_session"))
			return "The user hasn't the right manage_information_session";
		if (!PNApplication::$instance->user_management->has_right("edit_information_session"))
			return "The user hasn't the right edit_information_session";
		if (!PNApplication::$instance->user_management->has_right("can_access_selection_data"))
			return "The user hasn't the right can_access_selection_data";
		if (!PNApplication::$instance->user_management->has_right("see_information_session_details"))
			return "The user hasn't the right see_information_session_details";
		if (PNApplication::$instance->user_management->has_right("consult_user_list"))
			return "The user has the right consult_user_list";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

?>