<?php 
class service_applicant_get_applicants extends Service {
	
	public function getRequiredRights() { return array("see_applicant_info"); }
	
	public function documentation() { echo "Retrieve a list of applicants"; }
	public function inputDocumentation() {
		echo "All fields are optionals, to select applicants:<ul>";
		echo "<li><code>information_session</code>: Information session ID</li>";
		echo "<li><code>exam_center</code>: Exam center ID</li>";
		echo "<li><code>exam_center_room</code>: ID of a room in an exam center</li>";
		echo "<li><code>exam_session</code>: ID of an exam session</li>";
		echo "<li><code>excluded</code>: select only the one excluded or not excluded</li>";
		echo "<li><code>exam_passer</code>: select only the one who passed the exam</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "A list of Applicant JSON object"; }
	
	public function execute(&$component, $input) {
		$q = SQLQuery::create()->select("Applicant");
		if (isset($input["information_session"])) $q->whereValue("Applicant", "information_session", $input["information_session"]);
		if (isset($input["exam_center"])) $q->whereValue("Applicant", "exam_center", $input["exam_center"]);
		if (isset($input["exam_center_room"])) $q->whereValue("Applicant", "exam_center_room", $input["exam_center_room"]);
		if (isset($input["exam_session"])) $q->whereValue("Applicant", "exam_session", $input["exam_session"]);
		if (isset($input["excluded"])) $q->whereValue("Applicant", "excluded", $input["excluded"]);
		if (isset($input["exam_passer"])) $q->whereValue("Applicant", "exam_passer", $input["exam_passer"]);
		require_once("component/selection/SelectionApplicantJSON.inc");
		SelectionApplicantJSON::ApplicantSQL($q);
		$rows = $q->execute();
		echo SelectionApplicantJSON::ApplicantsJSON($rows);
	}
}
?>