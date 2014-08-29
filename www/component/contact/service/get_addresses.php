<?php 
class service_get_addresses extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Retrieve the addresses from peoples or organizations"; }
	public function inputDocumentation() { echo "type(people or organization), ids"; }
	public function outputDocumentation() { echo "a list of addresses for each given id"; }
	
	public function execute(&$component, $input) {
		if (count($input["ids"]) == 0) { echo "[]"; return; }
		if ($input["type"] == "people")
			$addresses = $component->getPeoplesAddresses($input["ids"]);
		else
			$addresses = $component->getOrganizationsAddresses($input["ids"]);
		$texts = GeographyJSON::prepareGeographicAreasTexts($addresses);
		echo "[";
		$first = true;
		foreach ($input["ids"] as $id) {
			$list = array();
			$list_texts = array();
			for ($i = count($addresses)-1; $i >= 0; --$i) {
				$a = $addresses[$i];
				if ($a["address_".$input["type"]."_id"] == $id) {
					array_push($list, $a);
					array_push($list_texts, $texts[$i]);
				}
			}
			if ($first) $first = false; else echo ",";
			echo ContactJSON::PostalAddresses($list, $list_texts);
		}
		echo "]";
	}
	
}
?>