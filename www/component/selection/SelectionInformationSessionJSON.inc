<?php 
class SelectionInformationSessionJSON {
	
	/**
	 * Prepares a SQLQuery to retrieve an information session, to be used with method InformationSession
	 * @param SQLQuery $q
	 * @no_name_check
	 */
	public static function InformationSessionSQL(&$q){
		$alias = $q->getTableAlias("InformationSession");
		$q->field($alias,"id", "is_id")
		  ->field($alias,"date", "is_date")
		  ->field($alias,"geographic_area", "is_geographic_area")
		  ->field($alias,"number_boys_expected", "is_number_boys_expected")
		  ->field($alias,"number_girls_expected", "is_number_girls_expected")
		  ->field($alias,"number_boys_real", "is_number_boys_real")
		  ->field($alias,"number_girls_real", "is_number_girls_real")
		  ->field($alias,"name", "is_name");
	}
	
	public static function InformationSessionJSON($row) {
		$s = "{";
		$s .= "id:".$row["is_id"];
		$s .= ",name:".json_encode($row["is_name"]);
		$s .= ",event_id:".json_encode($row["is_date"]);
		$s .= ",geographic_area_id:".json_encode($row["is_geographic_area"]);
		$s .= ",number_boys_expected:".json_encode($row["is_number_boys_expected"]);
		$s .= ",number_girls_expected:".json_encode($row["is_number_girls_expected"]);
		$s .= ",number_boys_real:".json_encode($row["is_number_boys_real"]);
		$s .= ",number_girls_real:".json_encode($row["is_number_girls_real"]);
		$s .= "}";
		return $s;
	}
	
	public static function InformationSessionsJSON($rows) {
		$s = "[";
		$first = true;
		foreach ($rows as $row) {
			if ($first) $first = false; else $s .= ",";
			$s .= self::InformationSessionJSON($row);
		}
		$s .= "]";
		return $s;
	}
	
	/**
	 * Prepare a JSON information session structure to be sent to the database (used by the IS/save service)<br/>
	 * This method checks that the IS has a name set. Otherwize, the name by default is the geographic_area_text, from the IS geographic_area attribute
	 * @param string $json the IS structure
	 * @return array fields => values to save
	 * @no_name_check
	 */
	public static function InformationSessionTableData2DB($json){
		$a = array(
				"date" => @$json["date"],
				"geographic_area" => @$json["geographic_area"],
				"number_boys_expected" => @$json["number_boys_expected"],
				"number_boys_real" => @$json["number_boys_real"],
				"number_girls_expected" => @$json["number_girls_expected"],
				"number_girls_real" => @$json["number_girls_real"],
		);
		if(isset($json["name"]))
			$a["name"] = $json["name"];
		else if(!isset($json["name"]) && isset($json["geographic_area"])){
			//Make sure that all the informations sessions have at least a name, to be displayed into the datalist
			$a["name"] = PNApplication::$instance->geography->getGeographicAreaText($json["geographic_area"]);
		}
		if(isset($json["id"]))
			$a["id"] = $json["id"];
		return $a;
	}
	
}
?>