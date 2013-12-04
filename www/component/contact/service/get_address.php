<?php
class service_get_address extends Service{
	public function get_required_rights(){return array();}
	public function input_documentation(){}//TODO
	public function output_documentation(){
		echo "In case the address is already set with a geographic area id:";
		echo "<br/>Returns an object (example given with the Talamban area): {id, country_id,country_name,country_code,area_id,street, street_number,building,unit,additional,addess_type,area_text:[\'Talamban\', \'Cebu City\', \'Cebu\']}";
		echo "<ul><li>If empty address: returns {}</li><li>If empty area: the array corresponding to the area_text attribute is empty: []</li></ul>";
	}
	public function documentation(){}//TODO
	public function execute(&$component,$input){
		if(isset($input["id"])){
			// echo PNApplication::$instance->contact->get_json_address($input["id"]);
			echo PNApplication::$instance->contact->get_json_address_good_format_for_address_text($input["id"]);
		} else echo "false";
	}
}	
?>