<?php 
class service_create_user extends Service {
	
	public function getRequiredRights() { return array("manage_users"); }
	
	public function documentation() { echo "Create a new user linked to an existing people"; }
	public function inputDocumentation() { 
		echo "<ul>";
		echo "<li><code>authentication_system</code>: domain or 'internal'</li>";
		echo "<li><code>username</code>: username</li>";
		echo "<li><code>password</code>: only in case of an internal user</li>";
		echo "<li><code>people_id</code></li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "<code>user_id</code> or false"; }
	
	public function execute(&$component, $input) {
		$username = $input["username"];
		$domain = $input["authentication_system"];
		$people_id = $input["people_id"];
		
		if ($domain == "internal") {
			$is_internal = true;
			$domain = PNApplication::$instance->current_domain;
			$password = $input["password"];
		} else
			$is_internal = false;
		
		SQLQuery::startTransaction();
		try {
			$people = PNApplication::$instance->people->getPeople($people_id);
			if ($people == null) {
				SQLQuery::rollbackTransaction();
				PNApplication::errorHTML("The user <i>".htmlentities($username)."</i> cannot be created because the given linked people is invalid");
				echo "false";
				return;
			}
			$exists = SQLQuery::create()->select("Users")->whereValue("Users","username",$username)->whereValue("Users","domain",$domain)->executeSingleRow();
			if ($exists <> null) {
				if (!$is_internal) {
					SQLQuery::rollbackTransaction();
					PNApplication::errorHTML("The user <i>".htmlentities($username)."</i> already exists");
					echo "false";
					return;
				} else {
					$internal_exists = SQLQuery::create()->bypassSecurity()->select("InternalUser")->whereValue("InternalUser","username",$username)->executeSingleRow();
					if ($internal_exists <> null) {
						SQLQuery::rollbackTransaction();
						PNApplication::errorHTML("The internal user <i>".htmlentities($username)."</i> already exists");
						echo "false";
						return;
					}
				}
			}
			if ($exists == null)
				$user_id = SQLQuery::create()->bypassSecurity()->insert("Users", array("username"=>$username,"domain"=>$domain));
			else
				$user_id = $exists["id"];
			PNApplication::$instance->people->addPeopleType($people_id, "user");
			$link_exists = SQLQuery::create()->bypassSecurity()->select("UserPeople")->whereValue("UserPeople","user",$user_id)->whereValue("UserPeople","people",$people_id)->executeSingleRow();
			if ($link_exists == null)
				SQLQuery::create()->bypassSecurity()->insert("UserPeople", array("user"=>$user_id,"people"=>$people_id));
			if ($is_internal)
				SQLQuery::create()->bypassSecurity()->insert("InternalUser", array("username"=>$username,"password"=>sha1($password)));
			echo "{user_id:".$user_id."}";
			SQLQuery::commitTransaction();
		} catch (Exception $e) {
			SQLQuery::rollbackTransaction();
			PNApplication::error($e);
			echo "false";
			return;
		}
	}
	
}
?>