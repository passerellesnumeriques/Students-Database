<?php 
class service_exam_copy_subject extends Service {
	
	public function getRequiredRights() { return array("manage_exam_subject"); }
	
	public function documentation() { echo "Copy a subject from another campaign"; }
	public function inputDocumentation() { echo "subject, campaign"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		$subject = SQLQuery::create()->selectSubModel("SelectionCampaign", $input["campaign"])->select("ExamSubject")->whereValue("ExamSubject", "id", $input["subject"])->executeSingleRow();
		$versions = SQLQuery::create()->selectSubModel("SelectionCampaign", $input["campaign"])->select("ExamSubjectVersion")->whereValue("ExamSubjectVersion", "exam_subject", $input["subject"])->execute();
		$versions_ids = array();
		foreach ($versions as $v) array_push($versions_ids, $v["id"]);
		$parts = SQLQuery::create()->selectSubModel("SelectionCampaign", $input["campaign"])->select("ExamSubjectPart")->whereValue("ExamSubjectPart", "exam_subject", $input["subject"])->execute();
		$parts_ids = array();
		foreach ($parts as $p) array_push($parts_ids, $p["id"]);
		$questions = SQLQuery::create()->selectSubModel("SelectionCampaign", $input["campaign"])->select("ExamSubjectQuestion")->whereIn("ExamSubjectQuestion", "exam_subject_part", $parts_ids)->execute();
		$answers = SQLQuery::create()->selectSubModel("SelectionCampaign", $input["campaign"])->select("ExamSubjectAnswer")->whereIn("ExamSubjectAnswer", "exam_subject_version", $versions_ids)->execute();
		// copy subject
		unset($subject["id"]);
		$subject_id = SQLQuery::create()->insert("ExamSubject", $subject);
		// copy versions
		$to_insert = array();
		foreach ($versions as $v) array_push($to_insert, array("exam_subject"=>$subject_id));
		$ids = SQLQuery::create()->bypassSecurity()->insertMultiple("ExamSubjectVersion", $to_insert);
		$new_versions_ids = array();
		for ($i = 0; $i < count($versions); $i++) $new_versions_ids[$versions_ids[$i]] = $ids[$i];
		// copy parts
		$new_parts_ids = array();
		foreach ($parts as $p) {
			$old_id = $p["id"];
			unset($p["id"]);
			$p["exam_subject"] = $subject_id;
			$new_id = SQLQuery::create()->bypassSecurity()->insert("ExamSubjectPart", $p);
			$new_parts_ids[$old_id] = $new_id;
		}
		// copy questions
		$new_questions_ids = array();
		foreach ($questions as $q) {
			$old_id = $q["id"];
			unset($q["id"]);
			$q["exam_subject_part"] = $new_parts_ids[$q["exam_subject_part"]];
			$new_id = SQLQuery::create()->bypassSecurity()->insert("ExamSubjectQuestion", $q);
			$new_questions_ids[$old_id] = $new_id;
		}
		// copy answers
		foreach ($answers as $a) {
			$a["exam_subject_question"] = $new_questions_ids[$a["exam_subject_question"]];
			$a["exam_subject_version"] = $new_versions_ids[$a["exam_subject_version"]];
			SQLQuery::create()->bypassSecurity()->insert("ExamSubjectAnswer", $a);
		}
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>