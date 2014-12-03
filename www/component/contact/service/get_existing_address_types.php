<?php 
class service_get_existing_address_types extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Retrieve list of address types existing in database"; }
	public function inputDocumentation() { echo "type: people or organization"; }
	public function outputDocumentation() { echo "list of string"; }
	
	public function execute(&$component, $input) {
		$table = $input["type"] == "people" ? "PeopleAddress" : "OrganizationAddress";
		echo json_encode(
			SQLQuery::create()->select($table)
				->join($table,"PostalAddress",array("address"=>"id"))
				->field("PostalAddress","address_type")
				->distinct()
				->executeSingleField()
		);
	}
	
}
?>