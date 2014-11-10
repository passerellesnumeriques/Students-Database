<?php 
class service_save_students_final_grade extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Save final grades of students for a given subject"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>subject_id</code></li>";
		echo "<li><code>students</code>: list of {<code>people</code>, <code>final_grade</code>}</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		// check access
		if (!PNApplication::$instance->user_management->has_right("edit_students_grades")) {
			$students_ids = array();
			foreach ($input["students"] as $s) array_push($students_ids, $s["people"]);
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
		if ($subject["only_final_grade"] <> 1) {
			PNApplication::error("This subject is configured to have evaluations specified: you cannot change the final grade, it is automatically computed");
			SQLQuery::commitTransaction();
			return;
		}
		// update final grade of students
		set_time_limit(120);
		if (count($input["students"]) > 0)
			$component->update_students_final_grade($input["subject_id"], $input["students"]);
		set_time_limit(120);
		SQLQuery::commitTransaction();
		echo "true";
	}
	
}
?>