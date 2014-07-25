<?php 
class service_exam_remove_subject_extract extends Service {
	
	public function getRequiredRights() { return array("manage_exam_subject"); }
	
	public function documentation() { echo "Remove an ExamSubjectExtract"; }
	public function inputDocumentation() { echo "id"; }
	public function outputDocumentation() {	echo "true"; }
	
	public function execute(&$component, $input) {
		$id = intval($input["id"]);
		
		// TODO check we can really modify this (not yet any grade...)
		
		SQLQuery::create()->bypassSecurity()->removeKey("ExamSubjectExtract", $id);
		// check if we don't have a rule without value anymore
		$orphans = SQLQuery::create()->select("ExamEligibilityRule")->join("ExamEligibilityRule","ExamEligibilityRuleTopic",array("id"=>"rule"))->whereNull("ExamEligibilityRuleTopic","rule")->execute();
		if (count($orphans) > 0)
			SQLQuery::create()->bypassSecurity()->removeRows("ExamEligibilityRule",$orphans);
		
		echo "true";
	}
	
}
?>