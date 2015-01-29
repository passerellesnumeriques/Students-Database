<?php 
class service_remove_user extends Service {
	
	public function getRequiredRights() { return array("manage_users"); }
	
	public function documentation() { echo "Remove a user from the software"; }
	public function inputDocumentation() { echo "domain, username"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		$q = SQLQuery::create()->bypassSecurity()
			->select("Users")
			->whereValue("Users", "domain", $input["domain"])
			->whereValue("Users", "username", $input["username"])
			->join("Users","InternalUser",array("username"=>"username","domain"=>array(PNApplication::$instance->local_domain)))
			->join("Users","UserPeople",array("id"=>"user"))
			->field("Users","id","user_id")
			->field("Users","username","username")
			->field("InternalUser","password","password");
			;
		PNApplication::$instance->people->joinPeople($q, "UserPeople", "people", false);
		$user = $q->executeSingleRow();
		
		if ($user == null) {
			PNApplication::error("Unknown user");
			return;
		}
		if ($user["password"] <> null && $user["username"] == "admin") {
			PNApplication::errorHTML("You cannot remove the user <i>admin</i>, in order to make sur there will be always an administrator user in the system.");
			return;
		} 
		
		SQLQuery::create()->bypassSecurity()->removeKey("Users", $user["user_id"]);
		if ($user["password"] <> null)
			SQLQuery::create()->bypassSecurity()->removeKey("InternalUser", $user["username"]);
		
		PNApplication::$instance->people->removePeoplesType(array($user["people_id"]), "user");
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>