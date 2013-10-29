<?php
require_once("component/people/ProfileGeneralInfoPlugin.inc");
class contact extends Component implements ProfileGeneralInfoPlugin {
	
	public function get_people_profile_general_info_sections($people_id) {
		return array(array("contacts","<img src='/static/contact/contact_16.png' style='vertical-align:bottom;padding-right:3px'/>Contacts",2));
	}
	
	public function generate_people_profile_general_info_section(&$page, $people_id, $section_id) {
		$contact_div_id = $page->generate_id();
		$address_div_id = $page->generate_id();
		echo "<table><tr><td valign=top id='$contact_div_id'></td><td valign=top id='$address_div_id'></td></tr></table>";
		require_once("page/contact.inc");
		contact($page, "people", $contact_div_id, $people_id);
		require_once("page/address.inc");
		address($page, "people", $address_div_id, $people_id);
	}
	
	public function get_create_people_pages($people_type) {
		return array(array("Contacts","component/contact/page/create_people__contacts.inc",2));
	}
	public function create_people($people_id, $people_type, $input, &$create_data) {
		$create_data["contacts"] = array();
		$create_data["addresses"] = array();
		try {
			foreach ($input["contacts"]["contacts"] as $contact) {
				$contact_id = SQLQuery::create()->insert("Contact", array("type"=>$contact["contact_type"],"contact"=>$contact["contact"],"sub_type"=>$contact["contact_sub_type"]));
				array_push($create_data["contacts"], $contact_id);
				SQLQuery::create()->insert("People_contact", array("people"=>$people_id,"contact"=>$contact_id));
			}
			foreach ($input["addresses"]["addresses"] as $address) {
				$address_id = SQLQuery::create()->insert("Postal_address",
					array(
						"country"=>$address["country"],
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
				SQLQuery::create()->insert("People_address", array("people"=>$people_id,"address"=>$address_id));
			}
			return true;
		} catch (Exception $ex) {
			// rollback
			PNApplication::error($ex);
			return false;
		}
	}
	public function rollback_create_people($people_id, $people_type, $input, &$create_data) {
		foreach ($create_data["contacts"] as $contact_id)
			SQLQuery::create()->remove_key("Contact", $contact_id);
		foreach ($create_data["addresses"] as $address_id)
			SQLQuery::create()->remove_key("Postal_address", $address_id);
	}
	
}
?>