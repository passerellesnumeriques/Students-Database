<?php 
class service_exam_link extends Service {
	
	public function getRequiredRights() { return array("manage_exam_center"); }
	
	public function documentation() { echo "Add and remove links between Information Sessions and Exam Centers"; }
	public function inputDocumentation() { echo "add, remove: list of {is,exam} to add or remove"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		
		// 1- remove links
		foreach ($input["remove"] as $link) {
			// remove the link
			SQLQuery::create()->removeKey("ExamCenterInformationSession", array("information_session"=>$link["is"], "exam_center"=>$link["exam"]));
			// unassign applicants
			$applicants = SQLQuery::create()
				->select("Applicant")
				->whereValue("Applicant", "exam_center", $link["exam"])
				->whereValue("Applicant", "information_session", $link["information_session"])
				->field("people")
				->executeSingleField();
			if (count($applicants) > 0)
				SQLQuery::create()->updateAllKeys("Applicant", $applicants, array("exam_center"=>null,"exam_session"=>null));
		}
		
		// 2- add links
		$links_to_insert = array();
		foreach ($input["add"] as $link) {
			// add the link
			array_push($links_to_insert, array("information_session"=>$link["is"],"exam_center"=>$link["exam"]));
			// assign applicants
			$applicants = SQLQuery::create()
				->select("Applicant")
				->whereValue("Applicant","information_session",$link["is"])
				->whereNull("Applicant","exam_center")
				->whereNotValue("Applicant","excluded",1)
				->field("people")
				->executeSingleField();
			if (count($applicants) > 0)
				SQLQuery::create()->updateAllKeys("Applicant", $applicants, array("exam_center"=>$link["exam"]));
		}
		if (count($links_to_insert) > 0)
			SQLQuery::create()->insertMultiple("ExamCenterInformationSession", $links_to_insert);
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>