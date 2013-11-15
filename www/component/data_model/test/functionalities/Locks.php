<?php 
class Locks extends TestFunctionalitiesScenario {
	
	public function getName() { return "Locks"; }
	
	public function getCoveredFunctions() {
		return array();
	}
	
	public function getUsers() {
		return array(
			new TestUser("test_noright", array()),
			new TestUser("test_readall", array("consult_user_list"=>true)),
			new TestUser("test_readwrite", array("consult_user_list"=>true,"consult_user_roles"=>true)),
			new TestUser("test_readwrite_specific", array("consult_user_list"=>true)),
		);
	}
	
	public function getSteps() {
		return array(
			new Locks__Prepare_DataModel(),
			new Locks__Test_NoRight(),
			new Locks__Test_OnlyRead(),
			new Locks__Test_ReadWrite(),
			new Locks__Test_ReadWriteSpecific(),
		);
	}
	
}

function Locks__declare_model() {
	require_once("component/data_model/Model.inc");
	$model = DataModel::get();
	$model->addTable("Test_noaccess")
		->addPrimaryKey("id")
		->addInteger("value")
		;
	$model->addTable("Test_can_read_all")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		;
	$model->addTable("Test_readwrite")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		->addWriteAccess("consult_user_roles", true)
		;
	$model->addTable("Test_readwrite_specific")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		->addWriteFilter(function(&$q, $table_alias) {
		}, function($rows) {
			$final = array();
			foreach ($rows as $row) if ($row["value"] > 1000) array_push($final, $row);
			return $final;
		})
		;
}

class Locks__Prepare_DataModel extends TestFunctionalitiesStep {
	public function getName() { return "Prepare Data Model"; }
	public function run(&$scenario_data) {
		Locks__declare_model();
		require_once("component/data_model/DataBaseModel.inc");
		foreach (DataModel::get()->internalGetTables() as $table)
			if (substr($table->getName(),0,5) == "Test_")
				DataBaseModel::create_table(SQLQuery::get_db_system_without_security(), $table);
		$scenario_data["noaccess_id1"] = SQLQuery::create()->bypass_security()->insert("Test_noaccess", array("value"=>1));
		$scenario_data["noaccess_id2000"] = SQLQuery::create()->bypass_security()->insert("Test_noaccess", array("value"=>2000));
		$scenario_data["can_read_all_id1"] = SQLQuery::create()->bypass_security()->insert("Test_can_read_all", array("value"=>1));
		$scenario_data["can_read_all_id2000"] = SQLQuery::create()->bypass_security()->insert("Test_can_read_all", array("value"=>2000));
		$scenario_data["readwrite_id1"] = SQLQuery::create()->bypass_security()->insert("Test_readwrite", array("value"=>1));
		$scenario_data["readwrite_id2000"] = SQLQuery::create()->bypass_security()->insert("Test_readwrite", array("value"=>2000));
		$scenario_data["readwrite_specific_id1"] = SQLQuery::create()->bypass_security()->insert("Test_readwrite_specific", array("value"=>1));
		$scenario_data["readwrite_specific_id2000"] = SQLQuery::create()->bypass_security()->insert("Test_readwrite_specific", array("value"=>2000));
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Locks__Test_NoRight extends TestFunctionalitiesStep {
	public function getName() { return "Try to lock with user having no right"; }
	public function run(&$scenario_data) {
		Locks__declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_noright", "");
		if ($error <> null) return "Cannot login with user test_noright";
		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		try {
			$lock_id = DataBaseLock::lock_table("Test_noaccess", $locked_by);
			return "Can lock the table";
		} catch (Exception $e) {}
		try {
			$lock_id = DataBaseLock::lock_column("Test_noaccess", "value", $locked_by);
			return "Can lock a column";
		} catch (Exception $e) {}
		try {
			$lock_id = DataBaseLock::lock_row("Test_noaccess", $scenario_data["noaccess_id1"], $locked_by);
			return "Can lock a row";
		} catch (Exception $e) {}
		try {
			$lock_id = DataBaseLock::lock_cell("Test_noaccess", $scenario_data["noaccess_id1"], "value", $locked_by);
			return "Can lock a cell";
		} catch (Exception $e) {}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Locks__Test_OnlyRead extends TestFunctionalitiesStep {
	public function getName() { return "Try to lock with user who can only read"; }
	public function run(&$scenario_data) {
		Locks__declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_readall", "");
		if ($error <> null) return "Cannot login with user test_readall";
		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		try {
			$lock_id = DataBaseLock::lock_table("Test_can_read_all", $locked_by);
			return "Can lock the table";
		} catch (Exception $e) {}
		try {
			$lock_id = DataBaseLock::lock_column("Test_can_read_all", "value", $locked_by);
			return "Can lock a column";
		} catch (Exception $e) {}
		try {
			$lock_id = DataBaseLock::lock_row("Test_can_read_all", $scenario_data["can_read_all_id1"], $locked_by);
			return "Can lock a row";
		} catch (Exception $e) {}
		try {
			$lock_id = DataBaseLock::lock_cell("Test_can_read_all", $scenario_data["can_read_all_id1"], "value", $locked_by);
			return "Can lock a cell";
		} catch (Exception $e) {}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Locks__Test_ReadWrite extends TestFunctionalitiesStep {
	public function getName() { return "Try to lock with user who can read and write all"; }
	public function run(&$scenario_data) {
		Locks__declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_readwrite", "");
		if ($error <> null) return "Cannot login with user test_readwrite";
		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		try {
			$lock_id = DataBaseLock::lock_table("Test_readwrite", $locked_by);
		} catch (Exception $e) {
			return "Cannot lock the table: ".$e->getMessage();
		}
		try {
			$lock_id = DataBaseLock::lock_column("Test_readwrite", "value", $locked_by);
		} catch (Exception $e) {
			return "Cannot lock a column: ".$e->getMessage();
		}
		try {
			$lock_id = DataBaseLock::lock_row("Test_readwrite", $scenario_data["readwrite_id1"], $locked_by);
		} catch (Exception $e) {
			return "Cannot lock a row: ".$e->getMessage();
		}
		try {
			$lock_id = DataBaseLock::lock_cell("Test_readwrite", $scenario_data["readwrite_id1"], "value", $locked_by);
		} catch (Exception $e) {
			return "Cannot lock a cell: ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Locks__Test_ReadWriteSpecific extends TestFunctionalitiesStep {
	public function getName() { return "Try to lock with user who can read all and write only specific row"; }
	public function run(&$scenario_data) {
		Locks__declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_readwrite_specific", "");
		if ($error <> null) return "Cannot login with user test_readwrite_specific";
		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		try {
			$lock_id = DataBaseLock::lock_table("Test_readwrite_specific", $locked_by);
			return "Can lock the table";
		} catch (Exception $e) {
		}
		try {
			$lock_id = DataBaseLock::lock_column("Test_readwrite_specific", "value", $locked_by);
			return "Can lock a column";
		} catch (Exception $e) {
		}
		try {
			$lock_id = DataBaseLock::lock_row("Test_readwrite_specific", $scenario_data["readwrite_specific_id1"], $locked_by);
			return "Can lock a row which is protected";
		} catch (Exception $e) {
		}
		try {
			$lock_id = DataBaseLock::lock_row("Test_readwrite_specific", $scenario_data["readwrite_specific_id2000"], $locked_by);
		} catch (Exception $e) {
			return "Cannot lock a row which is allowed: ".$e->getMessage();
		}
		try {
			$lock_id = DataBaseLock::lock_cell("Test_readwrite_specific", $scenario_data["readwrite_specific_id1"], "value", $locked_by);
			return "Can lock a cell in a row which is protected";
		} catch (Exception $e) {
		}
		try {
			$lock_id = DataBaseLock::lock_cell("Test_readwrite_specific", $scenario_data["readwrite_specific_id2000"], "value", $locked_by);
		} catch (Exception $e) {
			return "Cannot lock a cell in a row which is allowed: ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

?>