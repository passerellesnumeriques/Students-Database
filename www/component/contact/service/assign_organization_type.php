<?php 
class service_assign_organization_type extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Assign a type to an organization"; }
	public function input_documentation() { echo "<code>organization</code>, <code>type</code>"; }
	public function output_documentation() { echo "Return true on success"; }
	
	public function execute(&$component, $input) {
		if ($component->assignOrganizationType($input["organization"], $input["type"]))
			echo "true";
		else
			echo "false";
	}
	
}
?>