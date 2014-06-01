<?php 
class service_search_coordinates extends Service {
	
	public function getRequiredRights() { return array("edit_geography"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) { return isset($input["debug"]) && $input["debug"] == "debug" ? "text/plain" : "text/json"; }
	
	private $debug = false;
	private $setted = array();
	public function execute(&$component, $input) {
		$country_id = $input["country_id"];
		$id = $input["area_id"];
		$this->debug = @$input["debug"] == "debug";
		
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
		
		$results = $this->search($country, $areas);
		if (count($results) == 0) {
			if ($this->debug)
				echo "No result found for ".$areas[0]["name"].": try from google with parent check\r\n";
			if (count($areas) == 1 || !isset($areas[1]["north"])) {
				if ($this->debug)
					echo " - no geographic info from the parent\r\n";
			} else {
				$google = $this->searchGoogle($country, $areas);
				for ($i = 0; $i < count($google); $i++)
					if (!$this->boxContains($areas[1], $google[$i])) {
						array_splice($google, $i, 1);
						$i--;
					}
				if (count($google) == 1) {
					if ($this->debug)
						echo " - Google match!\r\n";
					unset($google[0]["full_name"]);
					SQLQuery::create()->updateByKey("GeographicArea", $areas[0]["id"], $google[0]);
					$areas[0]["north"] = $google[0]["north"];
					$areas[0]["south"] = $google[0]["south"];
					$areas[0]["east"] = $google[0]["east"];
					$areas[0]["west"] = $google[0]["west"];
					array_push($this->setted, $areas[0]);
				} else {
					if ($this->debug) echo " - ".count($google)." google results match, we cannot determine\r\n";
				}
			}
		}
		echo json_encode($this->setted);
	}
	
	private function boxContains($box, $sub, $debug = false) {
		if ($debug && $this->debug) {
			echo "      check contains:\r\n";
			echo "         sub.south = ".floatval($sub["south"])."\r\n";
			echo "         sub.north = ".floatval($sub["north"])."\r\n";
			echo "         box.south = ".floatval($box["south"])."\r\n";
			echo "         box.north = ".floatval($box["north"])."\r\n";
			echo "         sub.west = ".floatval($sub["west"])."\r\n";
			echo "         sub.east = ".floatval($sub["east"])."\r\n";
			echo "         box.west = ".floatval($box["west"])."\r\n";
			echo "         box.east = ".floatval($box["east"])."\r\n";
		}
		if (floatval($sub["south"]) >= floatval($box["south"]) &&
			floatval($sub["south"]) <= floatval($box["north"]) &&
			floatval($sub["north"]) >= floatval($box["south"]) &&
			floatval($sub["north"]) <= floatval($box["north"]) &&
			floatval($sub["west"]) >= floatval($box["west"]) &&
			floatval($sub["west"]) <= floatval($box["east"]) &&
			floatval($sub["east"]) >= floatval($box["west"]) &&
			floatval($sub["east"]) <= floatval($box["east"])) {
			if ($debug && $this->debug)
				echo "         TRUE\r\n";
			return true;
		}
		if ($debug && $this->debug)
			echo "         FALSE\r\n";
		return false;
	}
	
	private function search($country, &$areas) {
		if ($this->debug) echo "Search for ".$areas[0]["name"].":\r\n";
		if ($areas[0]["north"] <> null) {
			if ($this->debug) echo " - Already set!\r\n";
			return array(array(
				"north"=>$areas[0]["north"],
				"south"=>$areas[0]["south"],
				"east"=>$areas[0]["east"],
				"west"=>$areas[0]["west"]
			));
		}
		$results = $this->searchGeonames($country, $areas);
		
		if (count($results) == 0) {
			if ($this->debug) echo " - ".$areas[0]["name"]." Not Found!\r\n";
			return array();
		}
		if ($this->debug) echo " - Results found for ".$areas[0]["name"].": ".count($results)."\r\n";
		if ($this->debug) echo " - Check with parent areas\r\n";
		for ($i = 1; $i < count($areas); $i++) {
			if (!isset($areas[$i]["north"])) continue;
			for ($j = 0; $j < count($results); $j++)
				if (!$this->boxContains($areas[$i], $results[$j])) {
					array_splice($results, $j, 1);
					$j--;
				}
		}
		if ($this->debug) echo "   - Results after check: ".count($results)."\r\n";
		if (count($results) == 0) return array();
		if (count($results) == 1) {
			if ($this->debug) echo " - FOUND IT!\r\n";
			SQLQuery::create()->updateByKey("GeographicArea", $areas[0]["id"], $results[0]);
			$areas[0]["north"] = $results[0]["north"];
			$areas[0]["south"] = $results[0]["south"];
			$areas[0]["east"] = $results[0]["east"];
			$areas[0]["west"] = $results[0]["west"];
			array_push($this->setted, $areas[0]);
			return $results;
		}
		
		if ($this->debug) echo "More than 1 result: get Google Places results\r\n";
		$google = $this->searchGoogle($country, $areas);
		if ($this->debug) echo "  Try to search for sub areas\r\n";
		
		$sub_areas = SQLQuery::create()
			->select("GeographicArea")
			->whereValue("geographicArea", "parent", $areas[0]["id"])
			->execute();
		
		$score = array();
		$scoreGoogle = array();
		for ($i = 0; $i < count($results); $i++) $score[$i] = 0.00;
		for ($i = 0; $i < count($google); $i++) $scoreGoogle[$i] = 0.00;
		$sub_found = 0;
		foreach ($sub_areas as $sa) {
			$new_areas = array_merge(array($sa), $areas);
			$sub_results = $this->search($country, $new_areas);
			if (count($sub_results) == 0) continue;
			$sub_found++;
			foreach ($sub_results as $sr) {
				for ($i = 0; $i < count($results); $i++)
					if ($this->boxContains($results[$i], $sr))
						$score[$i] += 1.0/floatval(count($sub_results));
				for ($i = 0; $i < count($google); $i++)
					if ($this->boxContains($google[$i], $sr))
						$scoreGoogle[$i] += 1.0/floatval(count($sub_results));
			}
		}
		if ($this->debug) echo " - Sub areas found: ".$sub_found."/".count($sub_areas)."\r\n";
		$biggest = 0;
		for ($i = 1; $i < count($results); $i++)
			if ($score[$i] > $score[$biggest]) $biggest = $i;
		if ($sub_found > 0 && $score[$biggest]/$sub_found > 0.75) {
			if ($this->debug) echo " - Found a good match for ".$areas[0]["name"]."!\r\n";
			SQLQuery::create()->updateByKey("GeographicArea", $areas[0]["id"], $results[$biggest]);
			$areas[0]["north"] = $results[$biggest]["north"];
			$areas[0]["south"] = $results[$biggest]["south"];
			$areas[0]["east"] = $results[$biggest]["east"];
			$areas[0]["west"] = $results[$biggest]["west"];
			array_push($this->setted, $areas[0]);
			return array($results[$biggest]);
		}
		if ($this->debug) echo " - Ambiguous, biggest score is ".$score[$biggest]." still ".count($results)." results for ".$areas[0]["name"]."\r\n";
		$biggest = 0;
		for ($i = 1; $i < count($google); $i++)
			if ($scoreGoogle[$i] > $scoreGoogle[$biggest]) $biggest = $i;
		if ($sub_found > 0 && count($google) > 0 && $scoreGoogle[$biggest]/$sub_found > 0.75) {
			if ($this->debug) echo " - Found a good match for ".$areas[0]["name"]." in Google!\r\n";
			unset($google[$biggest]["full_name"]);
			SQLQuery::create()->updateByKey("GeographicArea", $areas[0]["id"], $google[$biggest]);
			$areas[0]["north"] = $google[$biggest]["north"];
			$areas[0]["south"] = $google[$biggest]["south"];
			$areas[0]["east"] = $google[$biggest]["east"];
			$areas[0]["west"] = $google[$biggest]["west"];
			array_push($this->setted, $areas[0]);
			return array($google[$biggest]);
		}
		if (count($google) > 0) {
			if (count($google) == 1 && $sub_found == 0) {
				// only one result from Google, no children, it may be the one
				$full_name = "";
				for ($i = 0; $i < count($areas); $i++) {
					if ($i > 0) $full_name .= ", ";
					$full_name .= $areas[$i]["name"];
				}
				$full_name .= ", ".$country["name"];
				if (strtolower($google[0]["full_name"]) == strtolower($full_name)) {
					// this is the one
					if ($this->debug) echo " - Google single result with matching full name!\r\n";
					unset($google[0]["full_name"]);
					SQLQuery::create()->updateByKey("GeographicArea", $areas[0]["id"], $google[0]);
					$areas[0]["north"] = $google[0]["north"];
					$areas[0]["south"] = $google[0]["south"];
					$areas[0]["east"] = $google[0]["east"];
					$areas[0]["west"] = $google[0]["west"];
					array_push($this->setted, $areas[0]);
					return array($google[0]);
				}
				// try to skip one of the parent which may not appear
				for ($j = 1; $j < count($areas); $j++) {
					$full_name = $areas[0]["name"].", ";
					for ($i = 1; $i < count($areas); $i++) {
						if ($i == $j) continue; //skip
						$full_name .= $areas[$i]["name"].", ";
					}
					$full_name .= $country["name"];
					if (strtolower($google[0]["full_name"]) == strtolower($full_name)) {
						// this is the one
						if ($this->debug) echo " - Google single result with partial full name (removing one of the parent)!\r\n";
						unset($google[0]["full_name"]);
						SQLQuery::create()->updateByKey("GeographicArea", $areas[0]["id"], $google[0]);
						$areas[0]["north"] = $google[0]["north"];
						$areas[0]["south"] = $google[0]["south"];
						$areas[0]["east"] = $google[0]["east"];
						$areas[0]["west"] = $google[0]["west"];
						array_push($this->setted, $areas[0]);
						return array($google[0]);
					}
				}
			}
			if ($this->debug) echo " - Ambiguous, biggest score for google is ".$scoreGoogle[$biggest]."\r\n";
		}
		return $results;
	}
	
	private function searchGeonames($country, $areas) {
		set_time_limit(300);
		$geonames = array();
		$url = "http://api.geonames.org/search?q=".urlencode(strtolower($areas[0]["name"]))."&country=".$country["code"]."&username=pnsdb&style=FULL&type=json";
		if ($this->debug) echo "    === Search ".$areas[0]["name"]." in Geonames: ".$url."\r\n";
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($c);
		curl_close($c);
		if ($result !== false) {
			$result = json_decode($result, true);
			if (isset($result["geonames"]))
				foreach ($result["geonames"] as $res) {
					if (!isset($res["bbox"])) continue;
					// look for admin names
					for ($admin_index = 5; $admin_index >= 1; $admin_index--) {
						if (!isset($res["adminName".$admin_index])) continue;
						if (strtolower($res["adminName".$admin_index]) == strtolower($areas[0]["name"])) break;
						//echo "different: ".strtolower($res["adminName".$admin_index])."/".strtolower($areas[0]["name"])."/\r\n";
					}
					//echo $admin_index."\r\n";
					if ($admin_index > 0) {
						// found in admin !
						$founds = 0;
						$next_index = $admin_index-1;
						$next = 1;
						while ($next_index > 0 && $next < count($areas)) {
							if (strtolower($res["adminName".$next_index]) == strtolower($areas[$next]["name"])) {
								$founds++;
								$next_index--;
								$next++;
							} else
								break;
						}
						if ($next == count($areas)) {
							// all are found!!!
							array_push($geonames, array(
								"north"=>$res["bbox"]["north"],
								"east"=>$res["bbox"]["east"],
								"south"=>$res["bbox"]["south"],
								"west"=>$res["bbox"]["west"]
							));
						}
					} else if (count($areas) > 1) {
						for ($admin_index = 5; $admin_index >= 1; $admin_index--) {
							if (isset($res["adminName".$admin_index]) && $res["adminName".$admin_index] <> "") break;
						}
						//echo $admin_index."\r\n";
						$founds = 0;
						$next_index = $admin_index;
						$next = 1;
						while ($next_index > 0 && $next < count($areas)) {
							if (strtolower($res["adminName".$next_index]) == strtolower($areas[$next]["name"])) {
								$founds++;
								$next_index--;
								$next++;
							} else
								break;
						}
						//echo $next."/".count($areas)."\r\n";
						if ($next == count($areas)) {
							// all are found!!!
							array_push($geonames, array(
								"north"=>$res["bbox"]["north"],
								"east"=>$res["bbox"]["east"],
								"south"=>$res["bbox"]["south"],
								"west"=>$res["bbox"]["west"]
							));
						}
					}
				}
		}
		if ($this->debug) echo "    === Results found: ".count($geonames)."\r\n";
		return $geonames;
	}
	
	private function searchGoogle($country, $areas) {
		set_time_limit(300);
		$query = "";
		for ($i = 0; $i < count($areas); $i++) {
			if ($i > 0) $query .= " ";
			$query .= $areas[$i]["name"];
		}
		$query .= " ".$country["name"];
		$url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=".urlencode($query);
		$url .= "&components=country:".$country["code"];
		$url .= "&types=political";
		$url .= "&sensor=false";
		$url .= "&key=AIzaSyBhG4Hn5zmbXcALGQtAPJDkUj2hDSZdVSU";
		if ($this->debug) echo "   === Search Google: ".$url."\r\n";
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($c);
		curl_close($c);
		$google = array();
		if ($result !== false) {
			$result = json_decode($result, true);
			if (isset($result["status"]) && $result["status"] <> "OK" && $result["status"] <> "ZERO_RESULTS")
				PNApplication::error("Google replied ".$result["status"].(isset($result["error_message"]) ? ": ".$result["error_message"] : ""));
			if (isset($result["results"]))
				foreach ($result["results"] as $res) {
					if (!isset($res["geometry"]["viewport"])) continue;
					array_push($google, array(
						"full_name"=>$res["formatted_address"],
						"north"=>$res["geometry"]["viewport"]["northeast"]["lat"],
						"east"=>$res["geometry"]["viewport"]["northeast"]["lng"],
						"south"=>$res["geometry"]["viewport"]["southwest"]["lat"],
						"west"=>$res["geometry"]["viewport"]["southwest"]["lng"]
					));
				}
		} 
		if ($this->debug) echo "   === Google results: ".count($google)."\r\n";
		return $google;
	}
	
}
?>