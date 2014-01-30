<?php 
class CreateUser extends TestFunctionalitiesScenario {
	
	public function getName() { return "Create user"; }
	
	public function getCoveredFunctions() {
		return array("create_user","remove_user");
	}
	
	public function getSteps() {
		return array(new CreateUser_Create(), new CreateUser_Remove());
	}
	
}

class CreateUser_Create extends TestFunctionalitiesStep {
	public function getName() { return "Create a user"; }
	public function run(&$scenario_data) {
		$user_id = PNApplication::$instance->user_management->create_user("Test", "test_user", true);
		if ($user_id == null) return "Creation failed";
		$res = SQLQuery::create()->bypassSecurity()->select("Users")->where("domain","Test")->where("username","test_user")->execute();
		if (count($res) == 0) return "User cannot be found in database";
		if (count($res) > 1) return "Several users found in database!";
		if ($user_id <> $res[0]["id"]) return "User id does not match between return value and database";
		$scenario_data["user_id"] = $user_id;
		return null;
	}
}
class CreateUser_Remove extends TestFunctionalitiesStep {
	public function getName() { return "Remove user"; }
	public function run(&$scenario_data) {
		PNApplication::$instance->user_management->remove_user($scenario_data["user_id"], true);
		$res = SQLQuery::create()->bypassSecurity()->select("Users")->where("id",$scenario_data["user_id"])->execute();
		if (count($res) > 0) return "User id still exists in database";
		$res = SQLQuery::create()->bypassSecurity()->select("Users")->where("domain","Test")->where("username","test_user")->execute();
		if (count($res) > 0) return "User domain and name still exist in database";
		return null;
	}
}
?>