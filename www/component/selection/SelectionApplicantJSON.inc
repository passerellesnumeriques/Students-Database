<?php 
class SelectionApplicantJSON {
	
	public static function ApplicantSQL(&$q) {
		require_once("component/people/PeopleJSON.inc");
		$people = $q->getTableAlias("People");
		if ($people == null)
			PNApplication::$instance->people->joinPeople($q, "Applicant", "people");
		PeopleJSON::PeopleSQL($q);
		$q->field("Applicant", "applicant_id", "applicant_id");
		$q->field("Applicant", "exclusion_reason", "applicant_exclusion_reason");
		$q->field("Applicant", "automatic_exclusion_step", "applicant_automatic_exclusion_step");
		$q->field("Applicant", "automatic_exclusion_reason", "applicant_automatic_exclusion_reason");
		$q->field("Applicant", "custom_exclusion", "applicant_custom_exclusion");
		$q->field("Applicant", "excluded", "applicant_excluded");
		$q->field("Applicant", "information_session", "applicant_information_session_id");
		$q->field("Applicant", "exam_center", "applicant_exam_center_id");
		$q->field("Applicant", "exam_center_room", "applicant_exam_center_room_id");
		$q->field("Applicant", "exam_session", "applicant_exam_session_id");
		$q->field("Applicant", "exam_attendance", "applicant_exam_attendance");
	}
	
	public static function ApplicantJSON($row) {
		require_once("component/people/PeopleJSON.inc");
		$s = "{";
		$s .= "people:".PeopleJSON::People($row);
		$s .= ",applicant_id:".$row["applicant_id"];
		$s .= ",exclusion_reason:".json_encode($row["applicant_exclusion_reason"]);
		$s .= ",automatic_exclusion_step:".json_encode($row["applicant_automatic_exclusion_step"]);
		$s .= ",automatic_exclusion_reason:".json_encode($row["applicant_automatic_exclusion_reason"]);
		$s .= ",custom_exclusion:".json_encode($row["applicant_custom_exclusion"]);
		$s .= ",excluded:".json_encode($row["applicant_excluded"]);
		$s .= ",information_session_id:".json_encode($row["applicant_information_session_id"]);
		$s .= ",exam_center_id:".json_encode($row["applicant_exam_center_id"]);
		$s .= ",exam_center_room_id:".json_encode($row["applicant_exam_center_room_id"]);
		$s .= ",exam_session_id:".json_encode($row["applicant_exam_session_id"]);
		$s .= ",exam_attendance:".json_encode($row["applicant_exam_attendance"]);
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