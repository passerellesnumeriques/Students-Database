<?php 
class service_get_existing_contact_types extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Retrieve list of address types existing in database"; }
	public function inputDocumentation() { echo "type: email, phone or IM"; }
	public function outputDocumentation() { echo "list of string"; }
	
	public function execute(&$component, $input) {
		echo json_encode(
			SQLQuery::create()->select("Contact")
				->whereValue("Contact","type",$input["type"])
				->field("Contact","sub_type")
				->distinct()
				->executeSingleField()
		);
	}
	
}
?>