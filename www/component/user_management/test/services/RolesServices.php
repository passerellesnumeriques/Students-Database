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
			new RolesServices_Test_CreateRole_Ok(),
			new RolesServices_Test_CreateRole_Again(),
		);
	}
	
}

class RolesServices_Test_CreateRole_NoRight extends TestServicesStep {
	public function getName() { return "Create role without permission"; }
	public function initializationStep(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","no_right","");
		if ($err <> null) return "Cannot login: ".$err;
		return null;
	}
	public function getServiceName() { return "create_role"; }
	public function getServiceInput(&$scenario_data) { return "{name:'test_error'}"; }
	public function getJavascriptToCheckServiceOutput($scenario_data) {
		return "if (!errors || errors.length == 0) return 'No error returned'; if (result) return 'Can create role: '+service.generateInput(result); return null;";
	}
	public function finalizationStep(&$scenario_data) {
		if (SQLQuery::create()->bypass_security()->select("Role")->where("name","test_error")->execute_single_row() <> null)
			return "Create role returned error, but the role exists in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
class RolesServices_Test_CreateRole_Ok extends TestServicesStep {
	public function getName() { return "Create role"; }
	public function initializationStep(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","can_manage_roles","");
		if ($err <> null) return "Cannot login: ".$err;
		return null;
	}
	public function getServiceName() { return "create_role"; }
	public function getServiceInput(&$scenario_data) { return "{name:'test_ok'}"; }
	public function getJavascriptToCheckServiceOutput($scenario_data) {
		return 
			"if (errors && errors.length > 0) return 'Cannot create role: '+service.generateInput(errors);".
			"if (!result) return 'Cannot create role: result is false';".
			"return null;";
	}
	public function finalizationStep(&$scenario_data) {
		if (SQLQuery::create()->bypass_security()->select("Role")->where("name","test_ok")->execute_single_row() == null)
			return "Create role succeed, but the role does not exist in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
class RolesServices_Test_CreateRole_Again extends TestServicesStep {
	public function getName() { return "Create role with same name"; }
	public function initializationStep(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","can_manage_roles","");
		if ($err <> null) return "Cannot login: ".$err;
		return null;
	}
	public function getServiceName() { return "create_role"; }
	public function getServiceInput(&$scenario_data) { return "{name:'test_ok'}"; }
	public function getJavascriptToCheckServiceOutput($scenario_data) {
		return
		"if (!errors || errors.length == 0) return 'Can create a role which already exists';".
		"if (result) return 'Can create a role which already exists';".
		"return null;";
	}
	public function finalizationStep(&$scenario_data) {
		$nb = count(SQLQuery::create()->bypass_security()->select("Role")->where("name","test_ok")->execute());
		if ($nb <> 1)
			return "The creation of a duplicate failed, but ".$nb." found in database (should be 1)";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

?>