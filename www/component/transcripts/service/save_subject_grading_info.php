<?php 
class service_save_subject_grading_info extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Save subject grading information"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>id</code>: subject id</li>";
		echo "<li><code>only_final_grade</code>: boolean indicating if only final grades will be entered, or all evaluations will be specified and the final grade automatically computed</li>";
		echo "<li><code>max_grade</code>: maximum grade of the subject</li>";
		echo "<li><code>passing_grade</code>: passing grade of the subject</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		// check access
		if (!PNApplication::$instance->user_management->has_right("edit_students_grades")) {
			if (!PNApplication::$instance->teaching->isAssignedToSubject(PNApplication::$instance->user_management->people_id, $input["subject_id"])) {
				PNApplication::error("Access denied");
				return;
			}
		}
		
		set_time_limit(120);
		// update subject information
		try {
			$component->set_subject_grading(
				$input["id"],
				$input["only_final_grade"],
				$input["max_grade"],
				$input["passing_grade"]
			);
			echo "true";
		} catch (Exception $e) {
			PNApplication::error($e);
			echo "false";
		}
	}
	
}
?>