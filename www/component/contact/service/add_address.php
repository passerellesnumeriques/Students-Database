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
		if(isset($input["table"]) && isset($input["key"])){
			$address_id = false;
			$new_address = array(
				"country" => $input["country"],
				"street_number" => $input["street_number"],
				"building" => $input["building"],
				"unit" => $input["unit"],
				"additional" => $input["additional"],
				"address_type" => $input["address_type"],
			);
			
			if(isset($input["street_name"]))
				$new_address["street"] = $input["street_name"];
			else if(isset($input["street"]))
				$new_address["street"] = $input["street"];
			else
				$new_address["street"] = null;
				
			if(isset($input["geographic_area"]) && isset($input["geographic_area"]["id"]))
				$new_address["geographic_area"] = $input["geographic_area"]["id"];
			else
				$new_address["geographic_area"] = null;
				
			if($input["table"] == "Organization_address")
				$address_id = PNApplication::$instance->contact->add_address_to_organization($input["key"],$new_address);
			else if($input["table"] == "People_address")
				$address_id = PNApplication::$instance->contact->add_address_to_people($input["key"],$new_address);
			if($address_id == false)
				echo "false";
			else echo "{id:".$address_id."}";
		} else echo "false";
	}
}
?>