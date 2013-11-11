<?php
require_once("component/people/PeoplePlugin.inc"); 
class students extends Component implements PeoplePlugin {
	
	public function preparePeopleProfilePagesRequest(&$q, $people_id) {
		$alias = $q->table_id();
		$people_alias = $q->get_table_alias("People");
		$q->join($people_alias, "Student", array("id"=>"people"), $alias);
		$q->field($alias, "batch");
	}
	public function getPeopleProfilePages($people_id, $people, $q) {
		if ($people["batch"] == null) return array(); // not a student
		return array(); // TODO
	}
	
	public function getPeopleProfileGeneralInfoSections($people_id, $people, $q) {
		return array(); // TODO
	}
	
	public function getCreatePeopleSupportedTypes() { return array("student"); }
	public function isCreatePeopleAllowed($people_type) {
		if ($people_type <> "student") return false;
		return PNApplication::$instance->user_management->has_right("manage_batches", true);
	}
	public function getCreatePeoplePages($people_types) {
		if (!in_array("student", $people_types)) return array();
		return array(
			array("/static/students/student_16.png", "Student Batch", "component/students/page/create_people__student.inc",6)
		);
	}
	
	public function createPeople($people_id, $people_types, $input, &$create_data) {
		if (!in_array("student", $people_types)) return true;
		SQLQuery::create()->bypass_security()->insert("Student", array(
			"people"=>$people_id,
			"batch"=>$input["student_batch"]
		));
		return true;
	}
	public function rollbackCreatePeople($people_id, $people_type, $input, &$create_data) {
		if (!in_array("student", $people_types)) return;
		// automatically done, when people removed
	}
	
	public function filterPeopleReadAccess(&$q, $people_table_alias) {
		if (!PNApplication::$instance->user_management->has_right("consult_students_list")) return null;
		$student_alias = $q->get_table_alias("Student");
		if ($student_alias == null) {
			$student_alias = $q->table_id();
			$q->join($people_table_alias, "Student", array("id"=>"people"), $student_alias);
		}
		return "`".$student_alias."`.`batch` IS NOT NULL";
	}
	public function prepareSelectPeopleWriteAccess(&$q, $people_table_alias) {}
	public function filterPeopleWriteAccess($people_rows) { return $people_rows; }
	public function prepareSelectPeopleRemoveAccess(&$q, $people_table_alias, &$locks) {}
	public function filterPeopleRemoveAccess($people_rows) { return $people_rows; }
}
?>