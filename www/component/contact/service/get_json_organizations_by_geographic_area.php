<?php
class service_get_json_organizations_by_geographic_area extends Service{
	public function getRequiredRights(){return array();}
	public function inputDocumentation(){
		echo "<code>geographic_area</code> the geographic area id";
	}
	public function outputDocumentation(){
		?>
		Return a JSON object containing two attributes:<ul>
			<li><code>from_area</code> the array containing the organizations retrieved from the given geographic area</li>
			<li><code>from_parent_area</code> the array containing the organizations retrieved from the parent area (the area retrieved from the given area have been excluded from this selection) of the given one (if it exists)</li>
		</ul>
		Both attributes contain arrays with the following structure: [{id:organization_id,name:organization_name,addresses:[address_id1, address_id2,...]},...]
		Each address element from addresses array is an object such as {address_id:, geographic_area_id:,geographic_area_text:}
		<?php
	}
	public function documentation(){
		echo "Get all the organizations existings in a given geographic area, and the matching addresses";
	}
	public function execute(&$component,$input){
		if(isset($input["geographic_area"])){
			$organizations = $component->getOrganizationsByGeographicArea($input["geographic_area"]);
			echo "{from_area:".$this->getJsonOrganizations($organizations[0]).", from_parent_area:".$this->getJsonOrganizations($organizations[1])."}";
		} else echo "false";
	}
	
	/**
	 * Get a json array containing the organizations data
	 * @param array $organization_data from contact#getJsonOrganizations method
	 * @return string the json array [{id:organization_id,name:organization_name,addresses:[address_id1, address_id2,...]},...]
	 */
	private function getJsonOrganizations($organization_data){
		$r = "[";
		if(count($organization_data) > 0){
			$first = true;
			foreach($organization_data as $id => $data){
				if(!$first)
					$r .= ", ";
				$first = false;
				$r .= "{id:".json_encode($id).", name:".json_encode($data["name"]).", addresses:[";
				$first_address = true;
				foreach($data["addresses"] as $address){
					if(!$first_address)
						$r .= ", ";
					$first_address = false;
					$r .= "{address_id:".json_encode($address["address"]).",geographic_area_id:".json_encode($address["geographic_area_id"]).", geographic_area_text:".json_encode($address["geographic_area_text"])."}";
				}
				$r .= "]}";
			}
		}
		$r .= "]";
		return $r;
	}
}
?>