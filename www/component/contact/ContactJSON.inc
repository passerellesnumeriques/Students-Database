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
			->field($alias,"lat","postal_address__lat")
			->field($alias,"lng","postal_address__lng")
			;
	}
	
	/**
	 * Generate a JSON PostalAddress object 
	 * @param array $row the row corresponding to the postal address
	 * @param array $geo_text pre-fetched information from geography to create the text describing the area, or null to make the database request
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
	
	/**
	 * Generate a JSON array containing PostalAddress objects
	 * @param array $rows the postal addresses
	 * @param array $texts if information are already pre-fetched for geographic area information, or null to make the request
	 * @return string the JSON array
	 * @no_name_check
	 */
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

	/** Generate a JSON array for the given contacts
	 * @param array $rows the contacts
	 * @return string the JSON array
	 * @no_name_check
	 */
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
	
	/** retrieve information from database and generate the JSON structure of the organization
	 * @param integer $id the organization id
	 * @return string the JSON
	 * @no_name_check
	 */
	public static function OrganizationFromID($id) {
		$q = SQLQuery::create()->select("Organization")->whereValue("Organization","id",$id);
		self::OrganizationSQL($q);
		$org = $q->executeSingleRow();
		$types_ids = SQLQuery::create()->select("OrganizationTypes")->whereValue("OrganizationTypes","organization",$id)->field("OrganizationTypes","type")->executeSingleField();
		$q = SQLQuery::create()->select("OrganizationContact")->whereValue("OrganizationContact","organization",$id)->join("OrganizationContact","Contact",array("contact"=>"id"));
		self::ContactSQL($q);
		$q->field("OrganizationContact", "attached_location", "attached_location");
		$contacts = $q->execute();
		$q = SQLQuery::create()->select("OrganizationAddress")->whereValue("OrganizationAddress","organization",$id)->join("OrganizationAddress","PostalAddress",array("address"=>"id"));
		self::PostalAddressSQL($q);
		$q->field("OrganizationAddress", "name", "location_name");
		$addresses = $q->execute();
		$q = SQLQuery::create()->select("ContactPoint")->whereValue("ContactPoint","organization",$id);
		self::OrganizationContactPointSQL($q);
		$contact_points = $q->execute();
		if (count($contact_points) > 0) {
			$peoples_ids = array();
			foreach ($contact_points as $cp) if (!in_array($cp["people_id"], $peoples_ids)) array_push($peoples_ids, $cp["people_id"]);
			$q = SQLQuery::create()->select("PeopleContact")->whereIn("PeopleContact","people",$peoples_ids)->join("PeopleContact", "Contact", array("contact"=>"id"));
			$q->field("PeopleContact","people","people_id");
			self::ContactSQL($q);
			$list = $q->execute();
			for ($i = 0; $i < count($contact_points); $i++) {
				$people_id = $contact_points[$i]["people_id"];
				$contact_points[$i]["contacts"] = array();
				foreach ($list as $c)
					if ($c["people_id"] == $people_id)
						array_push($contact_points[$i]["contacts"], $c);
			}
		} 
		return self::OrganizationJSON($org, $types_ids, $contacts, $addresses, $contact_points);
	}
	
	/** List of organizations
	 * @param integer[] $ids organizations ids
	 * @return string the JSON array
	 * @no_name_check
	 */
	public static function OrganizationsFromIDs($ids) {
		$q = SQLQuery::create()->select("Organization")->whereIn("Organization","id",$ids);
		self::OrganizationSQL($q);
		$orgs = $q->execute();
		$list_types_ids = SQLQuery::create()->select("OrganizationTypes")->whereIn("OrganizationTypes","organization",$ids)->execute();
		$q = SQLQuery::create()->select("OrganizationContact")->whereIn("OrganizationContact","organization",$ids)->join("OrganizationContact","Contact",array("contact"=>"id"));
		$q->field("OrganizationContact", "organization", "organization_id");
		$q->field("OrganizationContact", "attached_location", "attached_location");
		self::ContactSQL($q);
		$list_contacts = $q->execute();
		$q = SQLQuery::create()->select("OrganizationAddress")->whereIn("OrganizationAddress","organization",$ids)->join("OrganizationAddress","PostalAddress",array("address"=>"id"));
		$q->field("OrganizationAddress", "organization", "organization_id");
		$q->field("OrganizationAddress", "name", "location_name");
		self::PostalAddressSQL($q);
		$list_addresses = $q->execute();
		$q = SQLQuery::create()->select("ContactPoint")->whereIn("ContactPoint","organization",$ids);
		$q->field("ContactPoint", "organization", "organization_id");
		self::OrganizationContactPointSQL($q);
		$list_contact_points = $q->execute();
		if (count($list_contact_points) > 0) {
			$peoples_ids = array();
			foreach ($list_contact_points as $cp) if (!in_array($cp["people_id"], $peoples_ids)) array_push($peoples_ids, $cp["people_id"]);
			$q = SQLQuery::create()->select("PeopleContact")->whereIn("PeopleContact","people",$peoples_ids)->join("PeopleContact", "Contact", array("contact"=>"id"));
			$q->field("PeopleContact","people","people_id");
			self::ContactSQL($q);
			$list = $q->execute();
			for ($i = 0; $i < count($list_contact_points); $i++) {
				$people_id = $list_contact_points[$i]["people_id"];
				$list_contact_points[$i]["contacts"] = array();
				foreach ($list as $c)
					if ($c["people_id"] == $people_id)
						array_push($list_contact_points[$i]["contacts"], $c);
			}
		} 
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
	
	/** Prepare the SQLQuery to retrieve fields about the organization
	 * @param SQLQuery $q the query
	 * @param string $alias if specified, this is the Organization table alias, and every field will be prefixed with this alias and an underscore
	 * @no_name_check
	 */
	public static function OrganizationSQL(&$q, $alias = null) {
		if ($alias == null) {
			$alias = $q->getTableAlias("Organization");
			$q->field($alias, "id", "organization_id");
			$q->field($alias, "name", "organization_name");
			$q->field($alias, "creator", "organization_creator");
		} else {
			$q->field($alias, "id", $alias."_id");
			$q->field($alias, "name", $alias."_name");
			$q->field($alias, "creator", $alias."_creator");
		}
	}
	
	/** Generate a full JSON structure for an organization
	 * @param array $org the organization
	 * @param integer[] $types_ids types of the organization
	 * @param array $contacts the contacts
	 * @param array $addresses the postal addresses
	 * @param array $contact_points the contact points
	 * @return string the JSON
	 * @no_name_check
	 */
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
		$general_contacts = array();
		$general_contact_points = array();
		$locations = array();
		if ($addresses !== null)
			foreach ($addresses as $addr) $locations[$addr["postal_address__id"]] = array("name"=>$addr["location_name"],"address"=>$addr,"contacts"=>array(),"contact_points"=>array());
		$s .= ",general_contacts:";
		if ($contacts !== null) {
			foreach ($contacts as $c) if ($c["attached_location"] == null) array_push($general_contacts, $c); else array_push($locations[$c["attached_location"]]["contacts"], $c); 
			$s .= self::Contacts($general_contacts);
		} else 
			$s .= "null";
		$s .= ",general_contact_points:";
		if ($contact_points !== null) {
			foreach ($contact_points as $c) if ($c["contact_point_attached_location"] == null) array_push($general_contact_points, $c); else array_push($locations[$c["attached_location"]]["contact_points"], $c); 
			$s .= self::OrganizationContactPoints($general_contact_points);
		} else 
			$s .= "null";
		$s .= ",locations:[";
		$first = true;
		foreach ($locations as $addr_id=>$loc) {
			if ($first) $first = false; else $s .= ",";
			$s .= "{";
			$s .= "name:".json_encode($loc["name"]);
			$s .= ",address:".self::PostalAddress($loc["address"]);
			$s .= ",contacts:".self::Contacts($loc["contacts"]);
			$s .= ",contact_points:".self::OrganizationContactPoints($loc["contact_points"]);
			$s .= "}";
		}
		$s .= "]";
		$s .= "}";
		return $s;
	}
	
	/**
	 * Generate the JSON structure containing the contact points of the given organization
	 * @param integer $organization_id organization
	 * @return string the JSON
	 * @no_name_check
	 */
	public static function OrganizationContactsPointsFromDB($organization_id) {
		$q = SQLQuery::create()
			->select("ContactPoint")
			->whereValue("ContactPoint", "organization", $organization_id)
			;
		self::OrganizationContactPointSQL($q);
		return self::OrganizationContactPoints($q->execute());
	}
	
	/**
	 * Prepare the query to select fields: contact_point_organization_id, contact_point_designation, and fields from the people
	 * @param SQLQuery $q the query
	 * @no_name_check
	 */
	public static function OrganizationContactPointSQL(&$q) {
		$alias = $q->getTableAlias("ContactPoint");
		$q->field($alias, "organization", "contact_point_organization_id");
		$q->field($alias, "designation", "contact_point_designation");
		$q->field($alias, "attached_location", "contact_point_attached_location");
		require_once("component/people/PeopleJSON.inc");
		PNApplication::$instance->people->joinPeople($q, "ContactPoint", "people");
		PeopleJSON::PeopleSQL($q);
	}
	
	/** Generate the JSON for the given contact point
	 * @param array $row the contact point
	 * @return string the JSON
	 * @no_name_check
	 */
	public static function OrganizationContactPoint($row) {
		require_once("component/people/PeopleJSON.inc");
		return "{".
			"organization_id:".$row["contact_point_organization_id"].
			",people:".PeopleJSON::People($row).
			",designation:".json_encode($row["contact_point_designation"]).
			",attached_location:".json_encode($row["contact_point_attached_location"]).
			(isset($row["contacts"]) ? ",contacts:".self::Contacts($row["contacts"]) : "").
			"}";
	}
	
	/** Generate the JSON for the given contact points
	 * @param array $rows the contact points
	 * @return string the JSON
	 * @no_name_check
	 */
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
	 * @no_name_check
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