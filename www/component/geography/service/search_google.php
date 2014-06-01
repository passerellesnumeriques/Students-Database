<?php 
class service_search_google extends Service {
	
	public function getRequiredRights() { return array("edit_geography"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$country_id = $input["country_id"];
		$id = $input["area_id"];
		
		$country = SQLQuery::create()->select("Country")->whereValue("Country", "id", $country_id)->executeSingleRow();
		
		$areas = array();
		while ($id <> null) {
			$area = SQLQuery::create()->
				select("GeographicArea")
				->whereValue("GeographicArea", "id", $id)
				->executeSingleRow();
			array_push($areas, $area);
			$id = $area["parent"];
		}
		
		$results = array();
		for ($nb = count($areas); $nb > 0; $nb--) {
			$query = "";
			for ($i = 0; $i < $nb; $i++) {
				if ($i > 0) $query .= " ";
				$query .= $areas[$i]["name"];
			}
			$query .= " ".$country["name"];
			$r = $this->searchGoogle($country, $query);
			if ($r === null) break;
			$this->mergeResults($results, $r);
		}
		
		echo "[";
		$first  =true;
		foreach ($results as $res) {
			if (!isset($res["geometry"])) continue;
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "name:".json_encode($res["name"]);
			echo ",full_name:".json_encode($res["formatted_address"]);
			if (isset($res["geometry"]["viewport"])) {
				echo ",north:".$res["geometry"]["viewport"]["northeast"]["lat"];
				echo ",south:".$res["geometry"]["viewport"]["southwest"]["lat"];
				echo ",west:".$res["geometry"]["viewport"]["southwest"]["lng"];
				echo ",east:".$res["geometry"]["viewport"]["northeast"]["lng"];
			} else if (isset($res["geometry"]["location"])) {
				echo ",lat:".$res["geometry"]["location"]["lat"];
				echo ",lng:".$res["geometry"]["location"]["lng"];
			}
			echo "}";
		}
		echo "]";
	}
	
	private function searchGoogle($country, $query) {
		set_time_limit(300);
		$url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=".urlencode($query);
		$url .= "&components=country:".$country["code"];
		$url .= "&types=political";
		$url .= "&sensor=false";
		$url .= "&key=AIzaSyBhG4Hn5zmbXcALGQtAPJDkUj2hDSZdVSU";
		//echo $url."<br/><br/>\r\n\r\n";
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($c);
		if ($result === false) {
			PNApplication::error("Error connecting to Google: ".curl_error($c));
			curl_close($c);
			return null;
		}
		curl_close($c);
		//echo $result."<br/><br/>\r\n\r\n";
		$result = json_decode($result, true);
		if (isset($result["status"]) && $result["status"] <> "OK" && $result["status"] <> "ZERO_RESULTS")
			PNApplication::error("Google replied ".$result["status"].(isset($result["error_message"]) ? ": ".$result["error_message"] : ""));
		if (!isset($result["results"])) return array();
		return $result["results"];
	}
	
	private function mergeResults(&$results, $new_results) {
		foreach ($new_results as $nr) {
			$found = false;
			foreach ($results as $r) if ($r["id"] == $nr["id"]) { $found = true; break; }
			if (!$found) array_push($results, $nr);
		}
	}
	
}
?>