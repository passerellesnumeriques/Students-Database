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
	
}
?>