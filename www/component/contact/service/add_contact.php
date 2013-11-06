<?php 
class service_add_contact extends Service {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function documentation() { echo "Add a contact to a people or to an organization"; }
	public function input_documentation() {
?>
	<ul>
		<li><code>table</code>: "People_contact" or "Organization_contact", the table joining the contact to the entity it belongs to</li>
		<li><code>column</code>: the column joining the people or organization</li>
		<li><code>key</code>: the people id or organization id</li>
		<li><code>type</code>: contact type (email, phone, im)</li>
		<li><code>contact</code>: the text containing the contact</li>
		<li><code>sub_type</code>: the sub type of contact (i.e. Work, Home...)</li>
	</ul>
<?php
	}
	public function output_documentation() { echo "<code>id</code> the id of the contact created"; }
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		$table = DataModel::get()->getTable($input["table"]);
		if (!$table->acceptInsert(array($input["column"]=>$input["key"]))) {
			PNApplication::error("You are not allowed to add a contact for this people or organization");
			echo "false";
			return;
		}
		try {
			$contact_id = SQLQuery::create()->bypass_security()->insert("Contact", array("type"=>$input["type"],"contact"=>$input["contact"],"sub_type"=>$input["sub_type"]));
		} catch (Exception $ex) {
			$contact_id = 0;
			PNApplication::error($ex);
		}
		if ($contact_id == 0) {
			echo "false";
			return;
		}
		try {
			SQLQuery::create()->insert($input["table"], array($input["column"]=>$input["key"],"contact"=>$contact_id));
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