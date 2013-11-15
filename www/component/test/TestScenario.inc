<?php 
/**
 * A test scenario is composed of several steps.
 * It provides a name,
 * a list of test users (with their access rights) which will be used,
 * and the steps to execute (steps depends on the kind of scenario/implementation of TestScenario).
 * 
 * At the beginning, the method init is called, which will reset the Test Database, create the users, and insert initial data in the DataBase.
 * Then, the steps are executed.
 */
abstract class TestScenario {
	
	public abstract function getName();
	
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
		$this->init_database();
		return null;
	}
	
	private function _create_users() {
		foreach ($this->getUsers() as $user) {
			$user_id = PNApplication::$instance->user_management->create_user("Test", $user->username, true);
			if ($user_id == null) return "Creation of user ".$user->username;
			PNApplication::$instance->user_management->assign_user_rights($user_id, $user->rights, true);
		}
		return null;
	}
	
	protected function init_database() {
	}
}

/**
 * A TestStep is a step in a TestScenario. Its content and execution depends on the type of scenario/implementation of TestScenario.
 */
abstract class TestStep {
	
	public abstract function getName();
	
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