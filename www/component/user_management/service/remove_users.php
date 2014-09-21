<?php 
class service_remove_users extends Service {
	
	public function getRequiredRights() { return array("manage_users"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$users_ids = $input["users"];
		SQLQuery::startTransaction();
		$q = SQLQuery::create()->bypassSecurity()
			->select("Users")
			->whereIn("Users","id",$users_ids)
			->join("Users","InternalUser",array("username"=>"username","domain"=>array(PNApplication::$instance->local_domain)))
			->join("Users","UserPeople",array("id"=>"people"))
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
		
		$peoples_removed = array();
		$peoples_update = array();
		foreach ($users as $u) if ($u["people_types"] == "/user/") array_push($peoples_removed, $u["people_id"]); else array_push($peoples_update, $u);
		
		if (count($peoples_removed) > 0)
			SQLQuery::create()->bypassSecurity()->removeKeys("People",$peoples_removed);
		
		foreach ($peoples_update as $p) {
			$types = PNApplication::$instance->people->parseTypes($p["people_types"]);
			for ($i = 0; $i < count($types); $i++)
				if ($types[$i] == "user") { array_splice($types, $i, 1); $i--; }
			$s = "";
			foreach ($types as $t) $s .= "/$t/";
			SQLQuery::create()->bypassSecurity()->updateByKey("People", $p["people_id"], array("types"=>$s));
		}
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>