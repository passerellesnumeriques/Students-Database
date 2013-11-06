<?php 
class RemoveLinks extends TestFunctionalitiesScenario {
	
	public function getName() { return "Remove with links"; }
	
	public function getCoveredFunctions() {
		return array();
	}
	
	public function getUsers() {
		return array(
			new TestUser("test_readonly", array("consult_user_list"=>true)),
			new TestUser("test_remove_notmodify", array("consult_user_list"=>true,"consult_user_roles"=>true,"consult_students_list"=>true,"can_access_selection_data"=>true)),
			new TestUser("test_remove_notinstrong", array("consult_user_list"=>true,"consult_user_roles"=>true,"consult_students_list"=>true,"consult_staff_list"=>true)),
			new TestUser("test_remove_ok", array("consult_user_list"=>true,"consult_user_roles"=>true,"consult_staff_list"=>true,"can_access_selection_data"=>true)),
		);
	}
	
	public function getSteps() {
		return array(
			new RemoveLinks_Prepare_DataModel(),
			new RemoveLinks_RemoveNotAllowedAll(),
			new RemoveLinks_RemoveButNotModify(),
			new RemoveLinks_NotRemoveInStrongLink(),
			new RemoveLinks_RemoveOk(),
		);
	}
	
}

function RemoveLinks_declare_model() {
	require_once("component/data_model/Model.inc");
	$model = DataModel::get();
	$model->addTable("RemoveLinks_1_Root")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		->addWriteAccess("consult_user_roles", true)
		->addRemoveAccess("consult_user_roles", true)
		;
	$model->addTable("RemoveLinks_1_Linked_weak")
		->addPrimaryKey("id")
		->addForeignKey("root", "RemoveLinks_1_Root", false, false, false)
		->addReadAccess("consult_user_list", true)
		->addWriteAccess("consult_staff_list", true)
		->addRemoveAccess("consult_students_list", true)
		;
	$model->addTable("RemoveLinks_1_Linked_strong")
		->addPrimaryKey("id")
		->addForeignKey("root", "RemoveLinks_1_Root", true, false, false)
		->addReadAccess("consult_user_list", true)
		->addWriteAccess("consult_user_rights", true)
		->addRemoveAccess("can_access_selection_data", true)
		;
}

class RemoveLinks_Prepare_DataModel extends TestStep {
	public function getName() { return "Prepare Data Model"; }
	public function run(&$scenario_data) {
		RemoveLinks_declare_model();
		require_once("component/data_model/DataBaseModel.inc");
		foreach (DataModel::get()->internalGetTables() as $table)
			if (substr($table->getName(),0,12) == "RemoveLinks_")
				DataBaseModel::create_table(SQLQuery::get_db_system_without_security(), $table);
		// root1
		$scenario_data["root1_id1"] = SQLQuery::create()->bypass_security()->insert("RemoveLinks_1_Root", array("value"=>1));
		$scenario_data["root1_id2"] = SQLQuery::create()->bypass_security()->insert("RemoveLinks_1_Root", array("value"=>2));
		SQLQuery::create()->bypass_security()->insert("RemoveLinks_1_Linked_weak", array("root"=>$scenario_data["root1_id1"]));
		SQLQuery::create()->bypass_security()->insert("RemoveLinks_1_Linked_weak", array("root"=>$scenario_data["root1_id2"]));
		SQLQuery::create()->bypass_security()->insert("RemoveLinks_1_Linked_strong", array("root"=>$scenario_data["root1_id1"]));
		SQLQuery::create()->bypass_security()->insert("RemoveLinks_1_Linked_strong", array("root"=>$scenario_data["root1_id2"]));
		
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class RemoveLinks_RemoveNotAllowedAll extends TestStep {
	public function getName() { return "Try to remove, but user cannot remove or write any table"; }
	public function run(&$scenario_data) {
		RemoveLinks_declare_model();
		require_once("component/data_model/DataBaseModel.inc");
		$error = PNApplication::$instance->user_management->login("Test", "test_readonly", "");
		if ($error <> null) return "Cannot login with user test_readonly";
		try {
			SQLQuery::create()->remove_key("RemoveLinks_1_Root", $scenario_data["root1_id1"]);
			return "Can remove";
		} catch (Exception $e) {
		}
		try { $rows_root = SQLQuery::create()->select("RemoveLinks_1_Root")->execute(); } catch (Exception $e) { return "Cannot select on table RemoveLinks_1_Root"; }
		try { $rows_weak = SQLQuery::create()->select("RemoveLinks_1_Linked_weak")->execute(); } catch (Exception $e) { return "Cannot select on table RemoveLinks_1_Linked_weak"; }
		try { $rows_strong = SQLQuery::create()->select("RemoveLinks_1_Linked_strong")->execute(); } catch (Exception $e) { return "Cannot select on table RemoveLinks_1_Linked_strong"; }
		if (count($rows_root) <> 2) return "Select on RemoveLinks_1_Root returned ".count($rows_root).", expected is 2";
		if (count($rows_weak) <> 2) return "Select on RemoveLinks_1_Linked_weak returned ".count($rows_weak).", expected is 2";
		if (count($rows_strong) <> 2) return "Select on RemoveLinks_1_Linked_strong returned ".count($rows_strong).", expected is 2";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class RemoveLinks_RemoveButNotModify extends TestStep {
	public function getName() { return "Try to remove, with user who: can remove from all tables, but cannot modify a table having a weak link"; }
	public function run(&$scenario_data) {
		RemoveLinks_declare_model();
		require_once("component/data_model/DataBaseModel.inc");
		$error = PNApplication::$instance->user_management->login("Test", "test_remove_notmodify", "");
		if ($error <> null) return "Cannot login with user test_remove_notmodify";
		try {
			SQLQuery::create()->remove_key("RemoveLinks_1_Root", $scenario_data["root1_id1"]);
			return "Can remove";
		} catch (Exception $e) {
		}
		try { $rows_root = SQLQuery::create()->select("RemoveLinks_1_Root")->execute(); } catch (Exception $e) { return "Cannot select on table RemoveLinks_1_Root"; }
		try { $rows_weak = SQLQuery::create()->select("RemoveLinks_1_Linked_weak")->execute(); } catch (Exception $e) { return "Cannot select on table RemoveLinks_1_Linked_weak"; }
		try { $rows_strong = SQLQuery::create()->select("RemoveLinks_1_Linked_strong")->execute(); } catch (Exception $e) { return "Cannot select on table RemoveLinks_1_Linked_strong"; }
		if (count($rows_root) <> 2) return "Select on RemoveLinks_1_Root returned ".count($rows_root).", expected is 2";
		if (count($rows_weak) <> 2) return "Select on RemoveLinks_1_Linked_weak returned ".count($rows_weak).", expected is 2";
		if (count($rows_strong) <> 2) return "Select on RemoveLinks_1_Linked_strong returned ".count($rows_strong).", expected is 2";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class RemoveLinks_NotRemoveInStrongLink extends TestStep {
	public function getName() { return "Try to remove, with user who: can remove from root and weak link tables, can modify in weak link table, but cannot remove on strong link table"; }
	public function run(&$scenario_data) {
		RemoveLinks_declare_model();
		require_once("component/data_model/DataBaseModel.inc");
		$error = PNApplication::$instance->user_management->login("Test", "test_remove_notinstrong", "");
		if ($error <> null) return "Cannot login with user test_remove_notinstrong";
		try {
			SQLQuery::create()->remove_key("RemoveLinks_1_Root", $scenario_data["root1_id1"]);
			return "Can remove";
		} catch (Exception $e) {
		}
		try { $rows_root = SQLQuery::create()->select("RemoveLinks_1_Root")->execute(); } catch (Exception $e) { return "Cannot select on table RemoveLinks_1_Root"; }
		try { $rows_weak = SQLQuery::create()->select("RemoveLinks_1_Linked_weak")->execute(); } catch (Exception $e) { return "Cannot select on table RemoveLinks_1_Linked_weak"; }
		try { $rows_strong = SQLQuery::create()->select("RemoveLinks_1_Linked_strong")->execute(); } catch (Exception $e) { return "Cannot select on table RemoveLinks_1_Linked_strong"; }
		if (count($rows_root) <> 2) return "Select on RemoveLinks_1_Root returned ".count($rows_root).", expected is 2";
		if (count($rows_weak) <> 2) return "Select on RemoveLinks_1_Linked_weak returned ".count($rows_weak).", expected is 2";
		if (count($rows_strong) <> 2) return "Select on RemoveLinks_1_Linked_strong returned ".count($rows_strong).", expected is 2";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class RemoveLinks_RemoveOk extends TestStep {
	public function getName() { return "Try to remove, with user who can do it"; }
	public function run(&$scenario_data) {
		RemoveLinks_declare_model();
		require_once("component/data_model/DataBaseModel.inc");
		$error = PNApplication::$instance->user_management->login("Test", "test_remove_ok", "");
		if ($error <> null) return "Cannot login with user test_remove_ok";
		try {
			SQLQuery::create()->remove_key("RemoveLinks_1_Root", $scenario_data["root1_id1"]);
		} catch (Exception $e) {
			return "Cannot remove: ".$e->getMessage();
		}
		try { $rows_root = SQLQuery::create()->select("RemoveLinks_1_Root")->execute(); } catch (Exception $e) { return "Cannot select on table RemoveLinks_1_Root"; }
		try { $rows_weak = SQLQuery::create()->select("RemoveLinks_1_Linked_weak")->execute(); } catch (Exception $e) { return "Cannot select on table RemoveLinks_1_Linked_weak"; }
		try { $rows_strong = SQLQuery::create()->select("RemoveLinks_1_Linked_strong")->execute(); } catch (Exception $e) { return "Cannot select on table RemoveLinks_1_Linked_strong"; }
		if (count($rows_root) <> 1) return "Select on RemoveLinks_1_Root returned ".count($rows_root).", expected is 1";
		if (count($rows_weak) <> 2) return "Select on RemoveLinks_1_Linked_weak returned ".count($rows_weak).", expected is 1";
		if (count($rows_strong) <> 1) return "Select on RemoveLinks_1_Linked_strong returned ".count($rows_strong).", expected is 1";
		foreach ($rows_weak as $r) {
			if ($r["root"] == $scenario_data["root1_id1"])
				return "Still a link to the root table in RemoveLinks_1_Linked_weak";
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

?>