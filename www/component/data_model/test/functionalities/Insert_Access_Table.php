<?php 
class Insert_Access_Table extends TestFunctionalitiesScenario {
	
	public function getName() { return "Insert Access Table"; }
	
	public function getCoveredFunctions() {
		return array();
	}
	
	public function getUsers() {
		return array(
			new TestUser("test_insert_read", array("consult_user_list"=>true)),
			new TestUser("test_insert_readwrite", array("consult_user_list"=>true,"consult_user_roles"=>true)),
			new TestUser("test_insert_insert", array("consult_user_rights"=>true)),
			new TestUser("test_insert_readinsert", array("consult_user_list"=>true,"consult_user_rights"=>true)),
		);
	}
	
	public function getSteps() {
		return array(
			new Insert_Access_Table_Prepare_DataModel(),
			new Insert_Access_Table_Test_Read(),
			new Insert_Access_Table_Test_ReadWrite(),
			new Insert_Access_Table_Test_ReadInsert_UserReadWrite(),
			new Insert_Access_Table_Test_ReadInsert_UserReadInsert(),
			new Insert_Access_Table_Test_ReadInsertFilter_UserReadWrite(),
			new Insert_Access_Table_Test_ReadInsertFilter_UserReadInsert(),
		);
	}
	
}

function Insert_Access_Table_declare_model() {
	require_once("component/data_model/Model.inc");
	$model = DataModel::get();
	$model->addTable("TestInsertAccess_onlyread")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		;
	$model->addTable("TestInsertAccess_readwrite")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		->addWriteAccess("consult_user_roles", true)
		;
	$model->addTable("TestInsertAccess_readinsert")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		->addInsertAccess("consult_user_rights", true)
		;
	$model->addTable("TestInsertAccess_readinsertfilter")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		->addInsertAccess("consult_user_rights", true)
		->addInsertFilter(function($values) {
			if ($values["value"] > 1000) return true;
			return false;
		})
		;
}

class Insert_Access_Table_Prepare_DataModel extends TestFunctionalitiesStep {
	public function getName() { return "Prepare Data Model"; }
	public function run(&$scenario_data) {
		Insert_Access_Table_declare_model();
		require_once("component/data_model/DataBaseModel.inc");
		foreach (DataModel::get()->internalGetTables() as $table)
			if (substr($table->getName(),0,17) == "TestInsertAccess_")
			DataBaseModel::create_table(SQLQuery::getDataBaseAccessWithoutSecurity(), $table);
		$scenario_data["readinsert_id1"] = SQLQuery::create()->bypassSecurity()->insert("TestInsertAccess_readinsert", array("value"=>51));
		$scenario_data["readinsertfilter_id1"] = SQLQuery::create()->bypassSecurity()->insert("TestInsertAccess_readinsertfilter", array("value"=>51));
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Insert_Access_Table_Test_Read extends TestFunctionalitiesStep {
	public function getName() { return "Try to insert in a table which can only be read"; }
	public function run(&$scenario_data) {
		Insert_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_insert_read", "");
		if ($error <> null) return "Cannot login with user test_insert_read";
		try {
			DataModel::get()->getTable("TestInsertAccess_onlyread");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			SQLQuery::create()->insert("TestInsertAccess_onlyread", array("value"=>1));
			return "Can insert";
		} catch (Exception $e) {}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Insert_Access_Table_Test_ReadWrite extends TestFunctionalitiesStep {
	public function getName() { return "Try to insert in a table which can only be read and write"; }
	public function run(&$scenario_data) {
		Insert_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_insert_readwrite", "");
		if ($error <> null) return "Cannot login with user test_insert_readwrite";
		try {
			DataModel::get()->getTable("TestInsertAccess_readwrite");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			SQLQuery::create()->insert("TestInsertAccess_readwrite", array("value"=>1));
			return "Can insert";
		} catch (Exception $e) {}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Insert_Access_Table_Test_ReadInsert_UserReadWrite extends TestFunctionalitiesStep {
	public function getName() { return "Try to read,write and insert with a user who can only read and write"; }
	public function run(&$scenario_data) {
		Insert_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_insert_readwrite", "");
		if ($error <> null) return "Cannot login with user test_insert_readwrite";
		try {
			DataModel::get()->getTable("TestInsertAccess_readinsert");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestInsertAccess_readinsert")->where("id",$scenario_data["readinsert_id1"])->execute();
			if (count($res) <> 1) return "Select failed: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 51) return "Wrong value found in select: expected is 51, found is ".$res[0]["value"];
		} catch (Exception $e) {
			return "Cannot read: ".$e->getMessage();
		}
		try {
			SQLQuery::create()->updateByKey("TestInsertAccess_readinsert", $scenario_data["readinsert_id1"], array("value"=>1664));
			return "Can modify";
		} catch (Exception $e) {}
		try {
			SQLQuery::create()->insert("TestInsertAccess_readinsert", array("value"=>111));
			return "Can insert";
		} catch (Exception $e) {
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Insert_Access_Table_Test_ReadInsert_UserReadInsert extends TestFunctionalitiesStep {
	public function getName() { return "Try to read and insert with a user who can do it"; }
	public function run(&$scenario_data) {
		Insert_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_insert_readinsert", "");
		if ($error <> null) return "Cannot login with user test_insert_readinsert";
		try {
			DataModel::get()->getTable("TestInsertAccess_readinsert");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestInsertAccess_readinsert")->where("id",$scenario_data["readinsert_id1"])->execute();
			if (count($res) <> 1) return "Select failed: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 51) return "Wrong value found in select: expected is 51, found is ".$res[0]["value"];
		} catch (Exception $e) {
			return "Cannot read: ".$e->getMessage();
		}
		try {
			$scenario_data["readinsert_id2"] = SQLQuery::create()->insert("TestInsertAccess_readinsert", array("value"=>111));
		} catch (Exception $e) {
			return "Cannot insert: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestInsertAccess_readinsert")->where("id",$scenario_data["readinsert_id2"])->execute();
			if (count($res) <> 1) return "Select after insert failed: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 111) return "Wrong value found in select after insert: expected is 111, found is ".$res[0]["value"];
		} catch (Exception $e) {
			return "Cannot read after insert: ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Insert_Access_Table_Test_ReadInsertFilter_UserReadWrite extends TestFunctionalitiesStep {
	public function getName() { return "Try to read and insert on a table having a filter for insert, with a user who can read and write, so who can insert if matching the filter"; }
	public function run(&$scenario_data) {
		Insert_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_insert_readwrite", "");
		if ($error <> null) return "Cannot login with user test_insert_readwrite";
		try {
			DataModel::get()->getTable("TestInsertAccess_readinsertfilter");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestInsertAccess_readinsertfilter")->where("id",$scenario_data["readinsertfilter_id1"])->execute();
			if (count($res) <> 1) return "Select failed: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 51) return "Wrong value found in select: expected is 51, found is ".$res[0]["value"];
		} catch (Exception $e) {
			return "Cannot read: ".$e->getMessage();
		}
		try {
			SQLQuery::create()->insert("TestInsertAccess_readinsertfilter", array("value"=>111));
			return "Can insert with a value which does not match the filter";
		} catch (Exception $e) {
		}
		try {
			$scenario_data["readinsertfilter_id2"] = SQLQuery::create()->insert("TestInsertAccess_readinsertfilter", array("value"=>1111));
		} catch (Exception $e) {
			return "Cannot insert with a value which match the filter: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestInsertAccess_readinsertfilter")->where("id",$scenario_data["readinsertfilter_id2"])->execute();
			if (count($res) <> 1) return "Select after insert failed: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 1111) return "Wrong value found in select after insert: expected is 1111, found is ".$res[0]["value"];
		} catch (Exception $e) {
			return "Cannot read after insert: ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Insert_Access_Table_Test_ReadInsertFilter_UserReadInsert extends TestFunctionalitiesStep {
	public function getName() { return "Try to read and insert on a table having a filter for insert, with a user who can read and insert any value"; }
	public function run(&$scenario_data) {
		Insert_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_insert_readinsert", "");
		if ($error <> null) return "Cannot login with user test_insert_readinsert";
		try {
			DataModel::get()->getTable("TestInsertAccess_readinsertfilter");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestInsertAccess_readinsertfilter")->where("id",$scenario_data["readinsertfilter_id1"])->execute();
			if (count($res) <> 1) return "Select failed: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 51) return "Wrong value found in select: expected is 51, found is ".$res[0]["value"];
		} catch (Exception $e) {
			return "Cannot read: ".$e->getMessage();
		}
		try {
			$scenario_data["readinsertfilter_id3"] = SQLQuery::create()->insert("TestInsertAccess_readinsertfilter", array("value"=>111));
		} catch (Exception $e) {
			return "Cannot insert with a value which does not match the filter: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestInsertAccess_readinsertfilter")->where("id",$scenario_data["readinsertfilter_id3"])->execute();
			if (count($res) <> 1) return "Select after insert 1 failed: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 111) return "Wrong value found in select after insert 1: expected is 111, found is ".$res[0]["value"];
		} catch (Exception $e) {
			return "Cannot read after insert 1: ".$e->getMessage();
		}
		try {
			$scenario_data["readinsertfilter_id4"] = SQLQuery::create()->insert("TestInsertAccess_readinsertfilter", array("value"=>2000));
		} catch (Exception $e) {
			return "Cannot insert with a value which match the filter: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestInsertAccess_readinsertfilter")->where("id",$scenario_data["readinsertfilter_id4"])->execute();
			if (count($res) <> 1) return "Select after insert 2 failed: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 2000) return "Wrong value found in select after insert 2: expected is 2000, found is ".$res[0]["value"];
		} catch (Exception $e) {
			return "Cannot read after insert 2: ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
?>