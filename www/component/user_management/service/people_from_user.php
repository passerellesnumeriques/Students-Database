<?php 
class service_people_from_user extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Retrieve the people ID from the given user"; }
	public function inputDocumentation() { echo "domain and username"; }
	public function outputDocumentation() { echo "people_id"; }
	
	public function execute(&$component, $input) {
		if ($input["domain"] == PNApplication::$instance->local_domain) {
			require_once("component/people/service/picture.php");
			$service = new service_picture();
			$people_id = SQLQuery::create()->bypassSecurity()
				->select("Users")
				->whereValue("Users", "username", $input["username"])
				->join("Users", "UserPeople", array("id"=>"user"))
				->field("UserPeople", "people", "people_id")
				->executeSingleValue();
			echo "{people_id:".json_encode($people_id)."}";
		} else {
			echo "false";
		}
	}
	
}
?>