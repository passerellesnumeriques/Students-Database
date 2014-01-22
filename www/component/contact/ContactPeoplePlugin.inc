<?php 
require_once("component/people/PeoplePlugin.inc");
/**
 * Implementation of PeoplePlugin for the contact component
 */
class ContactPeoplePlugin implements PeoplePlugin {
	
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
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->canReadOrganization()) array_push($allowed, $pi->getOrganizationCreator());
			}
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
	
}
?>