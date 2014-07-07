<?php 
class service_search_geonames extends Service {
	
	public function getRequiredRights() { return array("edit_geography"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$country_id = $input["country_id"];
		$search_name = $input["name"];
		$country = SQLQuery::create()->select("Country")->whereValue("Country", "id", $country_id)->executeSingleRow();
		
		set_time_limit(300);
		$url = "http://api.geonames.org/search";
		$url .= "?q=".urlencode(strtolower($search_name));
		$url .= "&country=".$country["code"];
		if (isset($input["featureCode"]))
			$url .= "&featureCode=".$input["featureCode"];
		$url .= "&lang=en";
		$url .= "&username=pnsdb&style=FULL&type=json";
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($c);
		if ($result === false) {
			PNApplication::error("Error connecting to GeoNames: ".curl_error($c));
			curl_close($c);
			echo "[]";
			return;
		}
		curl_close($c);
		$result = json_decode($result, true);
		if (!isset($result["geonames"])) {
			echo "[]";
			return;
		}
		echo "[";
		$first  =true;
		foreach ($result["geonames"] as $res) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "name:".json_encode($res["name"]);
			$container = "";
			for ($admin_index = 5; $admin_index >= 1; $admin_index--) {
				if (!isset($res["adminName".$admin_index])) continue;
				if ($res["adminName".$admin_index] == "") continue;
				if ($res["adminName".$admin_index] == $res["name"]) continue;
				if ($container <> "") $container .= ", ";
				$container .= $res["adminName".$admin_index];
			}
			echo ",full_name:".json_encode($res["name"].", ".$container);
			if (isset($res["bbox"])) {
				echo ",north:".$res["bbox"]["north"];
				echo ",south:".$res["bbox"]["south"];
				echo ",west:".$res["bbox"]["west"];
				echo ",east:".$res["bbox"]["east"];
			} else if (isset($res["lat"]) && $res["lat"] <> "") {
				echo ",lat:".$res["lat"];
				echo ",lng:".$res["lng"];
			}
			echo "}";
		}
		echo "]";
	}
}
?>