<?php 
class service_search_country extends Service {
	
	public function getRequiredRights() { return array("edit_geography"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$country_id = $input["country_id"];
		$country = SQLQuery::create()->select("Country")->whereValue("Country", "id", $country_id)->executeSingleRow();

		$resultGeonames = $this->searchGeonames($country);
		$resultGoogle = $this->searchGoogle($country);
		echo json_encode(array(
			"geonames"=>$resultGeonames,
			"google"=>$resultGoogle
		));
	}

	private function searchGeonames($country) {
		set_time_limit(300);
		$url = "http://api.geonames.org/search";
		$url .= "?q=".urlencode(strtolower($country["name"]));
		$url .= "&country=".$country["code"];
		$url .= "&featureCode=PCLI";
		$url .= "&lang=en";
		$url .= "&username=pnsdb&style=FULL&type=json";
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($c);
		if ($result === false) {
			PNApplication::error("Error connecting to GeoNames: ".curl_error($c));
			curl_close($c);
			return null;
		}
		curl_close($c);
		$result = json_decode($result, true);
		if (!isset($result["geonames"])) {
			return null;
		}
		$results = array();
		foreach ($result["geonames"] as $res) {
			if (!isset($res["bbox"])) continue;
			array_push($results,array(
				"name"=>$res["name"],
				"north"=>$res["bbox"]["north"],
				"south"=>$res["bbox"]["south"],
				"west"=>$res["bbox"]["west"],
				"east"=>$res["bbox"]["east"],
			));
		}
		return $results;
	}
	
	private function searchGoogle($country) {
		require_once("component/google/GoogleAPI.inc");
		return GoogleAPI::PlacesTextSearch($country["name"], $country["code"], "country");
	}
}
?>