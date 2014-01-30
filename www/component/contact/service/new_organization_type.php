<?php 
class service_new_organization_type extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Create a new organization type"; }
	public function input_documentation() { echo "<code>creator</code>, <code>name</code>"; }
	public function output_documentation() { echo "On success, returns the id of the newly created organization type"; }
	
	public function execute(&$component, $input) {
		$id = $component->createOrganizationType($input["creator"], $input["name"]);
		if ($id === false) echo "false";
		else echo "{id:".$id."}";
	}
	
}
?>