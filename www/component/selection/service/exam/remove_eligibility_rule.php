<?php 
class service_exam_remove_eligibility_rule extends Service {
	
	public function getRequiredRights() { return array("manage_exam_subject"); }
	
	public function documentation() { echo "Remove an eligibility rule"; }
	public function inputDocumentation() { echo "id"; }
	public function outputDocumentation() {	echo "true"; }
	
	public function execute(&$component, $input) {
		$id = intval($input["id"]);
		
		// TODO check we can really modify this (not yet any grade...)
		
		SQLQuery::create()->bypassSecurity()->removeKey("ExamEligibilityRule", $id);
		
		echo "true";
	}
		
}
?>