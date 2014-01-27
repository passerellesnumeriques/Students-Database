<?php
/**
 * Manage contacts (email, phone, IM, postal address) for people and organizations
 */
class contact extends Component {
	
	public function getPluginImplementations() {
		require_once("ContactPeoplePlugin.inc");
		return array(
			new ContactPeoplePlugin()
		);
	}
	/**
	 * Add a new postal address to an organization
	 * @param integer $organization_id Organization ID
	 * @param array $new_address PostalAddress coming from JSON
	 * @param boolean $fake indicates if this address is attached to a fake organization
	 * @return integer|boolean the address ID or false if it failed
	 */
	public function addAddressToOrganization($organization_id,$new_address,$fake = false) {
		//Check the rights
		require_once("component/data_model/Model.inc");
		$table = DataModel::get()->getTable("Organization_address");
		if (!$fake && !$table->acceptInsert(array("organization"=>$organization_id))) {
			PNApplication::error("You are not allowed to add an address for this organization");
			return false;
		} else {
			$address_id = $this->addAddress("Organization_address","organization",$organization_id,$new_address,$fake);
			return $address_id;
		}
	}
	/**
	 * Add a new postal address to a people
	 * @param integer $people_id People ID
	 * @param array $new_address PostalAddress coming from JSON
	 * @param boolean $fake indicates if this address is attached to a fake organization
	 * @return integer|boolean the address ID or false if it failed
	 */
	public function addAddressToPeople($people_id,$new_address,$fake = false) {
		//Check the rights
		require_once("component/data_model/Model.inc");
		$table = DataModel::get()->getTable("People_address");
		if (!$fake && !$table->acceptInsert(array("people"=>$people_id))) {
			PNApplication::error("You are not allowed to add an address for this people");
			return false;
		} else {
			$address_id = $this->addAddress("People_address","people",$people_id,$new_address,$fake);
			return $address_id;
		}
	}
	/**
	 * Internal method to add a PostalAddress in database
	 * @param string $table the table where to insert the join (People_address or Organization_address)
	 * @param string $column column of the join (people or organization)
	 * @param number $key key of the join (people id or organization id)
	 * @param array $new_address address to insert
	 * @param boolean $bypass indicates if we need to bypass the security accessing the database
	 * @return boolean|number id of the postal address on success, or false
	 */
	private function addAddress($table, $column, $key, $new_address, $bypass) {
		// convert JSON to database
		require_once("ContactJSON.inc");
		$new_address = ContactJSON::PostalAddress2DB($new_address);
		// remove any id
		unset($new_address["id"]);
		// insert
		try {
			$address_id = SQLQuery::create()->bypass_security()->insert("Postal_address", $new_address);
		} catch (Exception $ex) {
			$address_id = 0;
			PNApplication::error($ex);
		}
		if ($address_id == 0) {
			return false;
		}
		try {
			if($bypass)
				SQLQuery::create()->bypass_security()->insert($table, array($column=>$key,"address"=>$address_id));
			else
				SQLQuery::create()->insert($table, array($column=>$key,"address"=>$address_id));
		} catch (Exception $ex) {
			PNApplication::error($ex);
			SQLQuery::create()->remove_key("Postal_address", $address_id);
			return false;
		}
		return $address_id;
	}
	
	/**
	 * Add a contact to a people
	 * @param number $people_id the people id
	 * @param array $contact Contact structure
	 * @return boolean|number the new contact id, or false if it failed
	 */
	public function addContactToPeople($people_id, $contact) {
		return $this->addContact("People_contact", "people", $people_id, $contact);
	}
	/**
	 * Add a contact to an organization
	 * @param number $org_id the organization id
	 * @param array $contact Contact structure
	 * @return boolean|number the new contact id, or false if it failed
	 */
	public function addContactToOrganization($org_id, $contact) {
		return $this->addContact("Organization_contact", "organization", $org_id, $contact);
	}
	/**
	 * Internal method to add a contact to a people or an organization
	 * @param string $table_name table joining the people or organization to the contact
	 * @param string $join_column column doing the join
	 * @param number $join_value id of the join (people id or organization id)
	 * @param array $contact the contact structure to add
	 * @return boolean|number the new contact id, or false if it failed
	 */
	private function addContact($table_name, $join_column, $join_value, $contact) {
		$table = DataModel::get()->getTable($table_name);
		if (!$table->acceptInsert(array($join_column=>$join_value))) {
			PNApplication::error("You are not allowed to add a contact for this ".$join_column);
			return false;
		}
		try {
			$contact_id = SQLQuery::create()->bypass_security()->insert("Contact", array("type"=>$contact["type"],"contact"=>$contact["contact"],"sub_type"=>$contact["sub_type"]));
		} catch (Exception $ex) {
			$contact_id = 0;
			PNApplication::error($ex);
		}
		if ($contact_id == 0)
			return false;
		try {
			SQLQuery::create()->insert($table_name, array($join_column=>$join_value,"contact"=>$contact_id));
		} catch (Exception $ex) {
			PNApplication::error($ex);
			SQLQuery::create()->remove_key("Contact", $contact_id);
			return false;
		}

		return $contact_id;
	}
	
	/**
	 * Create a new organization type
	 * @param string $creator indicates to which part of the application it belongs to
	 * @param string $name the organization type's name
	 * @return boolean|number the new type id, or false if it failed
	 */
	public function createOrganizationType($creator, $name) {
		require_once("component/contact/OrganizationPlugin.inc");
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->getOrganizationCreator() == $creator) {
					if (!($pi->canInsertOrganization())) {
						PNApplication::error("You are not allowed to create orgnization in ".$input["creator"]);
						return false;
					}
					break;
				}
			}
		}
		
		try {
			return SQLQuery::create()->insert("Organization_type", array("creator"=>$input["creator"], "name"=>$input["name"]));
		} catch (Exception $e) {
			PNApplication::error($e);
			return false;
		}
	}
	
	/**
	 * Assign a type to an organization
	 * @param number $organization_id organization id
	 * @param number $type_id type id
	 * @return boolean true if it succeed
	 */
	public function assignOrganizationType($organization_id, $type_id) {
		try {
			SQLQuery::create()->insert("Organization_types", array("organization"=>$organization_id, "type"=>$type_id));
			return true;
		}catch (Exception $e) {
			PNApplication::error($e);
			return false;
		}
	}
	/**
	 * Remove a type from an organization
	 * @param number $organization_id organization id
	 * @param number $type_id type id
	 * @return boolean true if it succeed
	 */
	public function unassignOrganizationType($organization_id, $type_id) {
		try {
			SQLQuery::create()->remove_key("Organization_types", array("organization"=>$organization_id, "type"=>$type_id));
			return true;
		}catch (Exception $e) {
			PNApplication::error($e);
			return false;
		}
	}
	
	
	public function get_json_address($id){
		$json_address = "";
		$q = SQLQuery::create()->select("Postal_address")
						->field('id')
						->field("Postal_address","country","country_id")
						->field("Postal_address","geographic_area","area_id")
						->field("street")
						->field("street_number")
						->field("building")
						->field("unit")
						->field("additional")
						->field("address_type")
						->where("id ='".$id."'");
						
		$adrs = $q->execute();
		
		if(!isset($adrs[0]['id'])) $json_address.= "{}";
		else{
			$json_address.= "{";
			$json_address.= "id: ".json_encode($adrs[0]["id"]).", ";
			$json_address.= "country_id: ".json_encode($adrs[0]["country_id"]).", ";
			$json_address.= "area_id: ".json_encode($adrs[0]["area_id"]).", ";
			$json_address.= "street: ".json_encode($adrs[0]["street"]).", ";
			$json_address.= "street_number: ".json_encode($adrs[0]["street_number"]).", ";
			$json_address.= "building: ".json_encode($adrs[0]["building"]).", ";
			$json_address.= "unit: ".json_encode($adrs[0]["unit"]).", ";
			$json_address.= "additional: ".json_encode($adrs[0]["additional"]).", ";
			$json_address.= "address_type: ".json_encode($adrs[0]["address_type"]).", ";
			$json_address.= "area_text:";
			if(isset($adrs[0]['area_id']) && $adrs[0]['area_id'] <> null){
				$q = SQLQuery::create()->select("Geographic_area")
							->field("parent")
							->field("name")
							->where("id = '".$adrs[0]['area_id']."'");
				$areas = $q->execute();
				$q_country = SQLQuery::create()->select("Country")
							->field("name")
							->field("code")
							->join("Country","Country_division",array("id"=>"country"))
							->join("Country_division","Geographic_area",array("id"=>"country_division"))
							->where("Geographic_area.id = '".$adrs[0]['area_id']."'");
				$country = $q_country->execute();
				if(isset($areas[0]["name"])){
					$json_address.= "[";
					$json_address.= json_encode($areas[0]["name"]);
					$parent = $areas[0]['parent'];
					while($parent <> null){
						$q = SQLQuery::create()->select("Geographic_area")
										->field("parent")
										->field("name")
										->where("id = '".$parent."'");
						$ar = $q->execute();
						$json_address.= ", '".$ar[0]['name']."'";
						$parent = $ar[0]["parent"];
					}
					$json_address.= "]";
				}	
			} else $json_address.= "[]";
			if(isset($country[0]["name"])) $json_address.= ", country_name:".json_encode($country[0]["name"]);
			if(isset($country[0]["code"])) $json_address.= ", country_code:".json_encode($country[0]["code"]);
			$json_address.= "}";
		}
		return $json_address;
	}
	//TODO: check where get_json_address is used and adapt it with the new version below!
	
	public function get_json_address_good_format_for_address_text($id){
		$json_address = "";
		$q = SQLQuery::create()->select("Postal_address")
						->field('id')
						->field("Postal_address","country","country_id")
						->field("Postal_address","geographic_area","area_id")
						->field("street")
						->field("street_number")
						->field("building")
						->field("unit")
						->field("additional")
						->field("address_type")
						// ->where("id ='".$id."'");
						->where_value("Postal_address","id",$id);
						
		$adrs = $q->execute();
		
		if(!isset($adrs[0]['id'])) $json_address.= "{}";
		else{
			$json_address.= "{";
			$json_address.= "id: ".json_encode($adrs[0]["id"]).", ";
			$json_address.= "country: ".json_encode($adrs[0]["country_id"]).", ";
			$json_address.= "street_name: ".json_encode($adrs[0]["street"]).", ";
			$json_address.= "street_number: ".json_encode($adrs[0]["street_number"]).", ";
			$json_address.= "building: ".json_encode($adrs[0]["building"]).", ";
			$json_address.= "unit: ".json_encode($adrs[0]["unit"]).", ";
			$json_address.= "additional: ".json_encode($adrs[0]["additional"]).", ";
			$json_address.= "address_type: ".json_encode($adrs[0]["address_type"]).", ";
			$json_address.= "geographic_area:";
			if(isset($adrs[0]['area_id']) && $adrs[0]['area_id'] <> null){
				$json_address.= "{id:".json_encode($adrs[0]['area_id']).", text:\"";
				$q = SQLQuery::create()->select("Geographic_area")
							->field("parent")
							->field("name")
							->where("id = '".$adrs[0]['area_id']."'");
				$areas = $q->execute();

				if(isset($areas[0]["name"])){
					$json_address.= $areas[0]["name"];
					$parent = $areas[0]['parent'];
					while($parent <> null){
						$q = SQLQuery::create()->select("Geographic_area")
										->field("parent")
										->field("name")
										->where("id = '".$parent."'");
						$ar = $q->execute();
						$json_address.= ", ".$ar[0]['name'];
						$parent = $ar[0]["parent"];
					}
				}
				$json_address.= "\"}";
			} else $json_address.= "null";
			if(isset($country[0]["name"])) $json_address.= ", country_name:".json_encode($country[0]["name"]);
			if(isset($country[0]["code"])) $json_address.= ", country_code:".json_encode($country[0]["code"]);
			$json_address.= "}";
		}
		return $json_address;
	}
}
?>