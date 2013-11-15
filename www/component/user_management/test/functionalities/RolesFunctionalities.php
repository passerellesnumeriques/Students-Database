<?php 
class RolesFunctionalities extends TestFunctionalitiesScenario {
	
	public function getName() { return "Roles"; }
	
	public function getCoveredFunctions() {
		return array("has_right", "compute_rights_implications", "assign_roles", "unassign_roles", "create_role", "remove_role");
	}
	
	public function getUsers() {
		return array(
			new TestUser("no_right",array()),
			new TestUser("can_manage_roles",array("manage_roles"=>true)),
			new TestUser("can_assign_roles",array("assign_role"=>true))
		);
	}
	
	public function getSteps() {
		return array(
			new RolesFunctionalities_Test_CreateRole_NoRight(),
			new RolesFunctionalities_Test_CreateRole_Ok(),
			new RolesFunctionalities_Test_CreateRole_OnlyAssign(),
			new RolesFunctionalities_Test_RemoveRole_NoRight(),
			new RolesFunctionalities_Test_RemoveRole_OnlyAssign(),
			new RolesFunctionalities_Test_RemoveRole_Ok(),
			new RolesFunctionalities_Test_Init(),
		);
	}
	
}

class RolesFunctionalities_Test_CreateRole_NoRight extends TestFunctionalitiesStep {
	public function getName() { return "Create role with a user who has no right"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","no_right","");
		if ($err <> null) return "Cannot login: ".$err;
		try {
			if (PNApplication::$instance->user_management->create_role("test_error"))
				return "Can create role";
			PNApplication::clear_errors();
		} catch (Exception $e) {}
		if (SQLQuery::create()->bypass_security()->select("Role")->where("name","test_error")->execute_single_row() <> null)
			return "Create role returned error, but the role exists in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
class RolesFunctionalities_Test_CreateRole_Ok extends TestFunctionalitiesStep {
	public function getName() { return "Create role with a user who can"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","can_manage_roles","");
		if ($err <> null) return "Cannot login: ".$err;
		try {
			$id = PNApplication::$instance->user_management->create_role("test_ok");
			if ($id == null)
				return "Cannot create role";
			$scenario_data["test_ok_id"] = $id;
		} catch (Exception $e) { return "Cannot create role: ".$e->getMessage(); }
		if (SQLQuery::create()->bypass_security()->select("Role")->where("name","test_ok")->execute_single_row() == null)
			return "Create succeed, but not present in database";
		try {
			if (PNApplication::$instance->user_management->create_role("test_ok"))
				return "Can create 2 times the same role";
			PNApplication::clear_errors();
		} catch (Exception $e) { }
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
class RolesFunctionalities_Test_CreateRole_OnlyAssign extends TestFunctionalitiesStep {
	public function getName() { return "Create role with a user who can only assign roles"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","can_assign_roles","");
		if ($err <> null) return "Cannot login: ".$err;
		try {
			if (PNApplication::$instance->user_management->create_role("test_error"))
				return "Can create role";
			PNApplication::clear_errors();
		} catch (Exception $e) {}
		if (SQLQuery::create()->bypass_security()->select("Role")->where("name","test_error")->execute_single_row() <> null)
			return "Create role returned error, but the role exists in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}


class RolesFunctionalities_Test_RemoveRole_NoRight extends TestFunctionalitiesStep {
	public function getName() { return "Remove role with a user who has no right"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","no_right","");
		if ($err <> null) return "Cannot login: ".$err;
		try {
			if (PNApplication::$instance->user_management->remove_role($scenario_data["test_ok_id"]))
				return "Can remove role";
			PNApplication::clear_errors();
		} catch (Exception $e) {}
		if (SQLQuery::create()->bypass_security()->select("Role")->where("name","test_ok")->execute_single_row() == null)
			return "Remove role returned error, but the role does not exist anymore in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
class RolesFunctionalities_Test_RemoveRole_OnlyAssign extends TestFunctionalitiesStep {
	public function getName() { return "Remove role with a user who can only assign roles"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","can_assign_roles","");
		if ($err <> null) return "Cannot login: ".$err;
		try {
			if (PNApplication::$instance->user_management->remove_role($scenario_data["test_ok_id"]))
				return "Can remove role";
			PNApplication::clear_errors();
		} catch (Exception $e) {}
		if (SQLQuery::create()->bypass_security()->select("Role")->where("name","test_ok")->execute_single_row() == null)
			return "Remove role returned error, but the role does not exist anymore in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
class RolesFunctionalities_Test_RemoveRole_Ok extends TestFunctionalitiesStep {
	public function getName() { return "Remove role with a user who can"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","can_manage_roles","");
		if ($err <> null) return "Cannot login: ".$err;
		try {
			if (!PNApplication::$instance->user_management->remove_role($scenario_data["test_ok_id"]))
				return "Cannot remove role";
		} catch (Exception $e) { return "Cannot remove role: ".$e->getMessage(); }
		if (SQLQuery::create()->bypass_security()->select("Role")->where("name","test_ok")->execute_single_row() <> null)
			return "Remove succeed, but still present in database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}


class RolesFunctionalities_Test_Init extends TestFunctionalitiesStep {
	public function getName() { return "Create roles for next steps"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","can_manage_roles","");
		if ($err <> null) return "Cannot login: ".$err;
		// TODO
		PNApplication::$instance->user_management->logout();
		return null;
	}
}


?>