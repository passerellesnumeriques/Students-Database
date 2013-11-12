<?php 
class Remove_Access_Table extends TestFunctionalitiesScenario {
	
	public function getName() { return "Remove Access Table"; }
	
	public function getCoveredFunctions() {
		return array();
	}
	
	public function getUsers() {
		return array(
			new TestUser("test_remove_read", array("consult_user_list"=>true)),
			new TestUser("test_remove_readwrite", array("consult_user_list"=>true,"consult_user_roles"=>true)),
			new TestUser("test_remove_remove", array("consult_user_rights"=>true)),
			new TestUser("test_remove_readremove", array("consult_user_list"=>true,"consult_user_rights"=>true)),
		);
	}
	
	public function getSteps() {
		return array(
			new Remove_Access_Table_Prepare_DataModel(),
			new Remove_Access_Table_Test_Read(),
			new Remove_Access_Table_Test_ReadWrite(),
			new Remove_Access_Table_Test_ReadRemove_UserReadWrite(),
			new Remove_Access_Table_Test_ReadRemove_UserReadRemove(),
			new Remove_Access_Table_Test_ReadRemoveFilter_UserReadWrite(),
			new Remove_Access_Table_Test_ReadRemoveFilter_UserReadRemove(),
		);
	}
	
}

function Remove_Access_Table_declare_model() {
	require_once("component/data_model/Model.inc");
	$model = DataModel::get();
	$model->addTable("TestRemoveAccess_onlyread")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		;
	$model->addTable("TestRemoveAccess_readwrite")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		->addWriteAccess("consult_user_roles", true)
		;
	$model->addTable("TestRemoveAccess_readremove")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		->addRemoveAccess("consult_user_rights", true)
		;
	$model->addTable("TestRemoveAccess_readremovefilter")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		->addRemoveAccess("consult_user_rights", true)
		->addRemoveFilter(function(&$q, $table_alias, &$locks){
			
		}, function ($rows){
			$final = array();
			foreach ($rows as $row)
				if ($row["value"] > 1000)
					array_push($final, $row);
			return $final;
		})
		;
}

class Remove_Access_Table_Prepare_DataModel extends TestStep {
	public function getName() { return "Prepare Data Model"; }
	public function run(&$scenario_data) {
		Remove_Access_Table_declare_model();
		require_once("component/data_model/DataBaseModel.inc");
		foreach (DataModel::get()->internalGetTables() as $table)
			if (substr($table->getName(),0,17) == "TestRemoveAccess_")
			DataBaseModel::create_table(SQLQuery::get_db_system_without_security(), $table);
		$scenario_data["onlyread_id1"] = SQLQuery::create()->bypass_security()->insert("TestRemoveAccess_onlyread", array("value"=>51));
		$scenario_data["readwrite_id1"] = SQLQuery::create()->bypass_security()->insert("TestRemoveAccess_readwrite", array("value"=>51));
		$scenario_data["readremove_id1"] = SQLQuery::create()->bypass_security()->insert("TestRemoveAccess_readremove", array("value"=>51));
		$scenario_data["readremove_id2"] = SQLQuery::create()->bypass_security()->insert("TestRemoveAccess_readremove", array("value"=>1));
		$scenario_data["readremovefilter_id1"] = SQLQuery::create()->bypass_security()->insert("TestRemoveAccess_readremovefilter", array("value"=>51));
		$scenario_data["readremovefilter_id2"] = SQLQuery::create()->bypass_security()->insert("TestRemoveAccess_readremovefilter", array("value"=>2));
		$scenario_data["readremovefilter_id2000"] = SQLQuery::create()->bypass_security()->insert("TestRemoveAccess_readremovefilter", array("value"=>2000));
		$scenario_data["readremovefilter_id3000"] = SQLQuery::create()->bypass_security()->insert("TestRemoveAccess_readremovefilter", array("value"=>3000));
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Remove_Access_Table_Test_Read extends TestStep {
	public function getName() { return "Try to remove from a table which can only be read"; }
	public function run(&$scenario_data) {
		Remove_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_remove_read", "");
		if ($error <> null) return "Cannot login with user test_remove_read";
		try {
			DataModel::get()->getTable("TestRemoveAccess_onlyread");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestRemoveAccess_onlyread")->execute();
			if (count($res) <> 1) return "Select failed: 1 row expected, found: ".count($res);
		} catch (Exception $e) {
			return "Cannot select: ".$e->getMessage();
		}
		try {
			SQLQuery::create()->remove_key("TestRemoveAccess_onlyread", $scenario_data["onlyread_id1"]);
			return "Can remove";
		} catch (Exception $e) {}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Remove_Access_Table_Test_ReadWrite extends TestStep {
	public function getName() { return "Try to remove in a table which can only be read and write"; }
	public function run(&$scenario_data) {
		Remove_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_remove_readwrite", "");
		if ($error <> null) return "Cannot login with user test_remove_readwrite";
		try {
			DataModel::get()->getTable("TestRemoveAccess_readwrite");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
			try {
			SQLQuery::create()->remove_key("TestRemoveAccess_readwrite", $scenario_data["readwrite_id1"]);
			return "Can remove";
		} catch (Exception $e) {}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Remove_Access_Table_Test_ReadRemove_UserReadWrite extends TestStep {
	public function getName() { return "Try to read,write and remove with a user who can only read and write"; }
	public function run(&$scenario_data) {
		Remove_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_remove_readwrite", "");
		if ($error <> null) return "Cannot login with user test_remove_readwrite";
		try {
			DataModel::get()->getTable("TestRemoveAccess_readremove");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestRemoveAccess_readremove")->where("id",$scenario_data["readremove_id1"])->execute();
			if (count($res) <> 1) return "Select failed: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 51) return "Wrong value found in select: expected is 51, found is ".$res[0]["value"];
		} catch (Exception $e) {
			return "Cannot read: ".$e->getMessage();
		}
		try {
			SQLQuery::create()->update_by_key("TestRemoveAccess_readremove", $scenario_data["readremove_id1"], array("value"=>1664));
			return "Can modify";
		} catch (Exception $e) {}
		try {
			SQLQuery::create()->remove_key("TestRemoveAccess_readremove", $scenario_data["readremove_id1"]);
			return "Can remove";
		} catch (Exception $e) {
		}
		try {
			$res = SQLQuery::create()->select("TestRemoveAccess_readremove")->where("id",$scenario_data["readremove_id1"])->execute();
			if (count($res) <> 1) return "Select failed after try to remove: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 51) return "Wrong value found in select after try to remove: expected is 51, found is ".$res[0]["value"];
		} catch (Exception $e) {
			return "Cannot read: ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Remove_Access_Table_Test_ReadRemove_UserReadRemove extends TestStep {
	public function getName() { return "Try to read and remove with a user who can do it"; }
	public function run(&$scenario_data) {
		Remove_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_remove_readremove", "");
		if ($error <> null) return "Cannot login with user test_remove_readremove";
		try {
			DataModel::get()->getTable("TestRemoveAccess_readremove");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			SQLQuery::create()->remove_key("TestRemoveAccess_readremove", $scenario_data["readremove_id2"]);
		} catch (Exception $e) {
			return "Cannot remove: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestRemoveAccess_readremove")->where("id", $scenario_data["readremove_id2"])->execute();
			if (count($res) <> 0) return "After remove, select still give a result";
		} catch (Exception $e) {
			return "Cannot select after remove: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestRemoveAccess_readremove")->where("id", $scenario_data["readremove_id1"])->execute();
			if (count($res) <> 1) return "After remove of a row, select cannot find another row (".count($res)." found)";
		} catch (Exception $e) {
			return "Cannot select after remove (2): ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Remove_Access_Table_Test_ReadRemoveFilter_UserReadWrite extends TestStep {
	public function getName() { return "Try to read and remove on a table having a filter for remove, with a user who can read and write, so who can remove if matching the filter"; }
	public function run(&$scenario_data) {
		Remove_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_remove_readwrite", "");
		if ($error <> null) return "Cannot login with user test_remove_readwrite";
		try {
			DataModel::get()->getTable("TestRemoveAccess_readremovefilter");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestRemoveAccess_readremovefilter")->where("id",$scenario_data["readremovefilter_id1"])->execute();
			if (count($res) <> 1) return "Select failed: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 51) return "Wrong value found in select: expected is 51, found is ".$res[0]["value"];
		} catch (Exception $e) {
			return "Cannot read: ".$e->getMessage();
		}
		try {
			SQLQuery::create()->remove_key("TestRemoveAccess_readremovefilter", $scenario_data["readremovefilter_id2"]);
			return "Can remove with a value which does not match the filter";
		} catch (Exception $e) {
		}
		try {
			SQLQuery::create()->remove_key("TestRemoveAccess_readremovefilter", $scenario_data["readremovefilter_id2000"]);
		} catch (Exception $e) {
			return "Cannot remove with a value which match the filter: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestRemoveAccess_readremovefilter")->where("id",$scenario_data["readremovefilter_id1"])->execute();
			if (count($res) <> 1) return "Select (1) after remove failed: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 51) return "Wrong value found in select (1) after remove: expected is 51, found is ".$res[0]["value"];
		} catch (Exception $e) {
			return "Cannot read after remove (1): ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestRemoveAccess_readremovefilter")->where("id",$scenario_data["readremovefilter_id2"])->execute();
			if (count($res) <> 1) return "Select (2) after remove failed: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 2) return "Wrong value found in select (2) after remove: expected is 2, found is ".$res[0]["value"];
		} catch (Exception $e) {
			return "Cannot read after remove (2): ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestRemoveAccess_readremovefilter")->where("id",$scenario_data["readremovefilter_id2000"])->execute();
			if (count($res) <> 0) return "Select (3) after remove failed: the row is still there";
		} catch (Exception $e) {
			return "Cannot read after remove (3): ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Remove_Access_Table_Test_ReadRemoveFilter_UserReadRemove extends TestStep {
	public function getName() { return "Try to read and remove on a table having a filter for remove, with a user who can read and remove any value"; }
	public function run(&$scenario_data) {
		Remove_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_remove_readremove", "");
		if ($error <> null) return "Cannot login with user test_remove_readremove";
		try {
			DataModel::get()->getTable("TestRemoveAccess_readremovefilter");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestRemoveAccess_readremovefilter")->where("id",$scenario_data["readremovefilter_id1"])->execute();
			if (count($res) <> 1) return "Select failed: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 51) return "Wrong value found in select: expected is 51, found is ".$res[0]["value"];
		} catch (Exception $e) {
			return "Cannot read: ".$e->getMessage();
		}
		try {
			SQLQuery::create()->remove_key("TestRemoveAccess_readremovefilter", $scenario_data["readremovefilter_id2"]);
		} catch (Exception $e) {
			return "Cannot remove with a value which does not match the filter: ".$e->getMessage();
		}
		try {
			SQLQuery::create()->remove_key("TestRemoveAccess_readremovefilter", $scenario_data["readremovefilter_id3000"]);
		} catch (Exception $e) {
			return "Cannot remove with a value which match the filter: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestRemoveAccess_readremovefilter")->where("id",$scenario_data["readremovefilter_id1"])->execute();
			if (count($res) <> 1) return "Select (1) after remove failed: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 51) return "Wrong value found in select (1) after remove: expected is 51, found is ".$res[0]["value"];
		} catch (Exception $e) {
			return "Cannot read after remove (1): ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestRemoveAccess_readremovefilter")->where("id",$scenario_data["readremovefilter_id2"])->execute();
			if (count($res) <> 0) return "Select (2) after remove failed: the row is still there";
		} catch (Exception $e) {
			return "Cannot read after remove (2): ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestRemoveAccess_readremovefilter")->where("id",$scenario_data["readremovefilter_id3000"])->execute();
			if (count($res) <> 0) return "Select (3) after remove failed: the row is still there";
		} catch (Exception $e) {
			return "Cannot read after remove (3): ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
?>