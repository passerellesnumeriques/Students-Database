<?php 
abstract class TestScenario {
	
	public abstract function getName();
	
	public abstract function getSteps();
	
	/**
	 * @return TestUser[]
	 */
	protected function getUsers() { return array(); }
	
	public function init(&$scenario_data) {
		$db = SQLQuery::get_db_system_without_security();
		// initialize database
		require_once("component/development/service/create_db.php");
		$service = new service_create_db();
		$service->execute(PNApplication::$instance->development, array("domain"=>"Test"));
		$db->execute("USE students_test");
		// insert data
		$error = $this->_create_users();
		if ($error <> null)
			return "An error occured while creating users for this scenario: ".$error;
		$this->_insert_data();
		return null;
	}
	
	public function run_step($step_index, &$scenario_data) {
		$db = SQLQuery::get_db_system_without_security();
		$db->execute("USE students_test");
		// run
		$steps = $this->getSteps();
		$step = $steps[$step_index];
		return $step->run($scenario_data);
	}
	
	private function _create_users() {
		foreach ($this->getUsers() as $user) {
			$user_id = PNApplication::$instance->user_management->create_user("Test", $user->username, true);
			if ($user_id == null) return "Creation of user ".$user->username;
			PNApplication::$instance->user_management->assign_user_rights($user_id, $user->rights, true);
		}
		return null;
	}
	
	private function _insert_data() {
		
	}
}

abstract class TestStep {
	
	public abstract function getName();
	public abstract function run(&$scenario_data);
	
}

abstract class TestFunctionalitiesScenario extends TestScenario {
	
	public abstract function getCoveredFunctions();
	
}

abstract class TestServicesScenario extends TestScenario {
	
	public abstract function getCoveredServices();
	
}

class TestUser {
	
	public $username;
	public $rights;
	
	public function __construct($username, $rights) {
		$this->username = $username;
		$this->rights = $rights;
	}
	
}

?>