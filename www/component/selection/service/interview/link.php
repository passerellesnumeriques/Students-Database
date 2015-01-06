<?php 
class service_interview_link extends Service {
	
	public function getRequiredRights() { return array("manage_interview_center"); }
	
	public function documentation() { echo "Add and remove links between Exam Centers and Interview Centers"; }
	public function inputDocumentation() { echo "add, remove: list of {exam_center,interview_center} to add or remove"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		
		// 1- remove links
		foreach ($input["remove"] as $link) {
			// remove the link
			SQLQuery::create()->removeKey("InterviewCenterExamCenter", array("interview_center"=>$link["interview_center"], "exam_center"=>$link["exam_center"]));
			// unassign applicants
			$applicants = SQLQuery::create()
				->select("Applicant")
				->whereValue("Applicant", "exam_center", $link["exam_center"])
				->whereValue("Applicant", "interview_center", $link["interview_center"])
				->field("people")
				->executeSingleField();
			if (count($applicants) > 0)
				SQLQuery::create()->updateAllKeys("Applicant", $applicants, array("interview_center"=>null,"interview_session"=>null));
		}
		
		// 2- add links
		$links_to_insert = array();
		foreach ($input["add"] as $link) {
			// assign applicants
			$applicants = SQLQuery::create()
				->select("Applicant")
				->whereValue("Applicant","exam_center",$link["exam_center"])
				->whereNull("Applicant","interview_center")
				->whereValue("Applicant", "exam_passer", 1)
				->whereNotValue("Applicant","excluded", 1)
				->field("people")
				->executeSingleField();
			if (count($applicants) > 0)
				SQLQuery::create()->updateAllKeys("Applicant", $applicants, array("interview_center"=>$link["interview_center"]));
		}
		if (count($input["add"]) > 0)
			SQLQuery::create()->insertMultiple("InterviewCenterExamCenter", $input["add"]);
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>