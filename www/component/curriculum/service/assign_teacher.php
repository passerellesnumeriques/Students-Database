<?php 
class service_assign_teacher extends Service {
	
	public function get_required_rights() { return array("edit_curriculum"); }
	
	public function documentation() { echo "Assign a teacher to a subject and classes"; }
	public function input_documentation() {
		echo "<ul>";
		echo "<li><code>people_id</code>: teacher</li>";
		echo "<li><code>subject_id</code>: subject</li>";
		echo "<li><code>classes_ids</code>: the list of classes to assign</li>";
		echo "</ul>";
	}
	public function output_documentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$people_id = $input["people_id"];
		$subject_id = $input["subject_id"];
		$classes_ids = $input["classes_ids"];
		SQLQuery::startTransaction();
		$already = SQLQuery::create()
			->select("TeacherAssignment")
			->whereValue("TeacherAssignment", "subject", $subject_id)
			->whereIn("TeacherAssignment", "class", $classes_ids)
			->execute();
		if (count($already) > 0) {
			SQLQuery::rollbackTransaction();
			PNApplication::error("A teacher is already assigned");
			return;
		}
		$teacher = PNApplication::$instance->people->getPeople($people_id);
		if ($teacher == null) {
			SQLQuery::rollbackTransaction();
			PNApplication::error("Invalid teacher ID");
			return;
		}
		$types = PNApplication::$instance->people->parseTypes($teacher["types"]);
		if (!in_array("teacher", $types)) {
			SQLQuery::rollbackTransaction();
			PNApplication::error("This people is not a teacher");
			return;
		}
		// TODO check dates of teacher with classes
		$rows = array();
		foreach ($classes_ids as $class_id)
			array_push($rows, array("people"=>$people_id,"subject"=>$subject_id,"class"=>$class_id));
		SQLQuery::create()->insertMultiple("TeacherAssignment", $rows);
		if (!PNApplication::has_errors()) {
			SQLQuery::commitTransaction();
			echo "true";
		} else
			SQLQuery::rollbackTransaction();
	}
	
}
?>