<?php 
class service_si_save_grade extends Service {
	
	public function getRequiredRights() { return array("edit_social_investigation"); }
	
	public function documentation() { echo "Save comittee decision"; }
	public function inputDocumentation() { echo "applicant, grade"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		$applicant = SQLQuery::create()->select("Applicant")->whereValue("Applicant","people",$input["applicant"])->executeSingleRow();
		$to_update = array("si_grade"=>$input["grade"]);
		if ($input["grade"] == "Failed") {
			// failed: exclude from process if not yet done
			if ($applicant["excluded"] == 0) {
				$to_update["excluded"] = 1;
				$to_update["automatic_exclusion_step"] = "Social Investigation";
				$to_update["automatic_exclusion_reason"] = "Failed";
			}
		} else {
			// not failed: back to process if ever previously excluded because of grade
			if ($applicant["automatic_exclusion_step"] == "Social Investigation") {
				$to_update["excluded"] = 0;
				$to_update["automatic_exclusion_step"] = null;
				$to_update["automatic_exclusion_reason"] = null;
			}
		}
		SQLQuery::create()->updateByKey("Applicant", $input["applicant"], $to_update);
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>