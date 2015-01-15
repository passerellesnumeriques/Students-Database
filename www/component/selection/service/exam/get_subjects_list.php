<?php 
class service_exam_get_subjects_list extends Service {
	
	public function getRequiredRights() { return array("see_exam_subject"); }
	
	public function documentation() { echo "Get the list of subjects"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "the list"; }
	
	public function execute(&$component, $input) {
		echo json_encode(SQLQuery::create()->select("ExamSubject")->execute());
	}
	
}
?>