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
		if(isset($input["address_id"])){
			$id = $input["address_id"];
			
			$q = SQLQuery::create()->select("Postal_address")
							->field('id')
							->field("Postal_address","country","country_id")
							->field("Postal_address","geographic_area","area_id")
							->field("street")
							->field("street_number")
							->field("building")
							->field("unit")
							->field("additional")
							->field("address_type")
							->where("id ='".$id."'");
							
			$adrs = $q->execute();
			
			if(!isset($adrs[0]['id'])) echo "{}";
			else{
				echo "{";
				echo "id: ".json_encode($adrs[0]["id"]).", ";
				echo "country_id: ".json_encode($adrs[0]["country_id"]).", ";
				echo "area_id: ".json_encode($adrs[0]["area_id"]).", ";
				echo "street: ".json_encode($adrs[0]["street"]).", ";
				echo "street_number: ".json_encode($adrs[0]["street_number"]).", ";
				echo "building: ".json_encode($adrs[0]["building"]).", ";
				echo "unit: ".json_encode($adrs[0]["unit"]).", ";
				echo "additional: ".json_encode($adrs[0]["additional"]).", ";
				echo "address_type: ".json_encode($adrs[0]["address_type"]).", ";
				echo "area_text:";
				if(isset($adrs[0]['area_id']) && $adrs[0]['area_id'] <> null){
					$q = SQLQuery::create()->select("Geographic_area")
								->field("parent")
								->field("name")
								->where("id = '".$adrs[0]['area_id']."'");
					$areas = $q->execute();
					$q_country = SQLQuery::create()->select("Country")
								->field("name")
								->field("code")
								->join("Country","Country_division",array("id"=>"country"))
								->join("Country_division","Geographic_area",array("id"=>"country_division"))
								->where("Geographic_area.id = '".$adrs[0]['area_id']."'");
					$country = $q_country->execute();
					if(isset($areas[0]["name"])){
						echo "[";
						echo json_encode($areas[0]["name"]);
						$parent = $areas[0]['parent'];
						while($parent <> null){
							$q = SQLQuery::create()->select("Geographic_area")
											->field("parent")
											->field("name")
											->where("id = '".$parent."'");
							$ar = $q->execute();
							echo ", '".$ar[0]['name']."'";
							$parent = $ar[0]["parent"];
						}
						echo "]";
					}	
				} else echo "[]";
				if(isset($country[0]["name"])) echo ", country_name:".json_encode($country[0]["name"]);
				if(isset($country[0]["code"])) echo ", country_code:".json_encode($country[0]["code"]);
				echo "}";
			}
		}
	}
}	
?>