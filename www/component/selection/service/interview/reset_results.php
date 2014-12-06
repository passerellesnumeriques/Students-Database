<?php 
class service_interview_reset_results extends Service {
	
	public function getRequiredRights() { return array("edit_interview_results"); }
	
	public function documentation() { echo "Remove results and attendance for one session or all"; }
	public function inputDocumentation() { echo "session (null for all)"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		if ($input["session"] == null) {
			// reset all
			$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantInterviewCriterionGrade")->execute();
			if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantInterviewCriterionGrade", $rows);
			$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantInterviewer")->execute();
			if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantInterviewer", $rows);
			SQLQuery::create()->updateAllRowsOfTableWithoutSecurity("Applicant", array("interview_attendance"=>null,"interview_comment"=>null,"interview_passer"=>null));
		} else {
			// reset only for 1 session
			$keys = SQLQuery::create()->bypassSecurity()->select("Applicant")->field("people")->whereValue("Applicant", "interview_session", $input["session"])->executeSingleField();
			if (count($keys) > 0) {
				$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantInterviewCriterionGrade")->whereIn("ApplicantInterviewCriterionGrade","people",$keys)->execute();
				if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantInterviewCriterionGrade", $rows);
				$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantInterviewer")->whereIn("ApplicantInterviewer","applicant",$keys)->execute();
				if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantInterviewer", $rows);
				SQLQuery::create()->bypassSecurity()->updateAllKeys("Applicant", $keys, array("interview_attendance"=>null,"interview_passer"=>null,"interview_comment"=>null));
			}
		}
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>