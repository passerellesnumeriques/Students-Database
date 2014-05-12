<?php 
class service_assign_organization_type extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Assign a type to an organization"; }
	public function inputDocumentation() { echo "<code>organization</code>, <code>type</code>"; }
	public function outputDocumentation() { echo "Return true on success"; }
	
	public function execute(&$component, $input) {
		if ($component->assignOrganizationType($input["organization"], $input["type"]))
			echo "true";
		else
			echo "false";
	}
	
}
?>