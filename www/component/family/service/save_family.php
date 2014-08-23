<?php 
class service_save_family extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		// TODO check security
		SQLQuery::startTransaction();
		// 1- save family
		$family = $input["family"];
		$family["entry_date"] = date("Y-m-d");
		if (intval($family["id"]) > 0) {
			// update
			$id = $family["id"];
			unset($family["id"]);
			SQLQuery::create()->bypassSecurity()->updateByKey("Family", $id, $family);
			$family["id"] = $id;
		} else {
			// new one
			unset($family["id"]);
			$family["id"] = SQLQuery::create()->bypassSecurity()->insert("Family", $family);
		}
		// 2- save members
		foreach ($input["members"] as &$member) {
			// create people if necessary
			if (isset($member["people_create"])) {
				$create = array("root"=>"People","sub_model"=>null,"paths"=>$member["people_create"]);
				$output = Service::internalExecution("data_model", "create_data", $create);
				unset($member["people_create"]);
				$q = PNApplication::$instance->people->getPeoplesSQLQuery(array($output["key"]));
				$q->bypassSecurity();
				require_once("component/people/PeopleJSON.inc");
				PeopleJSON::PeopleSQL($q, false);
				$member["people"] = $q->executeSingleRow();
			}
			$people = @$member["people"];
			if ($people <> null)
				$member["people"] = $people["people_id"];
			else
				$member["people"] = null;
			$member["entry_date"] = date("Y-m-d");
			$member["family"] = $family["id"];
			if (isset($member["id"]) && intval($member["id"]) > 0) {
				// update
				$id = $member["id"];
				unset($member["id"]);
				SQLQuery::create()->bypassSecurity()->updateByKey("FamilyMember", $id, $member);
				$member["id"] = $id;
			} else {
				// new one
				unset($member["id"]);
				$member["id"] = SQLQuery::create()->bypassSecurity()->insert("FamilyMember", $member);
			}
			if ($people <> null) $member["people"] = $people;
		}
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "{family:".json_encode($family).",members:".json_encode($input["members"])."}";
		}
	}
	
}
?>