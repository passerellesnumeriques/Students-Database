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
			->field($alias,"address_type","postal_address__lat")
			->field($alias,"address_type","postal_address__lng")
			;
	}
	
	/**
	 * Generate a JSON PostalAddress object 
	 * @param array $row the row corresponding to the postal address
	 * @return string the PostalAddress object in JSON
	 * @no_name_check
	 */
	public static function PostalAddress($row, $geo_text = null) {
		require_once("component/geography/GeographyJSON.inc");
		return
			"{".
				"id:".$row["postal_address__id"].
				",country_id:".json_encode(@$row["postal_address__country_id"]).
				",geographic_area:".GeographyJSON::GeographicAreaText($row, $geo_text).
				",street:".json_encode(@$row["postal_address__street"]).
				",street_number:".json_encode(@$row["postal_address__street_number"]).
				",building:".json_encode(@$row["postal_address__building"]).
				",unit:".json_encode(@$row["postal_address__unit"]).
				",additional:".json_encode(@$row["postal_address__additional"]).
				",address_type:".json_encode(@$row["postal_address__address_type"]).
				",lat:".json_encode(@$row["postal_address__lat"]).
				",lng:".json_encode(@$row["postal_address__lng"]).
			"}"
			;
	}
	
	public static function PostalAddresses($rows, $texts = null) {
		if (count($rows) == 0) return "[]";
		if ($texts == null)
			$texts = GeographyJSON::prepareGeographicAreasTexts($rows);
		$s = "[";
		for ($i = 0; $i < count($rows); $i++) {
			if ($i > 0) $s .= ",";
			$s .= self::PostalAddress($rows[$i], $texts[$i]);
		}
		$s .= "]";
		return $s;
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
			"address_type"=>@$json["address_type"],
			"lat"=>@$json["lat"],
			"lng"=>@$json["lng"]
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

	public static function Contacts($rows) {
		$s = "[";
		$first = true;
		foreach ($rows as $row) {
			if ($first) $first = false; else $s .= ",";
			$s .= self::Contact($row);
		}
		$s .= "]";
		return $s;
	}
	
	public static function OrganizationFromID($id) {
		$q = SQLQuery::create()->select("Organization")->whereValue("Organization","id",$id);
		self::OrganizationSQL($q);
		$org = $q->executeSingleRow();
		$types_ids = SQLQuery::create()->select("OrganizationTypes")->whereValue("OrganizationTypes","organization",$id)->field("OrganizationTypes","type")->executeSingleField();
		$q = SQLQuery::create()->select("OrganizationContact")->whereValue("OrganizationContact","organization",$id)->join("OrganizationContact","Contact",array("contact"=>"id"));
		self::ContactSQL($q);
		$contacts = $q->execute();
		$q = SQLQuery::create()->select("OrganizationAddress")->whereValue("OrganizationAddress","organization",$id)->join("OrganizationAddress","PostalAddress",array("address"=>"id"));
		self::PostalAddressSQL($q);
		$addresses = $q->execute();
		$q = SQLQuery::create()->select("ContactPoint")->whereValue("ContactPoint","organization",$id);
		self::OrganizationContactPointSQL($q);
		$contact_points = $q->execute();
		return self::OrganizationJSON($org, $types_ids, $contacts, $addresses, $contact_points);
	}
	
	public static function OrganizationsFromIDs($ids) {
		$q = SQLQuery::create()->select("Organization")->whereIn("Organization","id",$ids);
		self::OrganizationSQL($q);
		$orgs = $q->execute();
		$list_types_ids = SQLQuery::create()->select("OrganizationTypes")->whereIn("OrganizationTypes","organization",$ids)->execute();
		$q = SQLQuery::create()->select("OrganizationContact")->whereIn("OrganizationContact","organization",$ids)->join("OrganizationContact","Contact",array("contact"=>"id"));
		$q->field("OrganizationContact", "organization", "organization_id");
		self::ContactSQL($q);
		$list_contacts = $q->execute();
		$q = SQLQuery::create()->select("OrganizationAddress")->whereIn("OrganizationAddress","organization",$ids)->join("OrganizationAddress","PostalAddress",array("address"=>"id"));
		$q->field("OrganizationAddress", "organization", "organization_id");
		self::PostalAddressSQL($q);
		$list_addresses = $q->execute();
		$q = SQLQuery::create()->select("ContactPoint")->whereIn("ContactPoint","organization",$ids);
		$q->field("ContactPoint", "organization", "organization_id");
		self::OrganizationContactPointSQL($q);
		$list_contact_points = $q->execute();
		$s = "[";
		$first = true;
		foreach ($orgs as $org) {
			if ($first) $first = false; else $s .= ",";
			$types_ids = array();
			foreach ($list_types_ids as $t)
				if ($t["organization"] == $org["organization_id"])
					array_push($types_ids, $t["type"]);
			$contacts = array();
			foreach ($list_contacts as $e) if ($e["organization_id"] == $org["organization_id"]) array_push($contacts, $e);
			$addresses = array();
			foreach ($list_addresses as $e) if ($e["organization_id"] == $org["organization_id"]) array_push($addresses, $e);
			$contact_points = array();
			foreach ($list_contact_points as $e) if ($e["organization_id"] == $org["organization_id"]) array_push($contact_points, $e);
			$s .= self::OrganizationJSON($org, $types_ids, $contacts, $addresses, $contact_points);
		}
		$s .= "]";
		return $s;
	}
	
	public static function OrganizationSQL(&$q) {
		$alias = $q->getTableAlias("Organization");
		$q->field($alias, "id", "organization_id");
		$q->field($alias, "name", "organization_name");
		$q->field($alias, "creator", "organization_creator");
	}
	
	public static function OrganizationJSON($org, $types_ids, $contacts, $addresses, $contact_points) {
		$s = "{";
		$s .= "id:".$org["organization_id"];
		$s .= ",name:".json_encode($org["organization_name"]);
		$s .= ",creator:".json_encode($org["organization_creator"]);
		$s .= ",types_ids:";
		if ($types_ids !== null) {
			$s .= "[";
			$first = true;
			foreach ($types_ids as $tid) {
				if ($first) $first = false; else $s .= ",";
				$s .= $tid;
			}
			$s .= "]";
		} else
			$s .= "null";
		$s .= ",contacts:";
		if ($contacts !== null) {
			$s .= self::Contacts($contacts);
		} else 
			$s .= "null";
		$s .= ",addresses:";
		if ($addresses !== null) {
			$s .= self::PostalAddresses($addresses);
		} else 
			$s .= "null";
		$s .= ",contact_points:";
		if ($contact_points !== null) {
			$s .= self::OrganizationContactPoints($contact_points);
		} else 
			$s .= "null";
		$s .= "}";
		return $s;
	}
	
	public static function OrganizationContactsPointsFromDB($organization_id) {
		$q = SQLQuery::create()
			->select("ContactPoint")
			->whereValue("ContactPoint", "organization", $organization_id)
			;
		self::OrganizationContactPointSQL($q);
		return self::OrganizationContactsPoints($q->execute());
	}
	
	public static function OrganizationContactPointSQL(&$q) {
		$alias = $q->getTableAlias("ContactPoint");
		$q->field($alias, "organization", "contact_point_organization_id");
		$q->field($alias, "designation", "contact_point_designation");
		require_once("component/people/PeopleJSON.inc");
		PNApplication::$instance->people->joinPeople($q, "ContactPoint", "people");
		PeopleJSON::PeopleSQL($q);
	}
	
	public static function OrganizationContactPoint($row) {
		require_once("component/people/PeopleJSON.inc");
		return "{".
			"organization_id:".$row["contact_point_organization_id"].
			",people:".PeopleJSON::People($row).
			",designation:".json_encode($row["contact_point_designation"]).
			"}";
	}
	
	public static function OrganizationContactPoints($rows) {
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