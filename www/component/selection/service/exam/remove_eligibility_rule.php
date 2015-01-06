<?php 
class service_exam_remove_eligibility_rule extends Service {
	
	public function getRequiredRights() { return array("manage_exam_subject"); }
	
	public function documentation() { echo "Remove an eligibility rule"; }
	public function inputDocumentation() { echo "id"; }
	public function outputDocumentation() {	echo "true"; }
	
	public function execute(&$component, $input) {
		$id = intval($input["id"]);
		
		if (PNApplication::$instance->selection->hasInterviewResults()) {
			PNApplication::error("You cannot remove a rule because interview results are already entered");
			return;
		}
		SQLQuery::startTransaction();
		
		SQLQuery::create()->bypassSecurity()->removeKey("ExamEligibilityRule", $id);
		
		if (PNApplication::$instance->selection->hasExamResults()) {
			PNApplication::$instance->selection->applyExamEligibilityRules();
		}
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
		
}
?>