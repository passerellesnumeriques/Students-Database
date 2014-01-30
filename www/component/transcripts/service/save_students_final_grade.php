<?php 
class service_save_students_final_grade extends Service {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function documentation() { echo "Save final grades of students for a given subject"; }
	public function input_documentation() {
		echo "<ul>";
		echo "<li><code>subject_id</code></li>";
		echo "<li><code>students</code>: list of {<code>people</code>, <code>final_grade</code>}</li>";
		echo "</ul>";
	}
	public function output_documentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		set_time_limit(120);
		SQLQuery::start_transaction();
		$subject = SQLQuery::create()->select("CurriculumSubjectGrading")->where_value("CurriculumSubjectGrading", "subject", $input["subject_id"])->execute_single_row();
		if ($subject == null) {
			PNApplication::error("No information about this subject regarding grades");
			SQLQuery::end_transaction();
			return;
		}
		if ($subject["only_final_grade"] <> 1) {
			PNApplication::error("This subject is configured to have evaluations specified: you cannot change the final grade, it is automatically computed");
			SQLQuery::end_transaction();
			return;
		}
		// update final grade of students
		set_time_limit(120);
		$list = array();
		foreach ($input["students"] as $student)
			array_push($list, array("people"=>$student["people"], "final_grade"=>$student["final_grade"]));
		if (count($input["students"]) > 0)
			$component->update_students_final_grade($input["subject_id"], $input["students"]);
		set_time_limit(120);
		SQLQuery::end_transaction();
		echo "true";
	}
	
}
?>