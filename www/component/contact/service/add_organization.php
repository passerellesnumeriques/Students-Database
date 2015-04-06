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
		
		SQLQuery::startTransaction();
		
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
					SQLQuery::rollbackTransaction();
					return;
				}
			}
		}
		
		// create general contacts
		if (isset($input["general_contacts"]) && is_array($input["general_contacts"]))
		foreach ($input["general_contacts"] as $contact) {
			$contact_id = $component->addContactToOrganization($org_id, $contact);
			if ($contact_id === false) {
				SQLQuery::rollbackTransaction();
				return;
			}
		}

		// create general contact points
		if (isset($input["general_contact_points"]) && is_array($input["general_contact_points"]))
		foreach ($input["general_contact_points"] as $cp) {
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
		
		// create locations
		if (isset($input["locations"]) && is_array($input["locations"]))
			foreach ($input["locations"] as $location) {
				// create location address
				$address_id = $component->addAddressToOrganization($org_id, $location["address"], $location["name"]);
				if ($address_id === false) {
					SQLQuery::rollbackTransaction();
					return;
				}
				// create contacts
				foreach ($location["contacts"] as $contact) {
					$contact_id = $component->addContactToOrganization($org_id, $contact, $address_id);
					if ($contact_id === false) {
						SQLQuery::rollbackTransaction();
						return;
					}
				}
				// create contact points
				foreach ($location["contact_points"] as $cp) {
					for ($i = 0; $i < count($cp["create_people"]); $i++)
						if ($cp["create_people"][$i]["path"] == "People<<ContactPoint(people)") {
							if (!isset($cp["create_people"][$i]["columns"]))
								$cp["create_people"][$i]["columns"] = array();
							$cp["create_people"][$i]["columns"]["organization"] = $org_id;
							$cp["create_people"][$i]["columns"]["attached_location"] = $address_id;
							break;
						}
					$create_input = array(
						"root"=>"People",
						"paths"=>$cp["create_people"]
					);
					Service::internalExecution("data_model", "create_data", $create_input);
				}
			}

		if (PNApplication::hasErrors()) {
			SQLQuery::rollbackTransaction();
			return;
		}
		SQLQuery::commitTransaction();
		echo "{id:".$org_id."}";
	}
	
}
?>