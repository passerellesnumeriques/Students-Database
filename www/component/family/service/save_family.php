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
		for ($i = 0; $i < count($input["members"]); $i++) {
			// create people if necessary
			if (isset($input["members"][$i]["people_create"])) {
				$create = array("root"=>"People","sub_model"=>null,"paths"=>$input["members"][$i]["people_create"]);
				$output = Service::internalExecution("data_model", "create_data", $create);
				unset($input["members"][$i]["people_create"]);
				$q = PNApplication::$instance->people->getPeoplesSQLQuery(array($output["key"]));
				$q->bypassSecurity();
				require_once("component/people/PeopleJSON.inc");
				PeopleJSON::PeopleSQL($q, false);
				$input["members"][$i]["people"] = $q->executeSingleRow();
			}
			$people = @$input["members"][$i]["people"];
			if ($people <> null)
				$input["members"][$i]["people"] = $people["people_id"];
			else
				$input["members"][$i]["people"] = null;
			$input["members"][$i]["entry_date"] = date("Y-m-d");
			$input["members"][$i]["family"] = $family["id"];
			if (isset($input["members"][$i]["id"]) && intval($input["members"][$i]["id"]) > 0) {
				// update
				$id = $input["members"][$i]["id"];
				unset($input["members"][$i]["id"]);
				SQLQuery::create()->bypassSecurity()->updateByKey("FamilyMember", $id, $input["members"][$i]);
				$member["id"] = $id;
			} else {
				// new one
				unset($input["members"][$i]["id"]);
				$input["members"][$i]["id"] = SQLQuery::create()->bypassSecurity()->insert("FamilyMember", $input["members"][$i]);
			}
			if ($people <> null) $input["members"][$i]["people"] = $people;
		}
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "{family:".json_encode($family).",members:".json_encode($input["members"])."}";
		}
	}
	
}
?>