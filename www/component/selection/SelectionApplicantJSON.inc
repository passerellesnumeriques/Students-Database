<?php 
class SelectionApplicantJSON {
	
	public static function ApplicantSQL(&$q) {
		require_once("component/people/PeopleJSON.inc");
		$people = $q->getTableAlias("People");
		if ($people == null)
			PNApplication::$instance->people->joinPeople($q, "Applicant", "people");
		PeopleJSON::PeopleSQL($q);
		$q->field("Applicant", "applicant_id", "applicant_id");
		$q->field("Applicant", "automatic_exclusion_step", "applicant_automatic_exclusion_step");
		$q->field("Applicant", "automatic_exclusion_reason", "applicant_automatic_exclusion_reason");
		$q->field("Applicant", "custom_exclusion", "applicant_custom_exclusion");
		$q->field("Applicant", "excluded", "applicant_excluded");
		$q->field("Applicant", "information_session", "applicant_information_session_id");
		$q->field("Applicant", "exam_center", "applicant_exam_center_id");
		$q->field("Applicant", "exam_center_room", "applicant_exam_center_room_id");
		$q->field("Applicant", "exam_session", "applicant_exam_session_id");
		$q->field("Applicant", "exam_attendance", "applicant_exam_attendance");
		$q->field("Applicant", "exam_passer", "applicant_exam_passer");
		$q->field("Applicant", "interview_center", "applicant_interview_center_id");
		$q->field("Applicant", "interview_session", "applicant_interview_session_id");
		$q->field("Applicant", "interview_attendance", "applicant_interview_attendance");
		$q->field("Applicant", "interview_passer", "applicant_interview_passer");
	}
	
	public static function ApplicantJSON($row) {
		require_once("component/people/PeopleJSON.inc");
		$s = "{";
		$s .= "people:".PeopleJSON::People($row);
		$s .= ",applicant_id:".$row["applicant_id"];
		$s .= ",automatic_exclusion_step:".json_encode($row["applicant_automatic_exclusion_step"]);
		$s .= ",automatic_exclusion_reason:".json_encode($row["applicant_automatic_exclusion_reason"]);
		$s .= ",custom_exclusion:".json_encode($row["applicant_custom_exclusion"]);
		$s .= ",excluded:".json_encode($row["applicant_excluded"]);
		$s .= ",information_session_id:".json_encode($row["applicant_information_session_id"]);
		$s .= ",exam_center_id:".json_encode($row["applicant_exam_center_id"]);
		$s .= ",exam_center_room_id:".json_encode($row["applicant_exam_center_room_id"]);
		$s .= ",exam_session_id:".json_encode($row["applicant_exam_session_id"]);
		$s .= ",exam_attendance:".json_encode($row["applicant_exam_attendance"]);
		$s .= ",exam_passer:".json_encode($row["applicant_exam_passer"]);
		$s .= ",interview_center_id:".json_encode($row["applicant_interview_center_id"]);
		$s .= ",interview_session_id:".json_encode($row["applicant_interview_session_id"]);
		$s .= ",interview_attendance:".json_encode($row["applicant_interview_attendance"]);
		$s .= ",interview_passer:".json_encode($row["applicant_interview_passer"]);
		$s .= "}";
		return $s;
	}
	
	public static function ApplicantsJSON($rows) {
		$s = "[";
		$first = true;
		foreach ($rows as $row) {
			if ($first) $first = false; else $s .= ",";
			$s .= self::ApplicantJSON($row);
		}
		$s .= "]";
		return $s;
	}
}
?>