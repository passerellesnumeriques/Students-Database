<?php 
class service_search_google extends Service {
	
	public function getRequiredRights() { return array("edit_geography"); }
	
	public function documentation() { echo "Call the Google Places API to search for a place"; }
	public function inputDocumentation() { echo "country_id, name"; }
	public function outputDocumentation() { echo "the result from Google"; }
	
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