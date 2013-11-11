<?php 
class service_unassign_organization_type extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Unassign a type from an organization"; }
	public function input_documentation() { echo "<code>organization</code>, <code>type</code>"; }
	public function output_documentation() { echo "Return true on success"; }
	
	public function execute(&$component, $input) {
		try {
			SQLQuery::create()->remove_key("Organization_types", array("organization"=>$input["organization"], "type"=>$input["type"]));
			echo "true";
		}catch (Exception $e) {
			PNApplication::error($e);
		}
	}
	
}
?>