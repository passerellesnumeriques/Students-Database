<?php 
class service_get_organizations extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Retrieve all information about organizations"; }
	public function inputDocumentation() { echo "<code>ids</code>: list of organizations' ID"; }
	public function outputDocumentation() { echo "A list of Organization JSON structure, fully filled."; }
	
	public function execute(&$component, $input) {
		require_once("component/contact/ContactJSON.inc");
		echo ContactJSON::OrganizationsFromIDs($input["ids"]);
	}
	
}
?>