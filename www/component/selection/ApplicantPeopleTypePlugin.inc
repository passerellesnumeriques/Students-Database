<?php 
require_once("component/people/PeopleTypePlugin.inc");
class ApplicantPeopleTypePlugin implements PeopleTypePlugin {
	
	public function getSecurityPriority() { return 100; }
	
	public function getId() { return "applicant"; }
	public function getName() { return "Applicant"; }
	public function getIcon16() { return "/static/selection/applicant/applicant_16.png"; }
	public function getIcon32() { return "/static/selection/applicant/applicant_32.png"; }
	public function isStandalone() { return true; }
	public function canReassignSameType($people_id) {
		$current_campaign = SQLQuery::create()->bypassSecurity()
			->select("smlink_Applicant_People")
			->whereValue("smlink_Applicant_People", "root", $people_id)
			->whereValue("smlink_Applicant_People", "sm", PNApplication::$instance->selection->getCampaignId())
			->executeSingleRow();
		if ($current_campaign <> null)
			return null; // already in the same campaign
		return "Use it for this selection campaign";
	}
	public function reassign($people_id, $data) {
		SQLQuery::startTransaction();
		$cid = PNApplication::$instance->selection->getCampaignId();
		foreach ($data as $path) {
			if ($path["path"] == "People<Applicant[".$cid."](people)") {
				require_once 'component/data_model/Model.inc';
				$display = DataModel::get()->getTableDataDisplay("Applicant");
				$tables_fields = new TablesToUpdate();
				$tables_fields->addValue("Applicant", $cid, $people_id, "people", $people_id);
				foreach ($path["value"] as $data_value) {
					$data = $display->getDataDisplayByName($data_value["name"], null, $cid);
					$data->saveData($people_id, $data_value["value"], $cid, $tables_fields, "people", $people_id);
				}
				$display->finalizeCreateData($people_id, $cid, $tables_fields, null);
				$tables_fields->execute(true);
				break;
			}
		}
		if (!PNApplication::hasErrors()) SQLQuery::commitTransaction();
	}
	
	public function getTables() { return array("Applicant"); }
	
	public function canRead() {
		return PNApplication::$instance->user_management->hasRight("see_applicant_info");
	}
	public function canWrite() {
		return PNApplication::$instance->user_management->hasRight("edit_applicants");
	}
	public function canRemove() {
		return PNApplication::$instance->user_management->hasRight("edit_applicants");
	}
	
	public function getPeopleRowDescription($people) {
		$list = PNApplication::$instance->selection->getCampaignsFromApplicant($people["id"], true);
		$s = "Applicant during ";
		$first = true;
		foreach ($list as $c) {
			if ($first) $first = false; else $s .= ", ";
			$s .= "campaign ".toHTML($c["name"]);
		}
		return $s;
	}
	
	public function getBuiltinDefaultUserRoles($people) { return array(); }
	
	public function remove($people_id) {
		// TODO with sub model helper
	}
}
?>