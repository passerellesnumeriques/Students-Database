<?php 
class service_check_username extends Service {
	
	public function getRequiredRights() { return array("manage_users"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$username = $input["username"];
		$exists = SQLQuery::create()->bypassSecurity()->select("Users")->whereValue("Users","username",$username)->whereValue("Users","domain",PNApplication::$instance->local_domain)->executeSingleRow();
		if ($exists == null) {
			echo "true";
			return;
		}
		PNApplication::errorHTML("User <i>".$username."</i> already exists");
	}
	
}
?>