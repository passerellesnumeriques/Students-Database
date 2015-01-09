<?php 
class service_applicant_remove extends Service {
	
	public function getRequiredRights() { return array("edit_applicants"); }
	
	public function documentation() { echo "Remove applicants from the database"; }
	public function inputDocumentation() { echo "applicants"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$applicants_ids = $input["applicants"];

		// for applicants which are also in another campaign, just remove in this campaign and move the link to the other campaign
		set_time_limit(300);
		$campaigns_ids = SQLQuery::create()->select("SelectionCampaign")->whereNotValue("SelectionCampaign", "id", PNApplication::$instance->selection->getCampaignId())->field("id")->executeSingleField();
		foreach ($campaigns_ids as $cid) {
			$common_applicants_ids = SQLQuery::create()->selectSubModel("SelectionCampaign", $cid)->select("Applicant")->whereIn("Applicant","people",$applicants_ids)->field("people")->executeSingleField();
			if (count($common_applicants_ids) == 0) continue;
			foreach ($common_applicants_ids as $applicant_id) {
				SQLQuery::create()->bypassSecurity()->updateByKey("smlink_Applicant_People", $applicant_id, array("sm"=>$cid));
				for ($i = count($applicants_ids)-1; $i >= 0; $i--)
					if ($applicants_ids[$i] == $applicant_id) {
						array_splice($applicants_ids, $i, 1);
						break;
					}
			}
			foreach (DataModel::get()->getSubModel("SelectionCampaign")->internalGetTables() as $table) {
				foreach ($table->internalGetColumnsFor($cid) as $col) {
					if ($col instanceof datamodel\ForeignKey && $col->foreign_table == "People") {
						if ($table->getPrimaryKey() == $col)
							SQLQuery::create()->bypassSecurity()->removeKeys($table->getName(), $common_applicants_ids);
						else {
							$rows = SQLQuery::create()->bypassSecurity()->select($table->getName())->whereIn($table->getName(),$col->name,$common_applicants_ids)->execute();
							if (count($rows) > 0)
								SQLQuery::create()->bypassSecurity()->removeRows($table->getName(), $rows);
						}
						break;
					}
				}
			}
		}
		if (count($applicants_ids) == 0) {
			echo "true";
			return;
		}

		set_time_limit(300);
		$peoples = PNApplication::$instance->people->getPeoples($applicants_ids);
		
		$to_remove = array();
		$to_remove_type = array();
		foreach ($peoples as $p) {
			$types = PNApplication::$instance->people->parseTypes($p["types"]);
			$nb = count($types);
			if (!in_array("applicant", $types)) {
				PNApplication::error("One of the people is not an applicant!");
				return;
			}
			$nb--;
			if (in_array("family_member", $types))
				$nb--;
			if ($nb == 0)
				array_push($to_remove, $p["id"]);
			else
				array_push($to_remove_type, $p["id"]);
		}
		
		if (count($to_remove) > 0) {
			foreach ($to_remove as $people_id)
				PNApplication::$instance->people->removePeople($people_id);
		}
		if (count($to_remove_type) > 0) {
			PNApplication::$instance->people->removePeoplesType($to_remove_type, "applicant");
		}
		if (!PNApplication::hasErrors()) echo "true";
	}
	
}
?>