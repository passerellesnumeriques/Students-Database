<?php 
class service_add_organization extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Create a new organization"; }
	public function input_documentation() { echo "Structure coming from an organization.js control"; }
	public function output_documentation() { echo "On success, return the id of the newly created organization"; }
	
	public function execute(&$component, $input) {

		// validate the types
		if (count($input["types"]) > 0) {
			$types = SQLQuery::create()->select("Organization_type")->where_in("id", $input["types"])->execute();
			if (count($types) <> count($input["types"])) {
				PNApplication::error("Invalid organization types");
				return;
			}
		}
		
		// create Organization
		$org = array(
			"name"=>$input["name"],
			"creator"=>$input["creator"]
		);
		try {
			$org_id = SQLQuery::create()->insert("Organization", $org);
		} catch (Exception $e) {
			PNApplication::error($e);
			return;
		}
		
		// create types
		if (count($input["types"]) > 0) {
			foreach ($input["types"] as $type) {
				try {
					SQLQuery::create()->insert("Organization_types", array("organization"=>$org_id,"type"=>$type));
				} catch (Exception $e) {
					PNApplication::error($e);
					// rollback
					SQLQuery::create()->bypass_security()->remove_key("Organization", $org_id);
					return;
				}
			}
		}
		
		// create contacts
		if (isset($input["contacts"]) && is_array($input["contacts"]))
		foreach ($input["contacts"] as $contact) {
			$contact["table"] = "Organization_contact";
			$contact["column"] = "organization";
			$contact["key"] = $org_id;
			$res = Service::internal_execution("contact", "add_contact", $contact);
			if (!isset($res["id"])) {
				// rollback
				SQLQuery::create()->bypass_security()->remove_key("Organization", $org_id);
				return;
			}
		}

		// create addresses
		if (isset($input["addresses"]) && is_array($input["addresses"]))
		foreach ($input["addresses"] as $address) {
			$address["table"] = "Organization_address";
			$address["column"] = "organization";
			$address["key"] = $org_id;
			$res = Service::internal_execution("contact", "add_address", $address);
			if (!isset($res["id"])) {
				// rollback
				SQLQuery::create()->bypass_security()->remove_key("Organization", $org_id);
				return;
			}
		}
		
		// create contact points
		if (isset($input["points"]) && is_array($input["points"]))
		foreach ($input["points"] as $cp) {
			$cp["create_people"]["contact_point_organization"] = $org_id;
			Service::internal_execution("people", "create_people", $cp["create_people"]);
		}
		
		echo "{id:".$org_id."}";
	}
	
}
?>