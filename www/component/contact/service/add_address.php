<?php 
class service_add_address extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Add a postal address to a people or to an organization"; }
	public function input_documentation() {
?>
	<ul>
		<li><code>type</code>: "people" or "organization"</li>
		<li><code>type_id</code>: the people id or organization id</li>
		<li><code>address</code>: a PostalAddress object</li>
	</ul>
<?php
	}
	public function output_documentation() { echo "<code>id</code> the id of the address created"; }
	
	public function execute(&$component, $input) {
		$address_id = false;
				
		if($input["type"] == "organization")
			$address_id = PNApplication::$instance->contact->addAddressToOrganization($input["type_id"],$input["address"]);
		else if($input["type"] == "people")
			$address_id = PNApplication::$instance->contact->addAddressToPeople($input["type_id"],$input["address"]);
		if($address_id == false)
			echo "false";
		else echo "{id:".$address_id."}";
	}
}
?>