<?php 
class service_update_address extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	public function execute(&$component, $input) {
		if(isset($input["address"])){
			$address = array(
				"country" => $input["address"]["country"],
				"street_number" => $input["address"]["street_number"],
				"building" => $input["address"]["building"],
				"unit" => $input["address"]["unit"],
				"additional" => $input["address"]["additional"],
				"address_type" => $input["address"]["address_type"],
			);
			
			if(isset($input["address"]["street_name"]))
				$address["street"] = $input["address"]["street_name"];
			else if(isset($input["address"]["street"]))
				$address["street"] = $input["address"]["street"];
			else
				$address["street"] = null;
				
			if(isset($input["address"]["geographic_area"]) && isset($input["address"]["geographic_area"]["id"]))
				$address["geographic_area"] = $input["address"]["geographic_area"]["id"];
			else
				$address["geographic_area"] = null;
				
			if($input["address"]["id"] == -1 || $input["address"]["id"] == "-1"){
				// This is an insert
				$id = SQLQuery::create()
					->insert("Postal_address",$address);
				if(PNApplication::has_errors())
					echo "false";
				else
					echo "{id:".$id."}";
			} else {
				// This is an update
				$q = SQLQuery::create()
					->updateByKey("Postal_address",$input["address"]["id"],$address);
				if(PNApplication::has_errors())
					echo "false";
				else
					echo "true";
			}
		} else echo "false";
	}
}
?>