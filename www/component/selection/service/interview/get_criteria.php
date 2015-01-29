<?php 
class service_interview_get_criteria extends Service {
	
	public function getRequiredRights() { return array("see_interview_criteria"); }
	
	public function documentation() { echo "Returns the list of criteria for interviews"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "List of {id,name,max_score}"; }
	
	public function execute(&$component, $input) {
		echo json_encode(SQLQuery::create()->select("InterviewCriterion")->execute());
	}
	
}
?>