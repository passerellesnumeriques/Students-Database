<?php 
require_once("component/people/PeoplePlugin.inc");
/**
 * Implementation of PeoplePlugin for the contact component
 */
class ContactPeoplePlugin implements PeoplePlugin {
	
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
				$contact_id = SQLQuery::create()->bypassSecurity()->insert("Contact", array("type"=>$contact["type"],"contact"=>$contact["contact"],"sub_type"=>$contact["sub_type"]));
				array_push($create_data["contacts"], $contact_id);
				SQLQuery::create()->bypassSecurity()->insert("PeopleContact", array("people"=>$people_id,"contact"=>$contact_id));
			}
			foreach ($input["addresses"] as $address) {
				$address_id = SQLQuery::create()->bypassSecurity()->insert("PostalAddress",
						array(
								"country"=>$address["country_id"],
								"geographic_area"=>$address["geographic_area"],
								"street"=>$address["street"],
								"street_number"=>$address["street_number"],
								"building"=>$address["building"],
								"unit"=>$address["unit"],
								"additional"=>$address["additional"],
								"address_type"=>$address["address_type"]
						)
				);
				array_push($create_data["addresses"], $address_id);
				SQLQuery::create()->bypassSecurity()->insert("PeopleAddress", array("people"=>$people_id,"address"=>$address_id));
			}
			if (in_array("organization_contact_point", $people_types))
				SQLQuery::create()->insert("ContactPoint", array("designation"=>$input["contact_point_designation"],"organization"=>$input["contact_point_organization"],"people"=>$people_id));
			return true;
		} catch (Exception $ex) {
			// rollback
			PNApplication::error($ex);
			return false;
		}
	}
	public function rollbackCreatePeople($people_id, $people_types, $input, &$create_data) {
		foreach ($create_data["contacts"] as $contact_id)
			SQLQuery::create()->bypassSecurity()->removeKey("Contact", $contact_id);
		foreach ($create_data["addresses"] as $address_id)
			SQLQuery::create()->bypassSecurity()->removeKey("PostalAddress", $address_id);
		if (in_array("organization_contact_point", $people_types))
			SQLQuery::create()->bypassSecurity()->removeKey("ContactPoint",array("organization"=>$input["contact_point_organization"],"people"=>$people_id));
	}
	
	public function getPeopleImportDataDisplay() {
		return array_merge(
				DataModel::get()->getTable("PeopleContact")->getDisplayHandler("people")->getDisplayableData(),
				DataModel::get()->getTable("PeopleAddress")->getDisplayHandler("people")->getDisplayableData()
		);
	}
	
	public function importPeopleData($input, $people_id) {
		$create_data = array("contacts"=>array(),"addresses"=>array());
		$table = DataModel::get()->getTable("PeopleContact");
		$display = $table->getDisplayHandler("people");
		foreach ($display->getDisplayableData() as $data) {
			$value = getDataDisplayFromInput($display->category, $data->getDisplayName(), $input);
			if ($value == null) continue;
			$create_data["contacts"] = array_merge($create_data["contacts"], $value["contacts"]);
		}
		$table = DataModel::get()->getTable("PeopleAddress");
		$display = $table->getDisplayHandler("people");
		foreach ($display->getDisplayableData() as $data) {
			$value = getDataDisplayFromInput($display->category, $data->getDisplayName(), $input);
			if ($value == null) continue;
			$create_data["addresses"] = array_merge($create_data["addresses"], $value["addresses"]);
		}
		$created_data = array();
		return $this->createPeople($people_id, array(), $create_data, $created_data);
	}
	
}
?>