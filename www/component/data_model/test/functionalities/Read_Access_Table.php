<?php 
class Read_Access_Table extends TestFunctionalitiesScenario {
	
	public function getName() { return "Read Access Table"; }
	
	public function getCoveredFunctions() {
		return array();
	}
	
	public function getUsers() {
		return array(
			new TestUser("test_read_noright", array()),
			new TestUser("test_read_can_read_all", array("consult_user_list"=>true)),
			new TestUser("test_read_can_column", array("consult_user_roles"=>true)),
		);
	}
	
	public function getSteps() {
		return array(
			new Read_Access_Table__Prepare_DataModel(),
			new Read_Access_Table__Test_NoRight(),
			new Read_Access_Table__Test_CanReadAll_With_NoRight(),
			new Read_Access_Table__Test_CanReadAll_With_Right(),
			new Read_Access_Table__Test_CanReadColumn_With_NoRight(),
			new Read_Access_Table__Test_CanReadColumn_With_AllRight(),
			new Read_Access_Table__Test_CanReadColumn_With_SpecificRight(),
			new Read_Access_Table__Test_CanReadFilter_With_NoRight(),
			new Read_Access_Table__Test_CanReadFilter_With_AllRight(),
			new Read_Access_Table__Test_CanReadFilter_With_SpecificRight(),
		);
	}
	
}

function Read_Access_Table__declare_model() {
	require_once("component/data_model/Model.inc");
	$model = DataModel::get();
	$model->addTable("TestReadAccess_noaccess")
		->addPrimaryKey("id")
		->addInteger("value")
		;
	$model->addTable("TestReadAccess_can_read_all")
		->addPrimaryKey("id")
		->addInteger("value")
		->addReadAccess("consult_user_list", true)
		;
	$model->addTable("TestReadAccess_specific_column")
		->addPrimaryKey("id")
		->addInteger("column_ok")
		->addInteger("column_nok")
		->addColumnReadAccess("column_ok", "consult_user_roles", true)
		->addReadAccess("consult_user_list", true)
		;
	$model->addTable("TestReadAccess_filter")
		->addPrimaryKey("id")
		->addInteger("column_ok")
		->addInteger("column_nok")
		->addColumnReadAccess("column_ok", "consult_user_roles", true)
		->addReadAccess("consult_user_list", true)
		->addReadFilter(function(&$q, $table_alias) {
			$q->where("`".$table_alias."`.`column_nok`=51");
		})
		;
}

class Read_Access_Table__Prepare_DataModel extends TestFunctionalitiesStep {
	public function getName() { return "Prepare Data Model"; }
	public function run(&$scenario_data) {
		Read_Access_Table__declare_model();
		require_once("component/data_model/DataBaseUtilities.inc");
		foreach (DataModel::get()->internalGetTables() as $table)
			if (substr($table->getName(),0,15) == "TestReadAccess_")
				DataBaseUtilities::createTable(SQLQuery::getDataBaseAccessWithoutSecurity(), $table);
		SQLQuery::create()->bypassSecurity()->insert("TestReadAccess_specific_column", array("column_ok"=>1,"column_nok"=>2));
		SQLQuery::create()->bypassSecurity()->insert("TestReadAccess_filter", array("column_ok"=>1,"column_nok"=>2));
		SQLQuery::create()->bypassSecurity()->insert("TestReadAccess_filter", array("column_ok"=>10,"column_nok"=>20));
		SQLQuery::create()->bypassSecurity()->insert("TestReadAccess_filter", array("column_ok"=>1664,"column_nok"=>51));
		SQLQuery::create()->bypassSecurity()->insert("TestReadAccess_filter", array("column_ok"=>100,"column_nok"=>200));
		SQLQuery::create()->bypassSecurity()->insert("TestReadAccess_filter", array("column_ok"=>1000,"column_nok"=>2000));
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Read_Access_Table__Test_NoRight extends TestFunctionalitiesStep {
	public function getName() { return "Access to a table which cannot be read"; }
	public function run(&$scenario_data) {
		Read_Access_Table__declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_read_noright", "");
		if ($error <> null) return "Cannot login with user test_read_noright";
		try {
			DataModel::get()->getTable("TestReadAccess_noaccess");
			return "Can get table TestReadAccess_noaccess with user test_read_noright";
		} catch (Exception $e) {}
		try {
			$res = SQLQuery::create()->select("TestReadAccess_noaccess")->execute();
			return "Can select on table TestReadAccess_noaccess with user test_read_noright: ".count($res)." rows returned";
		} catch (Exception $e) {}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Read_Access_Table__Test_CanReadAll_With_NoRight extends TestFunctionalitiesStep {
	public function getName() { return "Access to a table which can be read with a specific right, but with a user which has not"; }
	public function run(&$scenario_data) {
		Read_Access_Table__declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_read_noright", "");
		if ($error <> null) return "Cannot login with user test_read_noright";
		try {
			DataModel::get()->getTable("TestReadAccess_can_read_all");
			return "Can get table TestReadAccess_can_read_all with user test_read_noright";
		} catch (Exception $e) {}
		try {
			$res = SQLQuery::create()->select("TestReadAccess_can_read_all")->execute();
			return "Can select on table TestReadAccess_can_read_all with user test_read_noright: ".count($res)." rows returned";
		} catch (Exception $e) {}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Read_Access_Table__Test_CanReadAll_With_Right extends TestFunctionalitiesStep {
	public function getName() { return "Access to a table which can be read with a specific right, with a user who has it"; }
	public function run(&$scenario_data) {
		Read_Access_Table__declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_read_can_read_all", "");
		if ($error <> null) return "Cannot login with user test_read_can_read_all";
		try {
			DataModel::get()->getTable("TestReadAccess_can_read_all");
		} catch (Exception $e) {
			return "Cannot get table TestReadAccess_can_read_all with user test_read_can_read_all: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestReadAccess_can_read_all")->execute();
		} catch (Exception $e) {
			return "Cannot select on table TestReadAccess_can_read_all with user test_read_can_read_all: ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Read_Access_Table__Test_CanReadColumn_With_NoRight extends TestFunctionalitiesStep {
	public function getName() { return "Access to a table with right on all and right on column, but with a user which has no right"; }
	public function run(&$scenario_data) {
		Read_Access_Table__declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_read_noright", "");
		if ($error <> null) return "Cannot login with user test_read_noright";
		try {
			DataModel::get()->getTable("TestReadAccess_specific_column");
			return "Can get table TestReadAccess_specific_column with user test_read_noright";
		} catch (Exception $e) {}
		try {
			$res = SQLQuery::create()->select("TestReadAccess_specific_column")->execute();
			return "Can select on table TestReadAccess_specific_column with user test_read_noright: ".count($res)." rows returned";
		} catch (Exception $e) {}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Read_Access_Table__Test_CanReadColumn_With_AllRight extends TestFunctionalitiesStep {
	public function getName() { return "Access to a table with right on all and right on column, with a user which can read all"; }
	public function run(&$scenario_data) {
		Read_Access_Table__declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_read_can_read_all", "");
		if ($error <> null) return "Cannot login with user test_read_can_read_all";
		try {
			DataModel::get()->getTable("TestReadAccess_specific_column");
		} catch (Exception $e) {
			return "Cannot get table TestReadAccess_specific_column with user test_read_can_read_all: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestReadAccess_specific_column")->execute();
			if (count($res) == 0)
				return "No result in select on table TestReadAccess_specific_column with user test_read_can_read_all";
			if (!isset($res[0]["column_nok"]))
				return "Cannot access to column_nok in a select on table TestReadAccess_specific_column with user test_read_can_read_all";
			if (!isset($res[0]["column_ok"]))
				return "Cannot access to column_ok in a select on table TestReadAccess_specific_column with user test_read_can_read_all: result is ".var_export($res,true);
		} catch (Exception $e) {
			return "Cannot select on table TestReadAccess_specific_column with user test_read_can_read_all: ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Read_Access_Table__Test_CanReadColumn_With_SpecificRight extends TestFunctionalitiesStep {
	public function getName() { return "Access to a table with right on all and right on column, with a user which can read only one column"; }
	public function run(&$scenario_data) {
		Read_Access_Table__declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_read_can_column", "");
		if ($error <> null) return "Cannot login with user test_read_can_column";
		try {
			DataModel::get()->getTable("TestReadAccess_specific_column");
		} catch (Exception $e) {
			return "Cannot get table TestReadAccess_specific_column with user test_read_can_column: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestReadAccess_specific_column")->execute();
			if (count($res) == 0)
				return "No result in select on table TestReadAccess_specific_column with user test_read_can_column";
			if (isset($res[0]["column_nok"]))
				return "Can access to column_nok in a select on table TestReadAccess_specific_column with user test_read_can_column";
			if (!isset($res[0]["column_ok"]))
				return "Cannot access to column_ok in a select on table TestReadAccess_specific_column with user test_read_can_column";
		} catch (Exception $e) {
			return "Cannot select on table TestReadAccess_specific_column with user test_read_can_column: ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Read_Access_Table__Test_CanReadFilter_With_NoRight extends TestFunctionalitiesStep {
	public function getName() { return "Access to a table with right on all, right on column and filter; with a user who has no right"; }
	public function run(&$scenario_data) {
		Read_Access_Table__declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_read_noright", "");
		if ($error <> null) return "Cannot login with user test_read_noright";
		try {
			DataModel::get()->getTable("TestReadAccess_filter");
		} catch (Exception $e) {
			return "Cannot get table TestReadAccess_filter with user test_read_noright: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestReadAccess_filter")->execute();
			if (count($res) <> 1)
				return "Select on table TestReadAccess_filter with user test_read_noright give ".count($res)." results, expected is 1. Result: ".var_export($res, true);
			if (!isset($res[0]["column_nok"]))
				return "Cannot access to column_nok in a select on table TestReadAccess_filter with user test_read_noright";
			if (isset($res[0]["column_ok"]))
				return "Can access to column_ok in a select on table TestReadAccess_filter with user test_read_noright";
			if ($res[0]["column_nok"] <> 51)
				return "Wrong row returned by select, expected column_nok=51, received=".$res[0]["column_nok"];
		} catch (Exception $e) {
			return "Cannot select on table TestReadAccess_filter with user test_read_noright: ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Read_Access_Table__Test_CanReadFilter_With_AllRight extends TestFunctionalitiesStep {
	public function getName() { return "Access to a table with right on all, right on column and filter; with a user who has all rights"; }
	public function run(&$scenario_data) {
		Read_Access_Table__declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_read_can_read_all", "");
		if ($error <> null) return "Cannot login with user test_read_can_read_all";
		try {
			DataModel::get()->getTable("TestReadAccess_filter");
		} catch (Exception $e) {
			return "Cannot get table TestReadAccess_filter with user test_read_can_read_all: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestReadAccess_filter")->execute();
			if (count($res) <> 5)
				return "Select on table TestReadAccess_filter with user test_read_can_read_all give ".count($res)." results, expected is 5. Result: ".var_export($res, true);
			if (!isset($res[0]["column_nok"]))
				return "Cannot access to column_nok in a select on table TestReadAccess_filter with user test_read_can_read_all";
			if (!isset($res[0]["column_ok"]))
				return "Cannot access to column_ok in a select on table TestReadAccess_filter with user test_read_can_read_all";
		} catch (Exception $e) {
			return "Cannot select on table TestReadAccess_filter with user test_read_can_read_all: ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Read_Access_Table__Test_CanReadFilter_With_SpecificRight extends TestFunctionalitiesStep {
	public function getName() { return "Access to a table with right on all, right on column and filter; with a user who has right on a single column"; }
	public function run(&$scenario_data) {
		Read_Access_Table__declare_model();
		$error = PNApplication::$instance->user_management->login("Test", "test_read_can_column", "");
		if ($error <> null) return "Cannot login with user test_read_can_column";
		try {
			DataModel::get()->getTable("TestReadAccess_filter");
		} catch (Exception $e) {
			return "Cannot get table TestReadAccess_filter with user test_read_can_column: ".$e->getMessage();
		}
		try {
			$res = SQLQuery::create()->select("TestReadAccess_filter")->execute();
			if (count($res) <> 1)
				return "Select on table TestReadAccess_filter with user test_read_can_column give ".count($res)." results, expected is 1. Result: ".var_export($res, true);
			if (!isset($res[0]["column_nok"]))
				return "Cannot access to column_nok in a select on table TestReadAccess_filter with user test_read_can_column";
			if (!isset($res[0]["column_ok"]))
				return "Cannot access to column_ok in a select on table TestReadAccess_filter with user test_read_can_column";
			if ($res[0]["column_ok"] <> 1664)
				return "Wrong row returned by select, expected column_ok=1664, received=".$res[0]["column_ok"];
		} catch (Exception $e) {
			return "Cannot select on table TestReadAccess_filter with user test_read_can_column: ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
?>