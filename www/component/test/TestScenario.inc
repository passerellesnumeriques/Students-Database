<?php 
abstract class TestScenario {
	
	public abstract function getName();
	
	public abstract function getSteps();
	
	public function run() {
		$db = SQLQuery::get_db_system_without_security();
		// initialize database
		$_POST["domain"] = "Test";
		require_once("component/development/service/create_db.php");
		$service = new service_create_db();
		$service->execute(PNApplication::$instance->development);
		$db->execute("USE students_test");
		// insert data
		$this->_create_users();
		$this->_insert_data();
		// run
		$steps = $this->getSteps();
		$results = array();
		$failed = false;
		foreach ($steps as $step) {
			if ($failed) {
				array_push($results, null);
				continue;
			}
			$res = $step->run();
			if ($res <> null) {
				$failed = true;
				array_push($results, $res);
			} else {
				array_push($results, "OK");
			}
		}
		return $results;
	}
	
	private function _create_users() {
		
	}
	
	private function _insert_data() {
		
	}
}

abstract class TestStep {
	
	public abstract function getName();
	public abstract function run();
	
}

abstract class TestFunctionalitiesScenario extends TestScenario {
	
	public abstract function getCoveredFunctions();
	
}

abstract class TestServicesScenario extends TestScenario {
	
	public abstract function getCoveredServices();
	
}

?>