<?php
/**
 * Utilities to generate JSON objects from DataBase
 */ 
class GeographyJSON {
	
	/** Prepare an SQL query to generate JSON
	 * @param SQLQuery $q the query to prepare
	 * @no_name_check
	 */
	public static function GeographicAreaTextSQL(&$q) {
		$alias = $q->getTableAlias("GeographicArea");
		$q->field($alias, "id", "geographic_area_text_area_id");
		$div_alias = $q->getTableAlias("CountryDivision");
		if ($div_alias == null) {
			$div_alias = $q->generateTableAlias();
			$q->join($alias, "CountryDivision", array("country_division"=>"id"), $div_alias);
		}
		$q->field($div_alias, "id", "geographic_area_text_country_division_id");
		$q->field($div_alias, "country", "geographic_area_text_country_id");
	}
	
	/** Generate a JSON object GeographicAreaText
	 * @param array $row row from database from a query previously prepared by GeographicAreaTextSQL
	 * @return string the JSON object GeographicAreaText
	 * @no_name_check
	 */
	public static function GeographicAreaText($row, $text = null) {
		if ($row["geographic_area_text_area_id"] == null) return "null";
		return "{".
			"id:".$row["geographic_area_text_area_id"].
			",division_id:".$row["geographic_area_text_country_division_id"].
			",country_id:".$row["geographic_area_text_country_id"].
			",text:".json_encode($text <> null ? $text : PNApplication::$instance->geography->getGeographicAreaText($row["geographic_area_text_area_id"]))
		."}";
	}
	
	public static function prepareGeographicAreasTexts($rows) {
		if (count($rows) == 0) return array();
		$areas_ids = array();
		foreach ($rows as $row) array_push($areas_ids, $row["geographic_area_text_area_id"]);
		return PNApplication::$instance->geography->getGeographicAreasText($areas_ids);
	}
	
}
?>