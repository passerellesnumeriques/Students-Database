<?php 
class RolesFunctionalities extends TestFunctionalitiesScenario {
	
	public function getName() { return "Roles"; }
	
	public function getCoveredFunctions() {
		return array("has_right", "compute_rights_implications", "assign_roles", "unassign_roles", "create_role", "remove_role", "set_role_rights");
	}
	
	public function getUsers() {
		return array(
			new TestUser("no_right",array()),
			new TestUser("can_manage_roles",array("manage_roles"=>true)),
			new TestUser("can_assign_roles",array("assign_role"=>true)),
			new TestUser("to_be_assigned",array()),
			new TestUser("user_role1",array()),
			new TestUser("user_role2",array()),
			new TestUser("user_role3",array()),
			new TestUser("user_role2_plus",array("consult_user_list"=>false)),
			new TestUser("user_role2_3",array()),
		);
	}
	
	public function getSteps() {
		return array(
			new RolesFunctionalities_Test_CreateRole_NoRight(),
			new RolesFunctionalities_Test_CreateRole_Ok(),
			new RolesFunctionalities_Test_CreateRole_OnlyAssign(),
			new RolesFunctionalities_Test_SetRoleRights_NoRight(),
			new RolesFunctionalities_Test_SetRoleRights_Ok(),
			new RolesFunctionalities_Test_AssignRole_NoRight(),
			new RolesFunctionalities_Test_AssignRole_Ok(),
			new RolesFunctionalities_Test_UnassignRole_NoRight(),
			new RolesFunctionalities_Test_UnassignRole_Ok(),
			new RolesFunctionalities_Test_RemoveRole_NoRight(),
			new RolesFunctionalities_Test_RemoveRole_OnlyAssign(),
			new RolesFunctionalities_Test_RemoveRole_Ok(),
			new RolesFunctionalities_Test_Init(),
			new RolesFunctionalities_Test_Role1(),
			new RolesFunctionalities_Test_Role2(),
			new RolesFunctionalities_Test_Role3(),
			new RolesFunctionalities_Test_Role2Plus(),
			new RolesFunctionalities_Test_Role2_3(),
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
			PNApplication::clearErrors();
		} catch (Exception $e) {}
		if (SQLQuery::create()->bypassSecurity()->select("Role")->where("name","test_error")->executeSingleRow() <> null)
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
			$scenario_data["create_role_ok_id"] = $id;
		} catch (Exception $e) { return "Cannot create role: ".$e->getMessage(); }
		if (SQLQuery::create()->bypassSecurity()->select("Role")->where("name","test_ok")->executeSingleRow() == null)
			return "Create succeed, but not present in database";
		try {
			if (PNApplication::$instance->user_management->create_role("test_ok"))
				return "Can create 2 times the same role";
			PNApplication::clearErrors();
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
			PNApplication::clearErrors();
		} catch (Exception $e) {}
		if (SQLQuery::create()->bypassSecurity()->select("Role")->where("name","test_error")->executeSingleRow() <> null)
			return "Create role returned error, but the role exists in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class RolesFunctionalities_Test_SetRoleRights_NoRight extends TestFunctionalitiesStep {
	public function getName() { return "Set rights of a role without permission"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","no_right","");
		if ($err <> null) return "Cannot login: ".$err;
		try {
			if (PNApplication::$instance->user_management->set_role_rights($scenario_data["create_role_ok_id"],array("assign_role"=>true)))
				return "Can set role's rights";
			PNApplication::clearErrors();
		} catch (Exception $e) {}
		$rights = SQLQuery::create()->bypassSecurity()->select("RoleRights")->where("role",$scenario_data["create_role_ok_id"])->execute(); 
		if (count($rights) > 0)
			return "Set rights returned error, but the role has rights in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
class RolesFunctionalities_Test_SetRoleRights_Ok extends TestFunctionalitiesStep {
	public function getName() { return "Set rights of a role"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","can_manage_roles","");
		if ($err <> null) return "Cannot login: ".$err;
		try {
			if (!PNApplication::$instance->user_management->set_role_rights($scenario_data["create_role_ok_id"],array("assign_role"=>true,"consult_user_list"=>true)))
				return "Cannot set role's rights";
		} catch (Exception $e) {
			return "Cannot set role's rights: ".$e->getMessage();
		}
		$rights = SQLQuery::create()->bypassSecurity()->select("RoleRights")->where("role",$scenario_data["create_role_ok_id"])->execute();
		if (count($rights) == 0)
			return "Set rights returned no error, but the role has no rights in the database";
		if (count($rights) <> 2)
			return "2 rights set, but ".count($rights)." rights found in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class RolesFunctionalities_Test_AssignRole_NoRight extends TestFunctionalitiesStep {
	public function getName() { return "Assign a role to a user, without permission"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","no_right","");
		if ($err <> null) return "Cannot login: ".$err;
		try {
			if (PNApplication::$instance->user_management->assign_roles(array($scenario_data["user_id_to_be_assigned"]),array($scenario_data["create_role_ok_id"])))
				return "Can assign role";
		} catch (Exception $e) {
		}
		$res = SQLQuery::create()->bypassSecurity()->select("UserRole")->where("role",$scenario_data["create_role_ok_id"])->where("user",$scenario_data["user_id_to_be_assigned"])->execute();
		if (count($res) <> 0)
			return "Assign role function failed, but the role is assigned in database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
class RolesFunctionalities_Test_AssignRole_Ok extends TestFunctionalitiesStep {
	public function getName() { return "Assign a role to a user"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","can_assign_roles","");
		if ($err <> null) return "Cannot login: ".$err;
		try {
			if (!PNApplication::$instance->user_management->assign_roles(array($scenario_data["user_id_to_be_assigned"]),array($scenario_data["create_role_ok_id"])))
				return "Cannot assign role";
		} catch (Exception $e) {
			return "Cannot assign role: ".$e->getMessage();
		}
		$res = SQLQuery::create()->bypassSecurity()->select("UserRole")->where("role",$scenario_data["create_role_ok_id"])->where("user",$scenario_data["user_id_to_be_assigned"])->execute();
		if (count($res) == 0)
			return "Assign role function succeed, but not in database";
		if (count($res) <> 1)
			return "The assignment is found ".count($res)." times in the database (should be 1)";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class RolesFunctionalities_Test_UnassignRole_NoRight extends TestFunctionalitiesStep {
	public function getName() { return "Unassign a role from a user"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","no_right","");
		if ($err <> null) return "Cannot login: ".$err;
		try {
			if (PNApplication::$instance->user_management->unassign_roles(array($scenario_data["user_id_to_be_assigned"]),array($scenario_data["create_role_ok_id"])))
				return "Can unassign role";
		} catch (Exception $e) {
		}
		$res = SQLQuery::create()->bypassSecurity()->select("UserRole")->where("role",$scenario_data["create_role_ok_id"])->where("user",$scenario_data["user_id_to_be_assigned"])->execute();
		if (count($res) == 0)
			return "Unassign role function failed, but not anymore in database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
class RolesFunctionalities_Test_UnassignRole_Ok extends TestFunctionalitiesStep {
	public function getName() { return "Unassign a role from a user"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","can_assign_roles","");
		if ($err <> null) return "Cannot login: ".$err;
		try {
			if (!PNApplication::$instance->user_management->unassign_roles(array($scenario_data["user_id_to_be_assigned"]),array($scenario_data["create_role_ok_id"])))
				return "Cannot unassign role";
		} catch (Exception $e) {
			return "Cannot unassign role: ".$e->getMessage();
		}
		$res = SQLQuery::create()->bypassSecurity()->select("UserRole")->where("role",$scenario_data["create_role_ok_id"])->where("user",$scenario_data["user_id_to_be_assigned"])->execute();
		if (count($res) <> 0)
			return "Unassign role function succeed, but still in database";
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
			if (PNApplication::$instance->user_management->remove_role($scenario_data["create_role_ok_id"]))
				return "Can remove role";
			PNApplication::clearErrors();
		} catch (Exception $e) {}
		if (SQLQuery::create()->bypassSecurity()->select("Role")->where("name","test_ok")->executeSingleRow() == null)
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
			if (PNApplication::$instance->user_management->remove_role($scenario_data["create_role_ok_id"]))
				return "Can remove role";
			PNApplication::clearErrors();
		} catch (Exception $e) {}
		if (SQLQuery::create()->bypassSecurity()->select("Role")->where("name","test_ok")->executeSingleRow() == null)
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
			if (!PNApplication::$instance->user_management->remove_role($scenario_data["create_role_ok_id"]))
				return "Cannot remove role";
		} catch (Exception $e) { return "Cannot remove role: ".$e->getMessage(); }
		if (SQLQuery::create()->bypassSecurity()->select("Role")->where("name","test_ok")->executeSingleRow() <> null)
			return "Remove succeed, but still present in database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}


class RolesFunctionalities_Test_Init extends TestFunctionalitiesStep {
	public function getName() { return "Create roles for next steps"; }
	private function create_role($role_name, &$scenario_data, $rights) {
		try {
			$id = PNApplication::$instance->user_management->create_role($role_name);
			if ($id == null)
				return "Cannot create role '".$role_name."'";
			$scenario_data[$role_name] = $id;
			if (!PNApplication::$instance->user_management->set_role_rights($id, $rights))
				return "Cannot set rights for role '".$role_name."'";
			return null;
		} catch (Exception $e) { return "Cannot create role '".$role_name."': ".$e->getMessage(); }
	}
	private function assign_role($username, $role_name, $scenario_data) {
		try {
			if (!PNApplication::$instance->user_management->assign_roles(array($scenario_data["user_id_".$username]),array($scenario_data[$role_name])))
				return "Cannot assign role ".$role_name." to user ".$username;
			return null;
		} catch (Exception $e) {
			return "Cannot assign role ".$role_name." to user ".$username.": ".$e->getMessage();
		}
	}
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","can_manage_roles","");
		if ($err <> null) return "Cannot login: ".$err;
		if (($err = $this->create_role("role1", $scenario_data, array())) <> null) return $err;
		if (($err = $this->create_role("role2", $scenario_data, array("consult_user_list"=>true))) <> null) return $err;
		if (($err = $this->create_role("role3", $scenario_data, array("consult_user_rights"=>true))) <> null) return $err;
		if (($err = $this->assign_role("user_role1", "role1", $scenario_data)) <> null) return $err;
		if (($err = $this->assign_role("user_role2", "role2", $scenario_data)) <> null) return $err;
		if (($err = $this->assign_role("user_role3", "role3", $scenario_data)) <> null) return $err;
		if (($err = $this->assign_role("user_role2_plus", "role2", $scenario_data)) <> null) return $err;
		if (($err = $this->assign_role("user_role2_3", "role2", $scenario_data)) <> null) return $err;
		if (($err = $this->assign_role("user_role2_3", "role3", $scenario_data)) <> null) return $err;
		PNApplication::$instance->user_management->logout();
		return null;
	}
}


class RolesFunctionalities_Test_Role1 extends TestFunctionalitiesStep {
	public function getName() { return "Role without rights"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","user_role1","");
		if ($err <> null) return "Cannot login: ".$err;
		if (PNApplication::$instance->user_management->has_right("consult_user_list"))
			return "Has right";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
class RolesFunctionalities_Test_Role2 extends TestFunctionalitiesStep {
	public function getName() { return "Role with 1 right"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","user_role2","");
		if ($err <> null) return "Cannot login: ".$err;
		if (!PNApplication::$instance->user_management->has_right("consult_user_list"))
			return "Has not right 1";
		if (PNApplication::$instance->user_management->has_right("consult_user_rights"))
			return "Has right 2";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
class RolesFunctionalities_Test_Role3 extends TestFunctionalitiesStep {
	public function getName() { return "Role with 1 right implying another one"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","user_role3","");
		if ($err <> null) return "Cannot login: ".$err;
		if (!PNApplication::$instance->user_management->has_right("consult_user_roles"))
			return "Has not right 1";
		if (!PNApplication::$instance->user_management->has_right("consult_user_rights"))
			return "Has not right 2";
		if (PNApplication::$instance->user_management->has_right("consult_user_list"))
			return "Has right 3";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
class RolesFunctionalities_Test_Role2Plus extends TestFunctionalitiesStep {
	public function getName() { return "Role with 1 right, and another config on user"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","user_role2_plus","");
		if ($err <> null) return "Cannot login: ".$err;
		if (!PNApplication::$instance->user_management->has_right("consult_user_list"))
			return "Has not the right";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
class RolesFunctionalities_Test_Role2_3 extends TestFunctionalitiesStep {
	public function getName() { return "Role with 1 right implying another one, and 1 role with an additional"; }
	public function run(&$scenario_data) {
		$err = PNApplication::$instance->user_management->login("Test","user_role2_3","");
		if ($err <> null) return "Cannot login: ".$err;
		if (!PNApplication::$instance->user_management->has_right("consult_user_list"))
			return "Has not right 1";
		if (!PNApplication::$instance->user_management->has_right("consult_user_rights"))
			return "Has not right 2";
		if (!PNApplication::$instance->user_management->has_right("consult_user_roles"))
			return "Has not right 3";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

?>