<?php 
class service_new_organization_type extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Create a new organization type"; }
	public function inputDocumentation() { echo "<code>creator</code>, <code>name</code>"; }
	public function outputDocumentation() { echo "On success, returns the id of the newly created organization type"; }
	
	public function execute(&$component, $input) {
		$id = $component->createOrganizationType($input["creator"], $input["name"]);
		if ($id === false) echo "false";
		else echo "{id:".$id."}";
	}
	
}
?>