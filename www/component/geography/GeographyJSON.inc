<?php 
class GeographyJSON {
	
	public static function GeographicAreaTextSQL(&$q) {
		$alias = $q->get_table_alias("Geographic_area");
		$q->field($alias, "id", "geographic_area_id");
	}
	
	public static function GeographicAreaText($q, $row) {
		return "new GeographicAreaText(".
			$row["geographic_area_id"].
			",".json_encode(PNApplication::$instance->geography->get_geographic_area_text($row["geographic_area_id"]))
		.")";
	}
	
}
?>