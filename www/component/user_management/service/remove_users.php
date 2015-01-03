<?php 
class service_remove_users extends Service {
	
	public function getRequiredRights() { return array("manage_users"); }
	
	public function documentation() { echo "Remove users from the software"; }
	public function inputDocumentation() { echo "<code>users</code>: list of users' id"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$users_ids = $input["users"];
		SQLQuery::startTransaction();
		$q = SQLQuery::create()->bypassSecurity()
			->select("Users")
			->whereIn("Users","id",$users_ids)
			->join("Users","InternalUser",array("username"=>"username","domain"=>array(PNApplication::$instance->local_domain)))
			->join("Users","UserPeople",array("id"=>"user"))
			->field("Users","username","username")
			->field("InternalUser","password","password");
			;
		PNApplication::$instance->people->joinPeople($q, "UserPeople", "people", false);
		$users = $q->execute();
		
		foreach ($users as $u)
			if ($u["username"] == "admin") {
				PNApplication::errorHTML("You cannot remove the user <i>admin</i>, in order to make sur there will be always an administrator user in the system.");
				return;
			} 
		
		SQLQuery::create()->bypassSecurity()->removeKeys("Users", $users_ids);
		$internal = array();
		foreach ($users as $u) if ($u["password"] <> null) array_push($internal, $u["username"]);
		if (count($internal) > 0)
			SQLQuery::create()->bypassSecurity()->removeKeys("InternalUser", $internal);
		
		$peoples_ids = array();
		foreach ($users as $u) array_push($peoples_ids, $u["people_id"]);
		PNApplication::$instance->people->removePeoplesType($peoples_ids, "user");
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>