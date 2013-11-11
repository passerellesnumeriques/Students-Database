<?php 
class service_new_organization_type extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Create a new organization type"; }
	public function input_documentation() { echo "<code>creator</code>, <code>name</code>"; }
	public function output_documentation() { echo "On success, returns the id of the newly created organization type"; }
	
	public function execute(&$component, $input) {
		require_once("component/contact/OrganizationPlugin.inc");
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof OrganizationPlugin)) continue;
			if ($c->getOrganizationCreator() == $input["creator"]) {
				if (!($c->canInsertOrganization())) {
					PNApplication::error("You are not allowed to create orgnization in ".$input["creator"]);
					return;
				}
				break;
			}
		}
		
		try {
			$id = SQLQuery::create()->insert("Organization_type", array("creator"=>$input["creator"], "name"=>$input["name"]));
			echo "{id:".$id."}";
		} catch (Exception $e) {
			PNApplication::error($e);
		}
	}
	
}
?>