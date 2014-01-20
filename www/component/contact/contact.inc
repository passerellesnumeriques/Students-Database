<?php
require_once("component/people/PeoplePlugin.inc");
/**
 * Manage contacts (email, phone, IM, postal address) for people and organizations
 */
class contact extends Component implements PeoplePlugin {
	
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
	
	/* functions for PeoplePlugin */
	
	public function preparePeopleProfilePagesRequest(&$q, $people_id) {}
	public function getPeopleProfilePages($people_id, $people, $q) {}
	
	public function getPeopleProfileGeneralInfoSections($people_id, $people, $q) {
		return array(
			array(
				"/static/contact/contact_16.png",
				"Contacts",
				"component/contact/page/people_contacts.inc",
				2
			)
		);
	}
	
	public function getCreatePeopleSupportedTypes() { return array("organization_contact_point"); }
	public function isCreatePeopleAllowed($people_type) {
		if ($people_type == "organization_contact_point") 
			return true;
		return false; 
	}
	public function getCreatePeoplePages($people_types) {
		$pages = array(array("/static/contact/contact_16.png","Contacts","component/contact/page/create_people__contacts.inc",2));
		if (in_array("organization_contact_point", $people_types))
			array_push($pages, array("/static/contact/contact_point.png","Contact Point","component/contact/page/create_people__contact_point.inc",5));
		return $pages;
	}
	public function createPeople($people_id, $people_types, $input, &$create_data) {
		$create_data["contacts"] = array();
		$create_data["addresses"] = array();
		try {
			foreach ($input["contacts"] as $contact) {
				$contact_id = SQLQuery::create()->bypass_security()->insert("Contact", array("type"=>$contact["type"],"contact"=>$contact["contact"],"sub_type"=>$contact["sub_type"]));
				array_push($create_data["contacts"], $contact_id);
				SQLQuery::create()->bypass_security()->insert("People_contact", array("people"=>$people_id,"contact"=>$contact_id));
			}
			foreach ($input["addresses"] as $address) {
				$address_id = SQLQuery::create()->bypass_security()->insert("Postal_address",
					array(
						"country"=>$address["country"],
						"geographic_area"=>$address["geographic_area"],
						"street"=>$address["street_name"],
						"street_number"=>$address["street_number"],
						"building"=>$address["building"],
						"unit"=>$address["unit"],
						"additional"=>$address["additional"],
						"address_type"=>$address["address_type"]
					)
				);
				array_push($create_data["addresses"], $address_id);
				SQLQuery::create()->bypass_security()->insert("People_address", array("people"=>$people_id,"address"=>$address_id));
			}
			if (in_array("organization_contact_point", $people_types))
				SQLQuery::create()->insert("Contact_point", array("designation"=>$input["contact_point_designation"],"organization"=>$input["contact_point_organization"],"people"=>$people_id));
			return true;
		} catch (Exception $ex) {
			// rollback
			PNApplication::error($ex);
			return false;
		}
	}
	public function rollbackCreatePeople($people_id, $people_types, $input, &$create_data) {
		foreach ($create_data["contacts"] as $contact_id)
			SQLQuery::create()->bypass_security()->remove_key("Contact", $contact_id);
		foreach ($create_data["addresses"] as $address_id)
			SQLQuery::create()->bypass_security()->remove_key("Postal_address", $address_id);
		if (in_array("organization_contact_point", $people_types))
			SQLQuery::create()->bypass_security()->remove_key("Contact_point",array("organization"=>$input["contact_point_organization"],"people"=>$people_id));
	}

	public function getPeopleImportDataDisplay() {
		return array_merge(
			DataModel::get()->getTable("People_contact")->getDisplayHandler("people")->getDisplayableData(),
			DataModel::get()->getTable("People_address")->getDisplayHandler("people")->getDisplayableData()
		); 
	}
	
	public function importPeopleData($input, $people_id) {
		$create_data = array("contacts"=>array(),"addresses"=>array());
		$table = DataModel::get()->getTable("People_contact");
		$display = $table->getDisplayHandler("people");
		foreach ($display->getDisplayableData() as $data) {
			$value = getDataDisplayFromInput($display->category, $data->getDisplayName(), $input);
			$create_data["contacts"] = array_merge($create_data["contacts"], $value["contacts"]); 
		}
		$table = DataModel::get()->getTable("People_address");
		$display = $table->getDisplayHandler("people");
		foreach ($display->getDisplayableData() as $data) {
			$value = getDataDisplayFromInput($display->category, $data->getDisplayName(), $input);
			$create_data["addresses"] = array_merge($create_data["addresses"], $value["addresses"]);
		}
		$created_data = array();
		return $this->createPeople($people_id, array(), $create_data, $created_data);
	}
	
	public function filterPeopleReadAccess(&$q, $people_table_alias) {
		$cp_alias = $q->get_table_alias("Contact_point");
		if ($cp_alias == null) {
			$cp_alias = $q->table_id();
			$q->join($people_table_alias, "Contact_point", array("id"=>"people"), $cp_alias);
		}
		$org_alias = $q->table_id();
		$q->join($cp_alias, "Organization", array("organization"=>"id"), $org_alias);
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof OrganizationPlugin)) continue;
			if ($c->canReadOrganization()) array_push($allowed, $c->getOrganizationCreator());
		}
		$w = "(";
		$first = true;
		foreach ($allowed as $creator) {
			if ($first) $first = false; else $w .= " OR ";
			$w .= "`".$org_alias."`.`creator`='".SQLQuery::escape($creator)."'";
		}
		$w .= ")";
		return $w;
	}
	public function prepareSelectPeopleWriteAccess(&$q, $people_table_alias) {
		$cp_alias = $q->get_table_alias("Contact_point");
		if ($cp_alias == null) {
			$cp_alias = $q->table_id();
			$q->join($people_table_alias, "Contact_point", array("id"=>"people"), $cp_alias);
		}
		$org_alias = $q->table_id();
		$q->join($cp_alias, "Organization", array("organization"=>"id"), $org_alias);
		DataModel::get()->getTable("Organization")->prepareSelectToDetermineWriteAccess($q, $org_alias);
	}
	public function filterPeopleWriteAccess($people_rows) {
		return DataModel::get()->getTable("Organization")->filterWriteAccess($people_rows);
	}
	public function prepareSelectPeopleRemoveAccess(&$q, $people_table_alias, &$locks) {
		$cp_alias = $q->get_table_alias("Contact_point");
		if ($cp_alias == null) {
			$cp_alias = $q->table_id();
			$q->join($people_table_alias, "Contact_point", array("id"=>"people"), $cp_alias);
		}
		$org_alias = $q->table_id();
		$q->join($cp_alias, "Organization", array("organization"=>"id"), $org_alias);
		DataModel::get()->getTable("Organization")->prepareSelectToDetermineRemoveAccess($q, $org_alias, $locks);
	}
	public function filterPeopleRemoveAccess($people_rows) { 
		return DataModel::get()->getTable("Organization")->filterRemoveAccess($people_rows);
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