<?php 
class service_add_organization extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Create a new organization"; }
	public function inputDocumentation() { echo "Organization object (defined in contact_objects.js)"; }
	public function outputDocumentation() { echo "On success, return the id of the newly created organization"; }
	
	public function execute(&$component, $input) {

		// validate the types
		if (count($input["types_ids"]) > 0) {
			$types = SQLQuery::create()->select("OrganizationType")->whereIn("id", $input["types_ids"])->execute();
			if (count($types) <> count($input["types_ids"])) {
				PNApplication::error("Invalid organization types");
				return;
			}
		}
		
		// create Organization
		$org = array(
			"name"=>$input["name"],
			"creator"=>$input["creator"],
			"obsolete"=>false
		);
		try {
			$org_id = SQLQuery::create()->insert("Organization", $org);
		} catch (Exception $e) {
			PNApplication::error($e);
			return;
		}
		
		// create types
		if (count($input["types_ids"]) > 0) {
			foreach ($input["types_ids"] as $type) {
				try {
					SQLQuery::create()->insert("OrganizationTypes", array("organization"=>$org_id,"type"=>$type));
				} catch (Exception $e) {
					PNApplication::error($e);
					// rollback
					SQLQuery::create()->bypassSecurity()->removeKey("Organization", $org_id);
					return;
				}
			}
		}
		
		// create contacts
		if (isset($input["contacts"]) && is_array($input["contacts"]))
		foreach ($input["contacts"] as $contact) {
			$contact_id = $component->addContactToOrganization($org_id, $contact);
			if ($contact_id === false) {
				// rollback
				SQLQuery::create()->bypassSecurity()->removeKey("Organization", $org_id);
				return;
			}
		}

		// create addresses
		if (isset($input["addresses"]) && is_array($input["addresses"]))
		foreach ($input["addresses"] as $address) {
			$address_id = $component->addAddressToOrganization($org_id, $address);
			if ($address_id === false) {
				// rollback
				SQLQuery::create()->bypassSecurity()->removeKey("Organization", $org_id);
				return;
			}
		}
		
		// create contact points
		if (isset($input["contact_points"]) && is_array($input["contact_points"]))
		foreach ($input["contact_points"] as $cp) {
			for ($i = 0; $i < count($cp["create_people"]); $i++)
				if ($cp["create_people"][$i]["path"] == "People<<ContactPoint(people)") {
					if (!isset($cp["create_people"][$i]["columns"]))
						$cp["create_people"][$i]["columns"] = array();
					$cp["create_people"][$i]["columns"]["organization"] = $org_id;
					break;
				}
			$create_input = array(
				"root"=>"People",
				"paths"=>$cp["create_people"]
			);
			Service::internalExecution("data_model", "create_data", $create_input);
		}
		
		echo "{id:".$org_id."}";
	}
	
}
?>