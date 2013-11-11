<?php
require_once("component/people/PeoplePlugin.inc");
class contact extends Component implements PeoplePlugin {
	
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
	
	public function getCreatePeopleSupportedTypes() { return array(); }
	public function isCreatePeopleAllowed($people_type) { return false; }
	public function getCreatePeoplePages($people_types) {
		return array(array("/static/contact/contact_16.png","Contacts","component/contact/page/create_people__contacts.inc",2));
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
	}
	
	public function filterPeopleReadAccess(&$q, $people_table_alias) {}
	public function prepareSelectPeopleWriteAccess(&$q, $people_table_alias) {}
	public function filterPeopleWriteAccess($people_rows) { return $people_rows; }
	public function prepareSelectPeopleRemoveAccess(&$q, $people_table_alias, &$locks) {}
	public function filterPeopleRemoveAccess($people_rows) { return $people_rows; }
}
?>