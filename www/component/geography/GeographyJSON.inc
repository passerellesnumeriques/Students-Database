<?php 
class GeographyJSON {
	
	public static function GeographicAreaTextSQL(&$q) {
		$alias = $q->getTableAlias("GeographicArea");
		$q->field($alias, "id", "geographic_area_id");
		$div_alias = $q->getTableAlias("CountryDivision");
		if ($div_alias == null) {
			$div_alias = $q->generateTableAlias();
			$q->join($alias, "CountryDivision", array("country_division"=>"id"), $div_alias);
		}
		$q->field($div_alias, "id", "country_division_id");
		$q->field($div_alias, "country", "country_id");
	}
	
	public static function GeographicAreaText($q, $row) {
		if ($row["geographic_area_id"] == null) return "null";
		if(!isset($row["country_division_id"])){
			$row["country_division_id"] = SQLQuery::create()
				->select("GeographicArea")
				->field("GeographicArea","country_division",'id')
				->whereValue("GeographicArea", "id", $row["geographic_area_id"])
				->executeSingleValue();
		}
		return "{".
			"id:".$row["geographic_area_id"].
			",division_id:".$row["country_division_id"].
			",country_id:".$row["country_id"].
			",text:".json_encode(PNApplication::$instance->geography->getGeographicAreaText($row["geographic_area_id"]))
		."}";
	}
	
}
?>