<?php
/**
 * Provides functionalities to convert structures between JSON and database
 */ 
class ContactJSON {

	/**
	 * Build a PostalAddress JSON structure, for the given postal address ID
	 * @param number $id the postal address id from database
	 * @return NULL|string the JSON structure, or null if the postal address does not exist
	 * @no_name_check
	 */
	public static function PostalAddressFromID($id) {
		$q = SQLQuery::create()->select("PostalAddress")->whereValue("PostalAddress", "id", $id);
		self::PostalAddressSQL($q);
		$row = $q->executeSingleRow();
		if ($row == null) return null;
		return self::PostalAddress($row);
	}
	
	/**
	 * Prepares a SQL query to retrieve a PostalAddress, to be used with the method PostalAddress
	 * @param SQLQuery $q the query to update
	 * @no_name_check
	 */
	public static function PostalAddressSQL(&$q) {
		$alias = $q->getTableAlias("PostalAddress");
		PNApplication::$instance->geography->joinGeographicArea($q, $alias, "geographic_area", false);
		require_once("component/geography/GeographyJSON.inc");
		GeographyJSON::GeographicAreaTextSQL($q);
		$q
			->field($alias,"id","postal_address__id")
			->field($alias,"country","postal_address__country_id")
			->field($alias,"street","postal_address__street")
			->field($alias,"street_number","postal_address__street_number")
			->field($alias,"building","postal_address__building")
			->field($alias,"unit","postal_address__unit")
			->field($alias,"additional","postal_address__additional")
			->field($alias,"address_type","postal_address__address_type")
			;
	}
	
	/**
	 * Generate a JSON PostalAddress object 
	 * @param array $row the row corresponding to the postal address
	 * @return string the PostalAddress object in JSON
	 * @no_name_check
	 */
	public static function PostalAddress($row) {
		require_once("component/geography/GeographyJSON.inc");
		return
			"{".
				"id:".$row["postal_address__id"].
				",country_id:".json_encode(@$row["postal_address__country_id"]).
				",geographic_area:".GeographyJSON::GeographicAreaText($row).
				",street:".json_encode(@$row["postal_address__street"]).
				",street_number:".json_encode(@$row["postal_address__street_number"]).
				",building:".json_encode(@$row["postal_address__building"]).
				",unit:".json_encode(@$row["postal_address__unit"]).
				",additional:".json_encode(@$row["postal_address__additional"]).
				",address_type:".json_encode(@$row["postal_address__address_type"]).
			"}"
			;
	}

	/**
	 * Convert an array coming from a JSON structure, into an array ready to use with database (insert or update)
	 * @param array $json the JSON structure decoded into an associative array
	 * @return array the associative array with column name from the database
	 * @no_name_check
	 */
	public static function PostalAddress2DB($json) {
		$a = array(
			"country"=>@$json["country_id"],
			"geographic_area"=>(isset($json["geographic_area"]) && isset($json["geographic_area"]["id"]) ? $json["geographic_area"]["id"] : null),
			"street"=>@$json["street"],
			"street_number"=>@$json["street_number"],
			"building"=>@$json["building"],
			"unit"=>@$json["unit"],
			"additional"=>@$json["additional"],
			"address_type"=>@$json["address_type"] 
		);
		if (isset($json["id"]))
			$a["id"] = $json["id"];
		return $a;
	}
	
	/**
	 * Prepares a SQL query to retrieve a Contact, to be used with the method Contact
	 * @param SQLQuery $q the query to update
	 * @no_name_check
	 */
	public static function ContactSQL(&$q) {
		$alias = $q->getTableAlias("Contact");
		$q
			->field($alias,"id","contact_id")
			->field($alias,"type","contact_type")
			->field($alias,"sub_type","contact_sub_type")
			->field($alias,"contact","contact_text")
			;
	}
	
	/**
	 * Generate a JSON Contact object 
	 * @param SQLQuery $q the query used
	 * @param array $row the row corresponding to the contact
	 * @return string the Contact object in JSON
	 * @no_name_check
	 */
	public static function Contact($row) {
		return
			"{".
				"id:".$row["contact_id"].
				",type:".json_encode($row["contact_type"]).
				",sub_type:".json_encode(@$row["contact_sub_type"]).
				",contact:".json_encode(@$row["contact_text"]).
			"}"
			;
	}
	
	public static function OrganizationContactsPointsFromDB($organization_id) {
		$q = SQLQuery::create()
			->select("ContactPoint")
			->whereValue("ContactPoint", "organization", $organization_id)
			->field("ContactPoint", "designation", "contact_point_designation")
			;
		require_once("component/people/PeopleJSON.inc");
		PNApplication::$instance->people->joinPeople($q, "ContactPoint", "people");
		PeopleJSON::PeopleSQL($q);
		return self::OrganizationContactsPoints($q->execute());
	}
	
	public static function OrganizationContactPoint($row) {
		require_once("component/people/PeopleJSON.inc");
		return "{".
			"people:".PeopleJSON::People($row).
			",designation:".json_encode($row["contact_point_designation"]).
			"}";
	}
	
	public static function OrganizationContactsPoints($rows) {
		$s = "[";
		$first = true;
		foreach ($rows as $row) {
			if ($first) $first = false; else $s .= ",";
			$s .= self::OrganizationContactPoint($row);
		}
		$s .= "]";
		return $s;
	}
	
	// Methods about partners
	
	/**
	 * Prepare a JSON structure of entity partners array to be saved into the database (for instance, information session partners)
	 * @param array $json the JSON structure
	 * @param string $field_name the name of the foreignkey linked to the entity (for instance "information_session")
	 * @return array containing two fields => values arrays:<ul><li>[0]: fields => values for Partner table</li><li>[1]: fields => values for ContactPoint table</li></ul>
	 */
	public static function PartnersAndContactPoints2DB($json, $field_name){
		$rows_partner = array();
		$rows_contact_point = array();
		foreach($json["partners"] as $p){
			array_push($rows_partner,array(
			$field_name => $json["id"],
			"organization" => $p["organization"],
			"host" => $p["host"],
			"host_address" => $p["host_address"]
			));
			if(isset($p["contact_points_selected"]) && count($p["contact_points_selected"]) > 0){
				foreach($p["contact_points_selected"] as $people){
					array_push($rows_contact_point,array(
					$field_name => $json["id"],
					"organization" => $p["organization"],
					"people" => $people
					));
				}
			}
		}
		return array($rows_partner, $rows_contact_point);
	}
	
	
}
?>