<?php 
class service_save_students_evaluations_grades extends Service {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function documentation() { echo "Save students' grades for a given subject"; }
	public function input_documentation() {
		echo "<ul>";
		echo "<li><code>subject_id</code></li>";
		echo "<li><code>students</code>: list of {<code>people</code>, <code>grades</code>:[{<code>evaluation</code>, <code>grade</code>}]</li>";
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
		if ($subject["only_final_grade"] == 1) {
			PNApplication::error("This subject is configured to have only final grades: you cannot change the grades of the students except the final grades");
			SQLQuery::end_transaction();
			return;
		}
		// update students grades
		$component->update_students_evaluation_grades($input["subject_id"], $input["students"]);
		set_time_limit(120);
		SQLQuery::end_transaction();
		echo "true";
	}
	
}
?>