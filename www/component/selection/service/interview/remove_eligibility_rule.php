<?php 
class service_interview_remove_eligibility_rule extends Service {
	
	public function getRequiredRights() { return array("manage_interview_criteria"); }
	
	public function documentation() { echo "Remove an eligibility rule"; }
	public function inputDocumentation() { echo "id"; }
	public function outputDocumentation() {	echo "true"; }
	
	public function execute(&$component, $input) {
		$id = intval($input["id"]);
		
		SQLQuery::startTransaction();
		
		SQLQuery::create()->bypassSecurity()->removeKey("InterviewEligibilityRule", $id);
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
		
}
?>