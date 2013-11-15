<?php 
class Write_Access_Table extends TestFunctionalitiesScenario {
	
	public function getName() { return "Write Access Table"; }
	
	public function getCoveredFunctions() {
		return array();
	}
	
	public function getUsers() {
		return array(
			new TestUser("test_write_onlyread", array("consult_user_list"=>true)),
			new TestUser("test_write_writeall", array("consult_user_list"=>true,"consult_user_roles"=>true)),
			new TestUser("test_write_writeonly", array("consult_user_roles"=>true)),
		);
	}
	
	public function getSteps() {
		return array(
			new Write_Access_Table_Prepare_DataModel(),
			new Write_Access_Table_Test_NoRight(),
			new Write_Access_Table_Test_WriteAll_CannotWrite(),
			new Write_Access_Table_Test_WriteAll_CanWriteAll(),
			new Write_Access_Table_Test_WriteAll_CanOnlyWrite(),
			new Write_Access_Table_Test_WriteFilter_OnlyFilter(),
			new Write_Access_Table_Test_WriteFilter_WriteAll(),
		);
	}
	
}

function Write_Access_Table_declare_model() {
	require_once("component/data_model/Model.inc");
	$model = DataModel::get();
	$model->addTable("TestWriteAccess_onlyread")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		;
	$model->addTable("TestWriteAccess_writeall")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		->addWriteAccess("consult_user_roles", true)
		;
	$model->addTable("TestWriteAccess_writefilter")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		->addWriteAccess("consult_user_roles", true)
		->addWriteFilter(function(&$q, $table_alias) {
			$q->where("`value`>1");
		}, function($rows){
			$final = array();
			foreach ($rows as $row) if ($row["value"] > 1) array_push($final, $row);
			return $final;
		})
		;
}

class Write_Access_Table_Prepare_DataModel extends TestFunctionalitiesStep {
	public function getName() { return "Prepare Data Model"; }
	public function run(&$scenario_data) {
		Write_Access_Table_declare_model();
		require_once("component/data_model/DataBaseModel.inc");
		foreach (DataModel::get()->internalGetTables() as $table)
			if (substr($table->getName(),0,16) == "TestWriteAccess_")
			DataBaseModel::create_table(SQLQuery::get_db_system_without_security(), $table);
		$scenario_data["onlyread_id"] = SQLQuery::create()->bypass_security()->insert("TestWriteAccess_onlyread", array("value"=>51));
		$scenario_data["writeall_id"] = SQLQuery::create()->bypass_security()->insert("TestWriteAccess_writeall", array("value"=>51));
		$scenario_data["writeall_id2"] = SQLQuery::create()->bypass_security()->insert("TestWriteAccess_writeall", array("value"=>51));
		$scenario_data["writefilter_id1"] = SQLQuery::create()->bypass_security()->insert("TestWriteAccess_writefilter", array("value"=>1));
		$scenario_data["writefilter_id2"] = SQLQuery::create()->bypass_security()->insert("TestWriteAccess_writefilter", array("value"=>2));
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Write_Access_Table_Test_NoRight extends TestFunctionalitiesStep {
	public function getName() { return "Try to modify a table which can only be read"; }
	public function run(&$scenario_data) {
		Write_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_write_onlyread", "");
		if ($error <> null) return "Cannot login with user test_write_onlyread";
		try {
			DataModel::get()->getTable("TestWriteAccess_onlyread");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestWriteAccess_onlyread")->where("id",$scenario_data["onlyread_id"])->execute();
			if (count($res) <> 1) return "Unexpected result on select: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 51) return "Wrong value found in select";
		} catch (Exception $e) {}
		try {
			SQLQuery::create()->update_by_key("TestWriteAccess_onlyread", $scenario_data["onlyread_id"], array("value"=>1664));
			return "Can modify table";
		} catch (Exception $e) {}
		try {
			$res = SQLQuery::create()->select("TestWriteAccess_onlyread")->where("id",$scenario_data["onlyread_id"])->execute();
			if (count($res) <> 1) return "After trying to modify: Unexpected result on select: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 51) return "After trying to modify: Wrong value found in select";
		} catch (Exception $e) {}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Write_Access_Table_Test_WriteAll_CannotWrite extends TestFunctionalitiesStep {
	public function getName() { return "Try to modify a table which can be read and write, with a user who can only read"; }
	public function run(&$scenario_data) {
		Write_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_write_onlyread", "");
		if ($error <> null) return "Cannot login with user test_write_onlyread";
		try {
			DataModel::get()->getTable("TestWriteAccess_writeall");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestWriteAccess_writeall")->where("id",$scenario_data["writeall_id"])->execute();
			if (count($res) <> 1) return "Unexpected result on select: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 51) return "Wrong value found in select";
		} catch (Exception $e) {}
		try {
			SQLQuery::create()->update_by_key("TestWriteAccess_writeall", $scenario_data["writeall_id"], array("value"=>1664));
			return "Can modify table";
		} catch (Exception $e) {}
		try {
			$res = SQLQuery::create()->select("TestWriteAccess_writeall")->where("id",$scenario_data["writeall_id"])->execute();
			if (count($res) <> 1) return "After trying to modify: Unexpected result on select: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 51) return "After trying to modify: Wrong value found in select";
		} catch (Exception $e) {}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Write_Access_Table_Test_WriteAll_CanWriteAll extends TestFunctionalitiesStep {
	public function getName() { return "Try to modify a table which can be read and write, with a user who can read and write all"; }
	public function run(&$scenario_data) {
		Write_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_write_writeall", "");
		if ($error <> null) return "Cannot login with user test_write_writeall";
		try {
			DataModel::get()->getTable("TestWriteAccess_writeall");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestWriteAccess_writeall")->where("id",$scenario_data["writeall_id"])->execute();
			if (count($res) <> 1) return "Unexpected result on select: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 51) return "Wrong value found in select";
		} catch (Exception $e) {}
		try {
			SQLQuery::create()->update_by_key("TestWriteAccess_writeall", $scenario_data["writeall_id"], array("value"=>1664));
		} catch (Exception $e) {
			return "Cannot modify table: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestWriteAccess_writeall")->where("id",$scenario_data["writeall_id"])->execute();
			if (count($res) <> 1) return "After trying to modify: Unexpected result on select: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 1664) return "After trying to modify: Wrong value found in select";
		} catch (Exception $e) {}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Write_Access_Table_Test_WriteAll_CanOnlyWrite extends TestFunctionalitiesStep {
	public function getName() { return "Try to modify a table which can be read and write, with a user who can only write so he should not be able to do anything"; }
	public function run(&$scenario_data) {
		Write_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_write_writeonly", "");
		if ($error <> null) return "Cannot login with user test_write_writeonly";
		try {
			DataModel::get()->getTable("TestWriteAccess_writeall");
			return "Can get table";
		} catch (Exception $e) {
		}
		try {
			$res = SQLQuery::create()->select("TestWriteAccess_writeall")->where("id",$scenario_data["writeall_id2"])->execute();
			return "Can select";
		} catch (Exception $e) {
		}
		try {
			SQLQuery::create()->update_by_key("TestWriteAccess_writeall", $scenario_data["writeall_id2"], array("value"=>1664));
			return "Can modify table";
		} catch (Exception $e) {
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Write_Access_Table_Test_WriteFilter_OnlyFilter extends TestFunctionalitiesStep {
	public function getName() { return "Try to modify a table with a user who can only read, and can write according to filter"; }
	public function run(&$scenario_data) {
		Write_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_write_onlyread", "");
		if ($error <> null) return "Cannot login with user test_write_onlyread";
		try {
			DataModel::get()->getTable("TestWriteAccess_writefilter");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestWriteAccess_writefilter")->execute();
			if (count($res) <> 2) return "Unexpected result on select: 2 rows expected, found: ".count($res);
		} catch (Exception $e) {}
		try {
			SQLQuery::create()->update_by_key("TestWriteAccess_writefilter", $scenario_data["writefilter_id1"], array("value"=>0));
			return "Can modify table for entry which does not match the filter";
		} catch (Exception $e) {}
		try {
			SQLQuery::create()->update_by_key("TestWriteAccess_writefilter", $scenario_data["writefilter_id2"], array("value"=>3));
		} catch (Exception $e) {
			return "Cannot modify table for entry which matches the filter";
		}
		try {
			$res = SQLQuery::create()->select("TestWriteAccess_writefilter")->where("id",$scenario_data["writefilter_id2"])->execute();
			if (count($res) <> 1) return "After trying to modify: Unexpected result on select: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 3) return "After trying to modify: Wrong value found in select";
		} catch (Exception $e) {}
		try {
			SQLQuery::create()->update_by_key("TestWriteAccess_writefilter", $scenario_data["writefilter_id2"], array("value"=>2));
		} catch (Exception $e) {
			return "Cannot modify a second time the table for entry which matches the filter";
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Write_Access_Table_Test_WriteFilter_WriteAll extends TestFunctionalitiesStep {
	public function getName() { return "Try to modify a table with a user who can modify everything, including a filter"; }
	public function run(&$scenario_data) {
		Write_Access_Table_declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_write_writeall", "");
		if ($error <> null) return "Cannot login with user test_write_writeall";
		try {
			DataModel::get()->getTable("TestWriteAccess_writefilter");
		} catch (Exception $e) {
			return "Cannot get table: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestWriteAccess_writefilter")->execute();
			if (count($res) <> 2) return "Unexpected result on select: 2 rows expected, found: ".count($res);
		} catch (Exception $e) {}
		try {
			SQLQuery::create()->update_by_key("TestWriteAccess_writefilter", $scenario_data["writefilter_id1"], array("value"=>0));
		} catch (Exception $e) {
			return "Cannot modify table for entry which does not match the filter";
		}
		try {
			SQLQuery::create()->update_by_key("TestWriteAccess_writefilter", $scenario_data["writefilter_id2"], array("value"=>3));
		} catch (Exception $e) {
			return "Cannot modify table for entry which matches the filter";
		}
		try {
			$res = SQLQuery::create()->select("TestWriteAccess_writefilter")->where("id",$scenario_data["writefilter_id2"])->execute();
			if (count($res) <> 1) return "After trying to modify: Unexpected result on select id2: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 3) return "After trying to modify id2: Wrong value found in select";
		} catch (Exception $e) {}
		try {
			$res = SQLQuery::create()->select("TestWriteAccess_writefilter")->where("id",$scenario_data["writefilter_id1"])->execute();
			if (count($res) <> 1) return "After trying to modify: Unexpected result on select di1: 1 row expected, found: ".count($res);
			if ($res[0]["value"] <> 0) return "After trying to modify id1: Wrong value found in select";
		} catch (Exception $e) {}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
?>