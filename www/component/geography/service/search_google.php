<?php 
class service_search_google extends Service {
	
	public function getRequiredRights() { return array("edit_geography"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$country_id = $input["country_id"];
		$search_name = $input["name"];
		$country = SQLQuery::create()->select("Country")->whereValue("Country", "id", $country_id)->executeSingleRow();
		
		set_time_limit(300);
		require_once("component/google/GoogleAPI.inc");
		echo json_encode(GoogleAPI::PlacesTextSearch($search_name, $country["code"], $input["types"]));
	}
}
?>