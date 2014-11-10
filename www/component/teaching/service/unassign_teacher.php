<?php 
class service_unassign_teacher extends Service {
	
	public function getRequiredRights() { return array("edit_curriculum"); }
	
	public function documentation() { echo "Unassign a teacher from a subject/class"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>people_id</code>: teacher</li>";
		echo "<li><code>subject_teaching_id</code>: subject</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$people_id = $input["people_id"];
		$subject_teaching_id = $input["subject_teaching_id"];
		SQLQuery::create()->removeKey("TeacherAssignment", array("people"=>$people_id,"subject_teaching"=>$subject_teaching_id));
		if (!PNApplication::hasErrors()) echo "true";
	}
	
}
?>