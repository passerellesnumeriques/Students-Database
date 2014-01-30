<?php
require_once("component/people/PeoplePlugin.inc");
/**
 * Implementation of PeoplePlugin for the students
 */ 
class StudentsPeoplePlugin implements PeoplePlugin {
	
	public function preparePeopleProfilePagesRequest(&$q, $people_id) {
		$alias = $q->getTableAlias("Student");
		if ($alias == null) {
			$alias = $q->generateTableAlias();
			$people_alias = $q->getTableAlias("People");
			$q->join($people_alias, "Student", array("id"=>"people"), $alias);
		}
		if ($q->getFieldAlias("Student", "batch") <> "student_batch")
			$q->field($alias, "batch", "student_batch");
	}
	public function getPeopleProfilePages($people_id, $people, $q) {
		if ($people["student_batch"] == null) return array(); // not a student
		return array(); // TODO
	}
	
	public function getPeopleProfileGeneralInfoSections($people_id, $people, $q) {
		if ($people["student_batch"] == null) return array(); // not a student
		return array(
			array("/static/students/student_16.png","Student","component/students/page/people_profile__student.inc",10)
		);
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
		SQLQuery::create()->bypassSecurity()->insert("Student", array(
		"people"=>$people_id,
		"batch"=>$input["student_batch"]
		));
		return true;
	}
	public function rollbackCreatePeople($people_id, $people_type, $input, &$create_data) {
		if (!in_array("student", $people_types)) return;
		// automatically done, when people removed
	}
	
	public function getPeopleImportDataDisplay() { return array(); }
	public function importPeopleData($input, $people_id) { return true; }
	
	public function filterPeopleReadAccess(&$q, $people_table_alias) {
		if (!PNApplication::$instance->user_management->has_right("consult_students_list")) return null;
		$student_alias = $q->getTableAlias("Student");
		if ($student_alias == null) {
			$student_alias = $q->generateTableAlias();
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