<?php 
class service_save_students_evaluations_grades extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Save students' grades for a given subject"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>subject_id</code></li>";
		echo "<li><code>students</code>: list of {<code>people</code>, <code>grades</code>:[{<code>evaluation</code>, <code>grade</code>}]</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		// check access
		if (!PNApplication::$instance->user_management->hasRight("edit_students_grades")) {
			$students_ids = array();
			foreach ($input["students"] as $s) array_push($students_ids, $s["people"]);
			if (count($students_ids) > 0)
				if (!PNApplication::$instance->teaching->isAssignedToSubjectAndStudents(PNApplication::$instance->user_management->people_id, $input["subject_id"], $students_ids)) {
					PNApplication::error("Access denied");
					return;
				}
		}
		set_time_limit(120);
		SQLQuery::startTransaction();
		$subject = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectGrading")->whereValue("CurriculumSubjectGrading", "subject", $input["subject_id"])->executeSingleRow();
		if ($subject == null) {
			PNApplication::error("No information about this subject regarding grades");
			SQLQuery::commitTransaction();
			return;
		}
		if ($subject["only_final_grade"] == 1) {
			PNApplication::error("This subject is configured to have only final grades: you cannot change the grades of the students except the final grades");
			SQLQuery::commitTransaction();
			return;
		}
		// update students grades
		$component->update_students_evaluation_grades($input["subject_id"], $input["students"]);
		set_time_limit(120);
		SQLQuery::commitTransaction();
		echo "true";
	}
	
}
?>