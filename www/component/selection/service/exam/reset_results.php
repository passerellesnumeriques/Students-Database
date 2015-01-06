<?php 
class service_exam_reset_results extends Service {
	
	public function getRequiredRights() { return array("edit_exam_results"); }
	
	public function documentation() { echo "Remove results, and optionally attendance, for one session or all"; }
	public function inputDocumentation() { echo "session, attendance"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		if (@$input["session"] == null) {
			// reset all
			$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamSubject")->execute();
			if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantExamSubject", $rows);
			$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamSubjectPart")->execute();
			if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantExamSubjectPart", $rows);
			$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamAnswer")->execute();
			if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantExamAnswer", $rows);
			$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamExtract")->execute();
			if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantExamExtract", $rows);
			if (@$input["attendance"]) {
				$keys = SQLQuery::create()->bypassSecurity()->select("Applicant")->field("people")->executeSingleField();
				if (count($keys) > 0) SQLQuery::create()->bypassSecurity()->updateAllKeys("Applicant", $keys, array("exam_attendance"=>null,"exam_passer"=>null));
			} else {
				$keys = SQLQuery::create()->bypassSecurity()->select("Applicant")->field("people")->executeSingleField();
				if (count($keys) > 0) SQLQuery::create()->bypassSecurity()->updateAllKeys("Applicant", $keys, array("exam_passer"=>null));
			}
		} else {
			// reset only for 1 session
			$keys = SQLQuery::create()->bypassSecurity()->select("Applicant")->field("people")->whereValue("Applicant", "exam_session", $input["session"])->executeSingleField();
			if (count($keys) > 0) {
				$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamSubject")->whereIn("ApplicantExamSubject","applicant",$keys)->execute();
				if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantExamSubject", $rows);
				$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamSubjectPart")->whereIn("ApplicantExamSubjectPart","applicant",$keys)->execute();
				if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantExamSubjectPart", $rows);
				$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamAnswer")->whereIn("ApplicantExamAnswer","applicant",$keys)->execute();
				if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantExamAnswer", $rows);
				$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamExtract")->whereIn("ApplicantExamExtract","applicant",$keys)->execute();
				if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantExamExtract", $rows);
				if (@$input["attendance"]) {
					SQLQuery::create()->bypassSecurity()->updateAllKeys("Applicant", $keys, array("exam_attendance"=>null,"exam_passer"=>null));
				} else {
					SQLQuery::create()->bypassSecurity()->updateAllKeys("Applicant", $keys, array("exam_passer"=>null));
				}
			}
		}
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>