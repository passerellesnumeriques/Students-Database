<?php 
class service_unassign_teacher extends Service {
	
	public function getRequiredRights() { return array("edit_curriculum"); }
	
	public function documentation() { echo "Unassign a teacher from a subject/class"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>people_id</code>: teacher</li>";
		echo "<li><code>subject_id</code>: subject</li>";
		echo "<li><code>class_id</code>: optional, if specified, only assignment to this class is removed, if not specified, all assignments of the teacher for the subject are removed</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$people_id = $input["people_id"];
		$subject_id = $input["subject_id"];
		$class_id = @$input["class_id"];
		if ($class_id <> null)
			SQLQuery::create()->removeKey("TeacherAssignment", array("people"=>$people_id,"subject"=>$subject_id,"class"=>$class_id));
		else {
			$classes_ids = SQLQuery::create()
				->select("TeacherAssignment")
				->whereValue("TeacherAssignment","people", $people_id)
				->whereValue("TeacherAssignment", "subject", $subject_id)
				->field("TeacherAssignment", "class")
				->executeSingleField();
			if (count($classes_ids) > 0) {
				$keys = array();
				foreach ($classes_ids as $class_id)
					array_push($keys, array("people"=>$people_id,"subject"=>$subject_id,"class"=>$class_id));
				SQLQuery::create()->removeKeys("TeacherAssignment", $keys);
			}
		}
		if (!PNApplication::hasErrors()) echo "true";
	}
	
}
?>