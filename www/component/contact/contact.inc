<?php
/**
 * Manage contacts (email, phone, IM, postal address) for people and organizations
 */
class contact extends Component {
	
	public function getPluginImplementations() {
		$list = array(
		);
		require_once("ContactPointPeopleTypePlugin.inc");
		require_once("OrganizationPlugin.inc");
		foreach (PNApplication::$instance->components as $c) {
			if ($c == $this) continue;
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				array_push($list, new ContactPointPeopleTypePlugin($pi));
			}
		}
		return $list;
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
		$table = DataModel::get()->getTable("OrganizationAddress");
		if (!$fake && !$table->acceptInsert(array("organization"=>$organization_id))) {
			PNApplication::error("You are not allowed to add an address for this organization");
			return false;
		} else {
			$address_id = $this->addAddress("OrganizationAddress","organization",$organization_id,$new_address,$fake);
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
		$table = DataModel::get()->getTable("PeopleAddress");
		if (!$fake && !$table->acceptInsert(array("people"=>$people_id))) {
			PNApplication::error("You are not allowed to add an address for this people");
			return false;
		} else {
			$address_id = $this->addAddress("PeopleAddress","people",$people_id,$new_address,$fake);
			return $address_id;
		}
	}
	/**
	 * Internal method to add a PostalAddress in database
	 * @param string $table the table where to insert the join (PeopleAddress or OrganizationAddress)
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
			$address_id = SQLQuery::create()->bypassSecurity()->insert("PostalAddress", $new_address);
		} catch (Exception $ex) {
			$address_id = 0;
			PNApplication::error($ex);
		}
		if ($address_id == 0) {
			return false;
		}
		try {
			if($bypass)
				SQLQuery::create()->bypassSecurity()->insert($table, array($column=>$key,"address"=>$address_id));
			else
				SQLQuery::create()->insert($table, array($column=>$key,"address"=>$address_id));
		} catch (Exception $ex) {
			PNApplication::error($ex);
			SQLQuery::create()->removeKey("PostalAddress", $address_id);
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
		return $this->addContact("PeopleContact", "people", $people_id, $contact);
	}
	/**
	 * Add a contact to an organization
	 * @param number $org_id the organization id
	 * @param array $contact Contact structure
	 * @return boolean|number the new contact id, or false if it failed
	 */
	public function addContactToOrganization($org_id, $contact) {
		return $this->addContact("OrganizationContact", "organization", $org_id, $contact);
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
			$contact_id = SQLQuery::create()->bypassSecurity()->insert("Contact", array("type"=>$contact["type"],"contact"=>$contact["contact"],"sub_type"=>$contact["sub_type"]));
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
			SQLQuery::create()->removeKey("Contact", $contact_id);
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
						PNApplication::error("You are not allowed to create orgnization in ".$creator);
						return false;
					}
					break;
				}
			}
		}
		
		try {
			return SQLQuery::create()->insert("OrganizationType", array("creator"=>$creator, "name"=>$name));
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
			SQLQuery::create()->insert("OrganizationTypes", array("organization"=>$organization_id, "type"=>$type_id));
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
			SQLQuery::create()->removeKey("OrganizationTypes", array("organization"=>$organization_id, "type"=>$type_id));
			return true;
		}catch (Exception $e) {
			PNApplication::error($e);
			return false;
		}
	}

	/**
	 * Get all the organizations existings in a given geographic area (and all its children areas), and the matching addresses
	 * @param number $area_id
	 * @return array containing two fields:<ul><li>Organizations found in the given area</li><li>Organizations found in the parent area (all the organizations set in this array are distinct from the ones found in the given area (no double return)</li></ul>
	 * The structure of these arrays is: array("organization_id" => array("name" => organization_name, "addresses" => array(array(address =>, geographic_area_id =>, geographic_area_text=>))))
	 */
	public function getOrganizationsByGeographicArea($area_id){
		//Get the parent id
		$parent_id = PNApplication::$instance->geography->getAreaParent($area_id);
		//Get all the children
		$children = PNApplication::$instance->geography->getAreaAllChildrenFlat($area_id);
		array_push($children, $area_id);
		$organizations_in_this_area = SQLQuery::create()
			->select("PostalAddress")
			->field("PostalAddress","id","address_id")
			->field("PostalAddress","geographic_area","area_id")
			->join("PostalAddress","OrganizationAddress",array("id" => "address"))
			->join("OrganizationAddress","Organization",array("organization" => "id"))
			->field("Organization","id","organization_id")
			->field("Organization","name","organization_name")
			->whereIn("PostalAddress", "geographic_area",$children)
			->execute();
		
// 		$all_address_ids_from_area = array();
		$to_return_from_this_area = array();
		$to_return_from_parent_area = array();
		if($organizations_in_this_area <> null){
			foreach($organizations_in_this_area as $o){
				if(isset($to_return_from_this_area[$o["organization_id"]])){
					array_push($to_return_from_this_area[$o["organization_id"]]["addresses"],$this->getArrayAddressIDGeographicAreaText($o["address_id"], $o["area_id"]));
				} else {
					$to_return_from_this_area[$o["organization_id"]] = array(
						"addresses" => array($this->getArrayAddressIDGeographicAreaText($o["address_id"], $o["area_id"])),
						"name" => $o["organization_name"]
					);
				}
// 				array_push($all_address_ids_from_area, $o["address_id"]);
			}
		}
		if($parent_id <> null){//This is not the root level
			$children_from_parent = PNApplication::$instance->geography->getAreaAllChildrenFlat($parent_id);
			array_push($children_from_parent, $parent_id);
			//Remove all the ids already retrieved
			for($i = 0; $i < count($children); $i++){
				$index = array_search($children[$i], $children_from_parent);
				if($index <> null)
					array_splice($children_from_parent, $index, 1);
			}
			//Get the data
			if(count($children_from_parent) > 0){
				$q = SQLQuery::create()
				->select("PostalAddress")
				->field("PostalAddress","id","address_id")
				->field("PostalAddress","geographic_area","area_id")
				->join("PostalAddress","OrganizationAddress",array("id" => "address"))
				->join("OrganizationAddress","Organization",array("organization" => "id"))
				->field("Organization","id","organization_id")
				->field("Organization","name","organization_name")
				->whereIn("PostalAddress", "geographic_area",$children_from_parent);
// 				if(count($all_address_ids_from_area) > 0)
// 					$q->whereNotIn("PostalAddress","id",$all_address_ids_from_area);
				 $organizations_in_parent_area = $q->execute();
				
				if($organizations_in_parent_area <> null){
					foreach ($organizations_in_parent_area as $o){
						if(isset($to_return_from_parent_area[$o["organization_id"]])){
							array_push($to_return_from_parent_area[$o["organization_id"]]["addresses"],$this->getArrayAddressIDGeographicAreaText($o["address_id"], $o["area_id"]));
						} else {
							$to_return_from_parent_area[$o["organization_id"]] = array(
									"addresses" => array($this->getArrayAddressIDGeographicAreaText($o["address_id"], $o["area_id"])),
									"name" => $o["organization_name"]
							);
						}
					}
				}
			}
		}
		return array($to_return_from_this_area, $to_return_from_parent_area);
	}
	
	/**
	 * Get an array contaning an address, its geographic area, and the matching geographic area text from address id and area id
	 * @param number $address_id
	 * @param number $area_id
	 * @return array containing three attributes:<ul><li><code>address</code> the id of the address</li><li><code>geographic_area_id</code></li><li><code>geographic_area_text</code></li></ul>
	 */
	private function getArrayAddressIDGeographicAreaText($address_id, $area_id){
		return array("address" => $address_id, "geographic_area_id" => $area_id,"geographic_area_text" => PNApplication::$instance->geography->getGeographicAreaText($area_id));
	}
	
	/**
	 * Get an organization name from its ID
	 * @param number $id
	 */
	public function getOrganizationName($id){
		return SQLQuery::create()
			->select("Organization")
			->field("name")
			->whereValue("Organization","id",$id)
			->executeSingleValue();
	}
	
	/**
	 * Remove a contact point for a given organization
	 * @param number $organization
	 * @param number $people
	 * @return boolean true if well performed
	 */
	public function removeContactPoint($organization, $people){
		//TODO rights checked by people / orga plugin?
		try {
			SQLQuery::create()->removeKeys("ContactPoint", array(array("people" => $people, "organization" => $organization)));
		} catch (Exception $e){
			PNApplication::error($e);
		}
		if(PNApplication::hasErrors())
			return false;
		else
			return true;
	}
	
	/**
	 * Get the names of several organizations
	 * @param array $ids all the ids to retrieve
	 * @return array of array("id" =>organization id, "name" => organization name)
	 */
	public function getOrganizationsNames($ids){
		if(count($ids) > 0){
			return SQLQuery::create()
			->select("Organization")
			->field("id")
			->field("name")
			->whereIn("Organization","id",$ids)
			->execute();
		}
	}
	
	/**
	 * Get the geographic area attribute from a postal address
	 * @param number $id the postal address id
	 * @return NULL | number the geographic area id if found, else NULL
	 */
	public function getAddressGeographicAreaAttribute($id){
		return SQLQuery::create()
			->select("PostalAddress")
			->field("PostalAddress","geographic_area")
			->whereValue("PostalAddress", "id", $id)
			->executeSingleValue();
	}
}
?>