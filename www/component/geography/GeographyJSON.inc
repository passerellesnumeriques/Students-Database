<?php 
class GeographyJSON {
	
	public static function GeographicAreaTextSQL(&$q) {
		$alias = $q->getTableAlias("GeographicArea");
		$q->field($alias, "id", "geographic_area_id");
	}
	
	public static function GeographicAreaText($q, $row) {
		if ($row["geographic_area_id"] == null) return "null";
		return "{".
			"id:".$row["geographic_area_id"].
			",text:".json_encode(PNApplication::$instance->geography->getGeographicAreaText($row["geographic_area_id"]))
		."}";
	}
	
}
?>