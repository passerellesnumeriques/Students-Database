<?php
/**
 * Manage contacts (email, phone, IM, postal address) for people and organizations
 */
class contact extends Component {
	
	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "NotesPlugin":
				require_once("OrganizationNotes.inc");
				return array(
					new OrganizationNotes()
				);
			case "PeopleTypePlugin":
				require_once("ContactPointPeopleTypePlugin.inc");
				require_once("OrganizationPlugin.inc");
				$list = array();
				foreach (PNApplication::$instance->components as $c) {
					if ($c == $this) continue;
					foreach ($c->getPluginImplementations("OrganizationPlugin") as $pi)
						array_push($list, new ContactPointPeopleTypePlugin($pi));
				}
				return $list;
		}
		return array();
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
	 * Join to the table PostalAddress
	 * @param SQLQuery $q the query to include the join
	 * @param string $from_table the table from which to join
	 * @param string $from_key the name of the foreign key for the join
	 */
	public function joinPostalAddress(&$q, $from_table, $from_key) {
		$q->join($from_table, "PostalAddress", array($from_key=>"id"));
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
	 * @param boolean $builtin if true, this type won't be editable neither removable
	 * @return boolean|number the new type id, or false if it failed
	 */
	public function createOrganizationType($creator, $name, $builtin = false) {
		require_once("component/contact/OrganizationPlugin.inc");
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations("OrganizationPlugin") as $pi) {
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
			return SQLQuery::create()->insert("OrganizationType", array("creator"=>$creator, "name"=>$name, "builtin"=>$builtin));
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
	 * Get an organization name from its ID
	 * @param integer $id id
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
	 * @param integer $organization id
	 * @param integer $people id
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
	
	/**
	 * Join to the table Organization
	 * @param SQLQuery $q the query to include the join
	 * @param string $from_table the table from which to join
	 * @param string $from_key the name of the foreign key for the join
	 * @param string $alias alias to use or null. If specified, it will be the Organization table alias, and all the fields of the organization will be precedeed by this alias and a underscore 
	 */
	public function joinOrganization(&$q, $from_table, $from_key, $alias = null) {
		$q->join($from_table, "Organization", array($from_key=>"id"), $alias);
		require_once("component/contact/ContactJSON.inc");
		ContactJSON::OrganizationSQL($q, $alias);
	}

	/**
	 * Retrieve the contacts of the given organizations
	 * @param integer[] $orgs_ids organizations' ids
	 * @return array the rows from table OrganizationContact joined with table Contact, with field 'contact_organization_id' plus the ones of the ContactJSON
	 */
	public function getOrganizationsContacts($orgs_ids) {
		$q = SQLQuery::create()
			->select("OrganizationContact")
			->whereIn("OrganizationContact", "organization", $orgs_ids)
			->join("OrganizationContact", "Contact", array("contact"=>"id"))
			->field("OrganizationContact", "organization", "contact_organization_id")
			;
		require_once("component/contact/ContactJSON.inc");
		ContactJSON::ContactSQL($q);
		return $q->execute();
	}

	/**
	 * Retrieve the addresses of the given organizations
	 * @param integer[] $orgs_ids organizations' ids
	 * @return array the rows from table OrganizationAddress joined with table PostalAddress, with field 'address_organization_id' plus the ones of the ContactJSON for postal addresses
	 */
	public function getOrganizationsAddresses($orgs_ids) {
		$q = SQLQuery::create()
			->select("OrganizationAddress")
			->whereIn("OrganizationAddress", "organization", $orgs_ids)
			->join("OrganizationAddress", "PostalAddress", array("address"=>"id"))
			->field("OrganizationAddress", "organization", "address_organization_id")
			;
		require_once("component/contact/ContactJSON.inc");
		ContactJSON::PostalAddressSQL($q);
		return $q->execute();
	}
	
	/**
	 * Retrieve the contact points for the given organizations
	 * @param integer[] $orgs_ids organizations' ids
	 * @return array the rows from table ContactPoint, with fields from ContactJSON::OrganizationContactPointSQL
	 */
	public function getOrganizationsContactPoints($orgs_ids) {
		$q = SQLQuery::create()
			->select("ContactPoint")
			->whereIn("ContactPoint", "organization", $orgs_ids)
			;
		require_once("component/contact/ContactJSON.inc");
		ContactJSON::OrganizationContactPointSQL($q);
		return $q->execute();
	}
	
	/**
	 * Retrieve the addresses of the given peoples
	 * @param integer[] $peoples_ids the peoples
	 * @return array the rows from table PeopleAddress joined with table PostalAddress, with field 'address_people_id' plus the ones of the ContactJSON for postal addresses
	 */
	public function getPeoplesAddresses($peoples_ids) {
		$q = SQLQuery::create()
			->select("PeopleAddress")
			->whereIn("PeopleAddress", "people", $peoples_ids)
			->join("PeopleAddress", "PostalAddress", array("address"=>"id"))
			->field("PeopleAddress", "people", "address_people_id")
			;
		require_once("component/contact/ContactJSON.inc");
		ContactJSON::PostalAddressSQL($q);
		return $q->execute();
	}
	
	/**
	 * Retrieve the contacts of the given peoples
	 * @param integer[] $peoples_ids the peoples
	 * @param array $contacts_types if given, filter by contact type (email, phone or IM)
	 * @return array the rows from table PeopleContact joined with table Contact with all fields of the Contact table plus 'people' giving the people id
	 */
	public function getPeoplesContacts($peoples_ids, $contacts_types = null) {
		$q = SQLQuery::create()->select("PeopleContact")
			->whereIn("PeopleContact", "people", $peoples_ids)
			->join("PeopleContact", "Contact", array("contact"=>"id"));
		if ($contacts_types <> null)
			$q->whereIn("Contact", "type", $contacts_types);
		$q->fieldsOfTable("Contact");
		$q->field("PeopleContact","people","people");
		return $q->execute();
	}
	
	/** Retrieve emails
	 * @param integer[] $contacts_ids ids
	 * @param boolean $bypass_security indicates if we should bypass the security checks
	 * @return array rows from table Contact
	 */
	public function getEMails($contacts_ids, $bypass_security = false) {
		return SQLQuery::create()->bypassSecurity($bypass_security)->select("Contact")->whereIn("Contact","id",$contacts_ids)->whereValue("Contact","type","email")->execute();
	}
	
	/**
	 * Retrieve the preferred email to use for the given people: first the PN address, else the first available one
	 * @param integer[] $peoples_ids the peoples
	 * @param boolean $bypass_security security check or not
	 * @return array associative array with people id as key, and the preferred email or null as value
	 */
	public function getPeoplesPreferredEMail($peoples_ids, $bypass_security = false) {
		$emails = SQLQuery::create()->bypassSecurity($bypass_security)
			->select("PeopleContact")
			->whereIn("PeopleContact","people",$peoples_ids)
			->join("PeopleContact","Contact",array("contact"=>"id"))
			->whereValue("Contact","type","email")
			->field("PeopleContact","people","people")
			->field("Contact","contact","contact")
			->execute();
		$result = array();
		foreach ($peoples_ids as $id) {
			$result[$id] = null;
			foreach ($emails as $e) {
				if ($e["people"] <> $id) continue;
				$email = $e["contact"];
				$i = strpos($email, "@");
				if ($i === false) continue;
				$domain = substr($email, $i+1);
				if (strpos($email, "passerellesnumeriques.org") == strlen($email)-25) {
					$result[$id] = $email;
					break;
				}
				if ($result[$id] == null) $result[$id] = $email;
			}
		}
		return $result;
	}
}
?>