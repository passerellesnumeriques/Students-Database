<?php
/**
 * The component students makes a link between people (the people being students) and curriculum (which batch, which specialization).
 * It keeps also few simple additional information for students like exclusion.
 * All other information related to students are handled by their respective components: students_groups for the assignements to classes and groups, transcripts for the grades, discipline, health, finance...
 */
class students extends Component {
	
	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "PeopleTypePlugin":
				require_once("StudentPeopleTypePlugin.inc");
				return array(new StudentPeopleTypePlugin());
			case "NewsPlugin":
				require_once("StudentsNewsPlugin.inc");
				return array(new StudentsNewsPlugin());
			case "CustomCalendarPlugin":
				require_once("StudentsBirthdaysCalendar.inc");
				return array(new StudentsBirthdaysCalendar());
		}
		return array();
	}
	
	/**
	 * Assign a student to a specialization
	 * @param integer $student_people_id student
	 * @param integer $specialization_id specialization
	 * @return boolean true on success
	 */
	public function assignStudentToSpecialization($student_people_id, $specialization_id) {
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
	
	/**
	 * Get a SELECT for the students of the given batches
	 * @param integer[]|null $batches_ids list of batches, or null for all
	 * @return SQLQuery the SELECT
	 */
	public function getStudentsQueryForBatches($batches_ids = null) {
		$q = SQLQuery::create()->select("Student");
		if ($batches_ids <> null) $q->whereIn("Student","batch",$batches_ids);
		return $q;
	}
	
	/**
	 * Get a SELECT to retrieve students for the given period
	 * @param integer $period_id period
	 * @param boolean $include_people_info if true, a join to the table People will be added
	 * @param boolean $include_excluded if true excluded students will be retrieved
	 * @param integer|boolean $restrict_specialization if false no filter on specialization is made, else only students of the given specialization will be selected
	 * @param boolean $assigned_to_groups if true only students already assigned to at least one group for the given period are returned
	 * @return SQLQuery the SELECT
	 */
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
	
	/**
	 * Make a join to the Student table
	 * @param SQLQuery $q the SELECT
	 * @param string $from_table the table containing a foreign key to People
	 * @param string $from_people_key foreign key to the table People to use for the join
	 */
	public function joinStudent(&$q, $from_table, $from_people_key) {
		$q->join($from_table, "Student", array($from_people_key=>"people"));
		$q->field("Student","people","people");
		$q->field("Student","specialization","specialization");
		$q->field("Student","exclusion_date","exclusion_date");
		$q->field("Student","exclusion_reason","exclusion_reason");
		$q->field("Student","university_id","university_id");
	}
	
	/**
	 * Get the batch the given student belongs to
	 * @param integer $people_id student
	 * @return integer batch id
	 */
	public function getStudentBatchId($people_id) {
		return SQLQuery::create()
			->select("Student")
			->whereValue("Student","people",$people_id)
			->field("Student","batch")
			->executeSingleValue();
	}
	
	/**
	 * Add a WHERE condition to filter students excluded before the given date
	 * @param SQLQuery $q the SELECT
	 * @param string $sql_date date
	 */
	public function removeExcluded(&$q, $sql_date) {
		$table_alias = $q->getTableAlias("Student");
		if ($table_alias == null) {
			$people_alias = $q->getTableAlias("People");
			$q->join($people_alias,"Student",array("id"=>"people"));
			$table_alias = "Student";
		}
		$q->where("(`$table_alias`.`exclusion_date` IS NULL OR `$table_alias`.`exclusion_date` > '$sql_date')");
	}
	
	/**
	 * Retrieve the row of the given student from the Student table
	 * @param integer $people_id student
	 * @return array the row
	 */
	public function getStudent($people_id) {
		return SQLQuery::create()
			->select("Student")
			->whereValue("Student","people",$people_id)
			->executeSingleRow();
	}
	
	/**
	 * Retrieve the rows of the given students from the Student table
	 * @param integer[] $people_ids students
	 * @param boolean $include_people_info if true, a join to the table People will be added
	 * @return array rows from the database
	 */
	public function getStudents($people_ids, $include_people_info = false) {
		$q = SQLQuery::create()->select("Student")->whereIn("Student","people",$people_ids);
		if ($include_people_info) {
			$q->fieldsOfTable("Student");
			PNApplication::$instance->people->joinPeople($q, "Student", "people");
		}
		return $q->execute();
	}
	
	/** Remove the given peoples as student (remove type 'student' to the people, if they only have this type, they will be definitely removed from the database
	 * @param integer[] $peoples_ids students
	 * @param boolean $bypass_security avoid security checks
	 */
	public function removeStudents($peoples_ids, $bypass_security = false) {
		PNApplication::$instance->people->removePeoplesType($peoples_ids, "student", $bypass_security);
	}
	/**
	 * Make the given people as a student of the given batch
	 * @param integer $people_id people to become a student
	 * @param integer $batch_id batch to assign
	 * @param boolean $bypass_security avoid security checks
	 */
	public function makeAsStudent($people_id, $batch_id, $bypass_security = false) {
		PNApplication::$instance->people->addPeopleType($people_id, "student", $bypass_security);
		SQLQuery::create()->bypassSecurity($bypass_security)->insert("Student", array("people"=>$people_id,"batch"=>$batch_id));
	}
}
?>