<?php 
class service_unassign_organization_type extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Unassign a type from an organization"; }
	public function input_documentation() { echo "<code>organization</code>: organization id, <code>type</code>: type id"; }
	public function output_documentation() { echo "Return true on success"; }
	
	public function execute(&$component, $input) {
		if ($component->unassignOrganizationType($input["organization"], $input["type"]))
			echo "true";
		else
			echo "false";
	}
	
}
?>