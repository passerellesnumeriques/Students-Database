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
		require_once("component/google/GoogleAPI.inc");
		return GoogleAPI::PlacesTextSearch($query, $country["code"]);
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