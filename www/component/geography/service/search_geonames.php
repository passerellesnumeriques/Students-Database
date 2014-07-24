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
		
		$results = $this->search($search_name, $country["code"], @$input["featureCode"]);
		if (count($results) == 0) {
			echo "[]";
			return;
		}
		echo "[";
		$first  =true;
		foreach ($results as $res) {
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
	
	private function search($search_name, $country_code, $featureCode, $resolve = true) {
		set_time_limit(300);
		$url = "http://api.geonames.org/search";
		$url .= "?name=".urlencode(strtolower($search_name));
		$url .= "&country=".$country_code;
		if ($featureCode <> null && $featureCode <> "")
			$url .= "&featureCode=".$featureCode;
		$url .= "&lang=en";
		$url .= "&username=pnsdb&style=FULL&type=json";
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($c);
		if ($result === false) {
			PNApplication::error("Error connecting to GeoNames: ".curl_error($c));
			curl_close($c);
			return array();
		}
		curl_close($c);
		$result = json_decode($result, true);
		if (!isset($result["geonames"])) {
			return array();
		}
		$results = $result["geonames"];
		if ($resolve)
			$this->resolveResultsWithoutBox($results, $search_name, $country_code, $featureCode);
		return $results;
	}
	
	private function resolveResultsWithoutBox(&$results, $original_name, $country_code, $featureCode) {
		$original_name = strtolower($original_name);
		$to_search = array();
		for ($i = 0; $i < count($results); $i++) {
			$res = $results[$i];
			if (isset($res["bbox"])) continue;
			// look for other names to search
			$n = strtolower($res["name"]);
			if ($n <> $original_name && !in_array($n, $to_search))
				array_push($to_search, $n);
			if (isset($res["alternateNames"]))
				foreach ($res["alternateNames"] as $an) {
					if (!isset($an["name"])) continue;
					$n = strtolower($an["name"]);
					if ($n <> $original_name && !in_array($n, $to_search))
						array_push($to_search, $n);
				}
			// remove the result
			array_splice($results, $i, 1);
			$i--;
		}
		foreach ($to_search as $new_name) {
			$new_results = $this->search($new_name, $country_code, $featureCode, false);
			foreach ($new_results as $res) {
				if (!isset($res["bbox"])) continue;
				$found = false;
				foreach ($results as $r) if ($r["geonameId"] == $res["geonameId"]) { $found = true; break; }
				if ($found) continue;
				array_push($results, $res);
			}
		}
	}
}
?>