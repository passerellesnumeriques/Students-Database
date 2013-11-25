<?php
require_once("component/people/PeoplePlugin.inc"); 
class students extends Component implements PeoplePlugin {
	
	public function assign_student_to_specialization($student_people_id, $specialization_id) {
		if ($specialization_id <> null) {
			// check this specialization is available for the batch of this student
			$possible_specializations = SQLQuery::create()
				->select("Student")
				->where_value("Student", "people", $student_people_id)
				->join("Student", "AcademicPeriod", array("batch"=>"batch"))
				->join("AcademicPeriod", "AcademicPeriodSpecialization", array("id"=>"period"))
				->field("AcademicPeriodSpecialization", "specialization", "specialization")
				->execute_single_field();
			if (!in_array($specialization_id, $possible_specializations)) {
				PNApplication::error("The student does not exist or the specialization is not available for this batch of students");
				return false;
			}
			// check the student is not already assigned to a class with a different specialization
			$different_classes = SQLQuery::create()
				->select("Student")
				->where_value("Student", "people", $student_people_id)
				->join("Student", "StudentClass", array("people"=>"people"))
				->join("StudentClass", "AcademicClass", array("class"=>"id"))
				->where("`AcademicClass`.`specialization` IS NOT NULL AND `AcademicClass`.`specialization`!='".SQLQuery::escape($specialization_id)."'")
				->execute();
			if (count($different_classes) > 0) {
				PNApplication::error("This student is already assigned to a class with another specialization");
				return false;
			}
		} else {
			// check the student exists
			$student = SQLQuery::create()
				->select("Student")
				->where_value("Student", "people", $student_people_id)
				->execute_single_row();
			if ($student == null) {
				PNApplication::error("The student does not exist");
				return false;
			}
			// check the student is not already assigned to a class with a specialization
			$different_classes = SQLQuery::create()
				->select("Student")
				->where_value("Student", "people", $student_people_id)
				->join("Student", "StudentClass", array("people"=>"people"))
				->join("StudentClass", "AcademicClass", array("class"=>"id"))
				->where("`AcademicClass`.`specialization` IS NOT NULL")
				->execute();
			if (count($different_classes) > 0) {
				PNApplication::error("This student is already assigned to a class with a specialization");
				return false;
			}
		}
		SQLQuery::create()->update_by_key("Student", $student_people_id, array("specialization"=>$specialization_id));
		return true;
	}
	
	public function assign_student_to_class($student_people_id, $class_id, $period_id) {
		// check the class exists
		if ($class_id <> null) {
			$class = SQLQuery::create()->select("AcademicClass")->where("id", $class_id)->execute_single_row();
			if ($class == null) { PNApplication::error("Invalid class id"); return false; }
			if ($class["period"] <> $period_id) { PNApplication::error("The class is not in the given academic period"); return false; }
		}
		// check the student exists
		$student = SQLQuery::create()->select("Student")->where("people", $student_people_id)->execute_single_row();
		if ($student == null) { PNApplication::error("Invalid student id"); return false; }
		// check the class specialization is compatible to student specialization
		if ($class_id <> null) {
			if ($class["specialization"] <> $student["specialization"]) { PNApplication::error("Student's specialization does not match with class' specialization"); return false; }
		}
		// get the assignment of this student for this academic period
		$assigned = SQLQuery::create()
			->select("StudentClass")
			->where_value("StudentClass", "people", $student_people_id)
			->join("StudentClass", "AcademicClass", array("class"=>"id"))
			->where_value("AcademicClass", "period", $period_id)
			->field("StudentClass", "class", "class")
			->execute();
		// do it
		foreach ($assigned as $a)
			SQLQuery::create()->remove_key("StudentClass", array("people"=>$student_people_id,"class"=>$assigned["class"]));
		if ($class_id <> null) {
			SQLQuery::create()->insert("StudentClass", array("people"=>$student_people_id,"class"=>$class_id));
		}
		return true;
	}
	
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
		if ($people["batch"] == null) return array(); // not a student
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

	public function getPeopleImportDataDisplay() { return array(); }
	public function importPeopleData($input, $people_id) { return true; }
	
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