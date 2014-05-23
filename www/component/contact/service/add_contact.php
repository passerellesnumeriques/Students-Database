<?php 
class service_add_contact extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Add a contact to a people or to an organization"; }
	public function inputDocumentation() {
?>
	<ul>
		<li><code>owner_type</code>: "people" or "organization"</li>
		<li><code>owner_id</code>: people id or organization id</li>
		<li><code>contact</code>: the Contact structure</li>
	</ul>
<?php
	}
	public function outputDocumentation() { echo "<code>id</code> the id of the contact created"; }
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		if ($input["owner_type"] == "people")
			$contact_id = $component->addContactToPeople($input["owner_id"], $input["contact"]);
		else if ($input["owner_type"] == "organization")
			$contact_id = $component->addContactToOrganization($input["owner_id"], $input["contact"]);
		else {
			PNApplication::error("Invalid owner_type");
			$contact_id = false;
		}
		if ($contact_id === false) echo "false";
		else echo "{id:".$contact_id."}";
	}
}
?>