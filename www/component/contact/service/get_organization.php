<?php 
class service_get_organization extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Retrieve all information about an organization"; }
	public function inputDocumentation() { echo "<code>id</code>: organization's ID"; }
	public function outputDocumentation() { echo "An Organization JSON structure, fully filled."; }
	
	public function execute(&$component, $input) {
		require_once("component/contact/ContactJSON.inc");
		echo ContactJSON::OrganizationFromID($input["id"]);
	}
	
}
?>