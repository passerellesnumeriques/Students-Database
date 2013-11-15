<?php 
class RolesServices extends TestServicesScenario {
	
	public function getName() { return "Roles"; }
	
	public function getUsers() {
		return array(
			new TestUser("no_right",array()),
			new TestUser("can_manage_roles",array("manage_roles"=>true)),
			new TestUser("can_assign_roles",array("assign_role"=>true))
		);
	}
	
	public function getSteps() {
		return array(
			new RolesServices_Test_CreateRole_NoRight(),
		);
	}
	
}

class RolesServices_Test_CreateRole_NoRight extends TestServicesStep {
	public function getName() { return "Create role with a user who has no right"; }
	public function initializationStep(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","no_right","");
		if ($err <> null) return "Cannot login: ".$err;
		return null;
	}
	public function getServiceName() { return "create_role"; }
	public function getServiceInput(&$scenario_data) { return "{name:'test_error'}"; }
	public function getJavascriptToCheckServiceOutput($scenario_data) {
		return "if (!errors || errors.length == 0) return 'No error returned'; if (result) return 'Can create role: '+service.generate_input(result); return null;";
	}
	public function finalizationStep(&$scenario_data) {
		if (SQLQuery::create()->bypass_security()->select("Role")->where("name","test_error")->execute_single_row() <> null)
			return "Create role returned error, but the role exists in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

?>