<?php 
class service_add_address extends Service {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function documentation() { echo "Add a postal address to a people or to an organization"; }
	public function input_documentation() {
?>
	<ul>
		<li><code>table</code>: "People_contact" or "Organization_contact", the table joining the contact to the entity it belongs to</li>
		<li><code>column</code>: the column joining the people or organization</li>
		<li><code>key</code>: the people id or organization id</li>
		<li><code>country</code></li>
		<li><code>geographic_area</code></li>
		<li><code>street</code></li>
		<li><code>street_number</code></li>
		<li><code>building</code></li>
		<li><code>unit</code></li>
		<li><code>additional</code></li>
		<li><code>address_type</code>: the sub type of contact (i.e. Work, Home...)</li>
	</ul>
<?php
	}
	public function output_documentation() { echo "<code>id</code> the id of the address created"; }
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		$table = DataModel::get()->getTable($input["table"]);
		if (!$table->acceptInsert(array($input["column"]=>$input["key"]))) {
			PNApplication::error("You are not allowed to add an address for this people or organization");
			echo "false";
			return;
		}
		try {
			$address_id = SQLQuery::create()->bypass_security()->insert("Postal_address", 
				array(
					"country"=>$input["country"],
					"geographic_area"=>$input["geographic_area"],
					"street"=>$input["street"],
					"street_number"=>$input["street_number"],
					"building"=>$input["building"],
					"unit"=>$input["unit"],
					"additional"=>$input["additional"],
					"address_type"=>$input["address_type"]
				)
			);
		} catch (Exception $ex) {
			$address_id = 0;
			PNApplication::error($ex);
		}
		if ($address_id == 0) {
			echo "false";
			return;
		}
		try {
			SQLQuery::create()->insert($input["table"], array($input["column"]=>$input["key"],"address"=>$address_id));
		} catch (Exception $ex) {
			PNApplication::error($ex);
			SQLQuery::create()->remove_key("Postal_address", $address_id);
			echo "false";
			return;
		}
		echo "{id:".$address_id."}";
	}
}
?>