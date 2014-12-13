<?php

class students extends Component {
	
	public function getPluginImplementations() {
		require_once("StudentsNewsPlugin.inc");
		require_once("StudentPeopleTypePlugin.inc");
		require_once("StudentsBirthdaysCalendar.inc");
		return array(
			new StudentPeopleTypePlugin(),
			new StudentsNewsPlugin(),
			new StudentsBirthdaysCalendar()
		);
	}
	
	public function assign_student_to_specialization($student_people_id, $specialization_id) {
		if ($specialization_id <> null) {
			// check this specialization is available for the batch of this student
			$possible_specializations = SQLQuery::create()
#DEV
				->noWarning() // TODO
#END
				->select("Student")
				->whereValue("Student", "people", $student_people_id)
				->join("Student", "BatchPeriod", array("batch"=>"batch"))
				->join("BatchPeriod", "BatchPeriodSpecialization", array("id"=>"period"))
				->field("BatchPeriodSpecialization", "specialization", "specialization")
				->executeSingleField();
			if (!in_array($specialization_id, $possible_specializations)) {
				PNApplication::error("The student does not exist or the specialization is not available for this batch of students");
				return false;
			}
			// check the student is not already assigned to a group with a different specialization
			$student_spe = PNApplication::$instance->students_groups->getStudentGroupSpecialization($student_people_id);
			if ($student_spe <> null) {
				PNApplication::error("This student is already assigned to a group with another specialization");
				return false;
			}
		} else {
			// check the student exists
			$student = SQLQuery::create()
				->select("Student")
				->whereValue("Student", "people", $student_people_id)
				->executeSingleRow();
			if ($student == null) {
				PNApplication::error("The student does not exist");
				return false;
			}
			// check the student is not already assigned to a class with a specialization
			$student_spe = PNApplication::$instance->students_groups->getStudentGroupSpecialization($student_people_id);
			if ($student_spe <> null) {
				PNApplication::error("This student is already assigned to a group with a specialization");
				return false;
			}
		}
		SQLQuery::create()->updateByKey("Student", $student_people_id, array("specialization"=>$specialization_id));
		return true;
	}
	
	public function getStudentsQueryForBatches($batches_ids = null) {
		$q = SQLQuery::create()->select("Student");
		if ($batches_ids <> null) $q->whereIn("Student","batch",$batches_ids);
		return $q;
	}
	
	public function getStudentsQueryForBatchPeriod($period_id, $include_people_info = true, $include_excluded = false, $restrict_specialization = false, $assigned_to_groups = true) {
		$period = PNApplication::$instance->curriculum->getAcademicPeriodAndBatchPeriod($period_id);
		if (!$assigned_to_groups) {
			$q = SQLQuery::create()
			->select("Student")
			->whereValue("Student", "batch", $period["batch"])
			;
		} else {
			$groups = PNApplication::$instance->students_groups->getGroups(null, $period["id"], $restrict_specialization);
			$groups_ids = array();
			foreach ($groups as $g) array_push($groups_ids, $g["id"]);
			
			if (count($groups_ids) == 0)
				$q = SQLQuery::create()
					->select("Student")
					->whereValue("Student", "batch", $period["batch"])
					;
			else {
				$q = PNApplication::$instance->students_groups->getStudentsQueryForGroups($groups_ids, true);
				$q->join("StudentGroup","Student",array("people"=>"people"));
			}
		}
		$q->field("Student","people","people");
		$q->field("Student","specialization","specialization");
		$q->field("Student","exclusion_date","exclusion_date");
		$q->field("Student","exclusion_reason","exclusion_reason");
		$q->field("Student","university_id","university_id");
		if ($restrict_specialization !== false) {
			if ($restrict_specialization === null)
				$q->whereNull("Student", "specialization");
			else
				$q->whereValue("Student", "Specialization", $restrict_specialization);
		}
		if (!$include_excluded)
			$q->where("(Student.exclusion_date IS NULL OR Student.exclusion_date > '".$period["academic_period_start"]."')");
		if ($include_people_info) {
			PNApplication::$instance->people->joinPeople($q, "Student", "people");
		}
		return $q;
	}
	
	public function joinStudent(&$q, $from_table, $from_people_key) {
		$q->join($from_table, "Student", array($from_people_key=>"people"));
		$q->field("Student","people","people");
		$q->field("Student","specialization","specialization");
		$q->field("Student","exclusion_date","exclusion_date");
		$q->field("Student","exclusion_reason","exclusion_reason");
		$q->field("Student","university_id","university_id");
	}
	
	public function removeExcluded(&$q, $sql_date) {
		$table_alias = $q->getTableAlias("Student");
		if ($table_alias == null) {
			$people_alias = $q->getTableAlias("People");
			$q->join($people_alias,"Student",array("id"=>"people"));
			$table_alias = "Student";
		}
		$q->where("(`$table_alias`.`exclusion_date` IS NULL OR `$table_alias`.`exclusion_date` > '$sql_date')");
	}
	
	public function getStudent($people_id) {
		return SQLQuery::create()
			->select("Student")
			->whereValue("Student","people",$people_id)
			->executeSingleRow();
	}
	
	public function getStudents($people_ids, $include_people_info = false) {
		$q = SQLQuery::create()->select("Student")->whereIn("Student","people",$people_ids);
		if ($include_people_info) {
			$q->fieldsOfTable("Student");
			PNApplication::$instance->people->joinPeople($q, "Student", "people");
		}
		return $q->execute();
	}
	
	public function removeStudents($peoples_ids, $bypass_security = false) {
		PNApplication::$instance->people->removePeoplesType($peoples_ids, "student", $bypass_security);
	}
	public function makeAsStudent($people_id, $batch_id, $bypass_security = false) {
		PNApplication::$instance->people->addPeopleType($people_id, "student", $bypass_security);
		SQLQuery::create()->bypassSecurity($bypass_security)->insert("Student", array("people"=>$people_id,"batch"=>$batch_id));
	}
}
?>