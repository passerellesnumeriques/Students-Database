<?php 
class service_internal_to_authentication_system extends Service {
	
	public function getRequiredRights() { return array("manage_users"); }
	
	public function documentation() { echo "Move a user from internal to external"; }
	public function inputDocumentation() { echo "domain, username"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		if ($input["domain"] <> PNApplication::$instance->local_domain) {
			PNApplication::error("User is not on the local domain");
			return;
		}
		SQLQuery::startTransaction();
		$q = SQLQuery::create()->bypassSecurity()
			->select("Users")
			->whereValue("Users", "domain", $input["domain"])
			->whereValue("Users", "username", $input["username"])
			->join("Users","InternalUser",array("username"=>"username","domain"=>array(PNApplication::$instance->local_domain)))
			->field("Users","id","user_id")
			->field("Users","username","username")
			->field("InternalUser","password","password");
			;
		$user = $q->executeSingleRow();
		
		if ($user == null) {
			PNApplication::error("Unknown user");
			return;
		}
		if ($user["password"] == null) {
			PNApplication::errorHTML("User <i>".$input["username"]."</i> is not internal");
			return;
		}
		if ($user["username"] == "admin") {
			PNApplication::errorHTML("You cannot remove the user <i>admin</i>, in order to make sur there will be always an administrator user in the system.");
			return;
		}
		
		SQLQuery::create()->bypassSecurity()->removeKey("InternalUser", $user["username"]);
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>