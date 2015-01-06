<?php 
class service_import_users_from_domain extends Service {
	
	public function getRequiredRights() { return array("manage_users"); }
	
	public function documentation() { echo "Sync users from another domain"; }
	public function inputDocumentation() { echo "domain,new_users,remove_users"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$domain = $input["domain"];
		if ($domain == PNApplication::$instance->current_domain) {
			PNApplication::error("Invalid domain");
			return;
		}
		SQLQuery::startTransaction();
		$insert = array();
		foreach ($input["new_users"] as $username)
			array_push($insert, array(
				"domain"=>$domain,
				"username"=>$username
			));
		if (count($insert) > 0)
			SQLQuery::create()->bypassSecurity()->insertMultiple("Users",$insert);
		if (count($input["remove_users"]) > 0) {
			$remove = SQLQuery::create()->bypassSecurity()->select("Users")->whereValue("Users","domain",$domain)->whereIn("Users","username",$input["remove_users"])->field("id")->executeSingleField();
			if (count($remove) > 0)
				SQLQuery::create()->removeKeys("Users", $remove);
		}
		if (PNApplication::hasErrors()) return;
		SQLQuery::commitTransaction();
		echo "true";
	}
	
}
?>