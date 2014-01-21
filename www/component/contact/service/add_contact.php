<?php 
class service_add_contact extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Add a contact to a people or to an organization"; }
	public function input_documentation() {
?>
	<ul>
		<li><code>owner_type</code>: "people" or "organization"</li>
		<li><code>owner_id</code>: people id or organization id</li>
		<li><code>contact</code>: the Contact structure</li>
	</ul>
<?php
	}
	public function output_documentation() { echo "<code>id</code> the id of the contact created"; }
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		if ($input["owner_type"] == "people") $table_name = "People_contact";
		else if ($input["owner_type"] == "organization") $table_name = "Organization_contact";
		else {
			PNApplication::error("Invalid owner_type");
			echo "false";
			return;
		}
		$table = DataModel::get()->getTable($table_name);
		if (!$table->acceptInsert(array($input["owner_type"]=>$input["owner_id"]))) {
			PNApplication::error("You are not allowed to add a contact for this ".$input["owner_type"]);
			echo "false";
			return;
		}
		try {
			$contact_id = SQLQuery::create()->bypass_security()->insert("Contact", array("type"=>$input["contact"]["type"],"contact"=>$input["contact"]["contact"],"sub_type"=>$input["contact"]["sub_type"]));
		} catch (Exception $ex) {
			$contact_id = 0;
			PNApplication::error($ex);
		}
		if ($contact_id == 0) {
			echo "false";
			return;
		}
		try {
			SQLQuery::create()->insert($table_name, array($input["owner_type"]=>$input["owner_id"],"contact"=>$contact_id));
		} catch (Exception $ex) {
			PNApplication::error($ex);
			SQLQuery::create()->remove_key("Contact", $contact_id);
			echo "false";
			return;
		}
		echo "{id:".$contact_id."}";
	}
}
?>