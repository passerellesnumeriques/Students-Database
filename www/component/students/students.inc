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
			// check the student is not already assigned to a class with a different specialization
			$q = SQLQuery::create()
				->select("Student")
				->whereValue("Student", "people", $student_people_id)
				->join("Student", "StudentClass", array("people"=>"people"));
			PNApplication::$instance->curriculum->joinAcademicClass($q, "StudentClass", "class");
			$q->where("`AcademicClass`.`specialization` IS NOT NULL AND `AcademicClass`.`specialization`!='".SQLQuery::escape($specialization_id)."'");
			$different_classes = $q->execute();
			if (count($different_classes) > 0) {
				PNApplication::error("This student is already assigned to a class with another specialization");
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
			$q = SQLQuery::create()
				->select("Student")
				->whereValue("Student", "people", $student_people_id)
				->join("Student", "StudentClass", array("people"=>"people"));
			PNApplication::$instance->curriculum->joinAcademicClass($q, "StudentClass", "class");
			$q->where("`AcademicClass`.`specialization` IS NOT NULL");
			$different_classes = $q->execute();
			if (count($different_classes) > 0) {
				PNApplication::error("This student is already assigned to a class with a specialization");
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
	
	public function getStudentsQueryForBatchPeriod($period_id, $include_people_info = true, $include_excluded = false, $restrict_specialization = false, $assigned_to_classes = true) {
		$period = PNApplication::$instance->curriculum->getAcademicPeriodAndBatchPeriod($period_id);
		if (!$assigned_to_classes) {
			$q = SQLQuery::create()
			->select("Student")
			->whereValue("Student", "batch", $period["batch"])
			;
		} else {
			$classes = PNApplication::$instance->curriculum->getAcademicClassesForPeriod($period["id"]);
			$classes_ids = array();
			foreach ($classes as $c) array_push($classes_ids, $c["id"]);
			
			if (count($classes_ids) == 0)
				$q = SQLQuery::create()
					->select("Student")
					->whereValue("Student", "batch", $period["batch"])
					;
			else
				$q = SQLQuery::create()
					->select("StudentClass")
					->whereIn("StudentClass","class",$classes_ids)
					->join("StudentClass","Student",array("people"=>"people"))
					;
		}
		$q->field("Student","people","people");
		$q->field("Student","specialization","specialization");
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
			require_once("component/people/PeopleJSON.inc");
			PeopleJSON::PeopleSQL($q);
		}
		return $q;
	}
	
	public function getStudentsQueryForClass($class_id, $include_people_info = true) {
		$q = SQLQuery::create()
			->select("StudentClass")
			->whereValue("StudentClass", "class", $class_id)
			;
		if ($include_people_info) {
			PNApplication::$instance->people->joinPeople($q, "StudentClass", "people");
			require_once("component/people/PeopleJSON.inc");
			PeopleJSON::PeopleSQL($q);
		}
		return $q;
	}
	
	public function getStudentsForClasses($classes_ids, $include_people_info = true) {
		$q = SQLQuery::create()
			->select("StudentClass")
			->whereIn("StudentClass", "class", $classes_ids)
			;
		if ($include_people_info) {
			PNApplication::$instance->people->joinPeople($q, "StudentClass", "people");
			require_once("component/people/PeopleJSON.inc");
			PeopleJSON::PeopleSQL($q);
		}
		return $q->execute();
	}
	
	public function getStudentClasses($people_id) {
		return SQLQuery::create()
			->select("StudentClass")
			->whereValue("StudentClass", "people", $people_id)
			->execute();
	}
	public function getStudent($people_id) {
		return SQLQuery::create()
			->select("Student")
			->whereValue("Student","people",$people_id)
			->executeSingleRow();
	}
}
?>