<?php 
class service_save_students_final_grade extends Service {
	
	public function getRequiredRights() { return array(); } // TODO
	
	public function documentation() { echo "Save final grades of students for a given subject"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>subject_id</code></li>";
		echo "<li><code>students</code>: list of {<code>people</code>, <code>final_grade</code>}</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		set_time_limit(120);
		SQLQuery::startTransaction();
		$subject = SQLQuery::create()->select("CurriculumSubjectGrading")->whereValue("CurriculumSubjectGrading", "subject", $input["subject_id"])->executeSingleRow();
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