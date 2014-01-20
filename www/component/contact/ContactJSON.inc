<?php 
class ContactJSON {

	/**
	 * Prepares a SQL query to retrieve a PostalAddress, to be used with the method PostalAddress
	 * @param SQLQuery $q the query to update
	 */
	public static function PostalAddressSQL(&$q) {
		$area_alias = $q->get_table_alias("Geographic_area");
		if ($area_alias == null) {
			$area_alias = $q->table_id();
			$q->join("Postal_address", "Geographic_area", array("geographic_area"=>"id"), $area_alias);
		}
		require_once("component/geography/GeographyJSON.inc");
		GeographyJSON::GeographicAreaTextSQL($q);
		$alias = $q->get_table_alias("Postal_address");
		$q
			->field($alias,"id","address_id")
			->field($alias,"country","country_id")
			->field($alias,"street","street")
			->field($alias,"street_number","street_number")
			->field($alias,"building","building")
			->field($alias,"unit","unit")
			->field($alias,"additional","additional")
			->field($alias,"address_type","address_type")
			;
	}
	
	/**
	 * Generate a JavaScript PostalAddress object 
	 * @param SQLQuery $q the query used
	 * @param array $row the row corresponding to the postal address
	 * @return string the PostalAddress object instantiation
	 */
	public static function PostalAddress($q, $row) {
		require_once("component/geography/GeographyJSON.inc");
		return
			"new PostalAddress(".
				$row["address_id"].
				",".$row["country_id"].
				",".GeographyJSON.GeographicAreaText($q, $row).
				",".$row["street"].
				",".$row["street_number"].
				",".$row["building"].
				",".$row["unit"].
				",".$row["additional"].
				",".$row["address_type"].
			")"
			;
	}
	
	public static function PostalAddress2DB($json) {
		return array(
			"id"=>$json["id"],
			"country"=>@$json["country_id"],
			"geographic_area"=>(isset($json["geographic_area"]) && isset($json["geographic_area"]["id"]) ? $json["geographic_area"]["id"] : null),
			"street"=>@$json["street"],
			"street_number"=>@$json["street_number"],
			"building"=>@$json["building"],
			"unit"=>@$json["unit"],
			"additional"=>@$json["additional"],
			"address_type"=>@$json["address_type"] 
		);
	}
	
}
?>