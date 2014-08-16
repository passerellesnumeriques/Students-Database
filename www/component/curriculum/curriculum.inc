<?php
/**
 * Handles curriculum information: students batches, academic periods, specializations, classes, and subjects. However, nothing is linked to a student, this is done by the student component 
 */ 
class curriculum extends Component {
	
	public function getPluginImplementations() {
		require_once("TeacherPeopleTypePlugin.inc");
		require_once("CurriculumStudentsPlugin.inc");
		return array(
			new TeacherPeopleTypePlugin(),
			new CurriculumStudentsPlugin()
		);
	}
	
	/** Returns all AcademicYear from database
	 * @return array rows in the AcademicYear table
	 */
	public function getAcademicYears() {
		return SQLQuery::create()->select("AcademicYear")->orderBy("AcademicYear","year")->execute();
	}
	
	/** Returns all AcademicPeriod from database
	 * @return array rows in the AcademicPeriod table
	 */
	public function getAcademicPeriods() {
		return SQLQuery::create()->select("AcademicPeriod")->orderBy("AcademicPeriod", "start")->execute();
	}
	
	/** Returns all AcademicPeriod from database, together with the name of the AcademicYear they belong to
	 * @return array rows in the AcademicPeriod table
	 */
	public function getAcademicPeriodsWithYearName() {
		return SQLQuery::create()
			->select("AcademicPeriod")
			->join("AcademicPeriod","AcademicYear",array("year"=>"id"))
			->fieldsOfTable("AcademicPeriod")
			->field("AcademicYear","name","year_name")
			->orderBy("AcademicPeriod", "start")
			->execute();
	}
	
	/** Retrieve the AcademicPeriod corresponding to the given BatchPeriod
	 * @param integer $batch_period_id BatchPeriod ID
	 * @return array the row from the AcademicPeriod table
	 */
	public function getAcademicPeriodFromBatchPeriod($batch_period_id) {
		return SQLQuery::create()
			->select("BatchPeriod")
			->whereValue("BatchPeriod","id",$batch_period_id)
			->join("BatchPeriod", "AcademicPeriod", array("academic_period"=>"id"))
			->fieldsOfTable("AcademicPeriod")
			->executeSingleRow();
	}
	/** Retrieve the AcademicPeriod joined with the given BatchPeriod
	 * @param integer $batch_period_id BatchPeriod ID
	 * @return array the row resulting from the join between the AcademicPeriod and BatchPeriod tables
	 */
	public function getAcademicPeriodAndBatchPeriod($batch_period_id) {
		return SQLQuery::create()
			->select("BatchPeriod")
			->whereValue("BatchPeriod","id",$batch_period_id)
			->join("BatchPeriod", "AcademicPeriod", array("academic_period"=>"id"))
			->fieldsOfTable("BatchPeriod")
			->field("AcademicPeriod", "id", "academic_period_id")
			->field("AcademicPeriod", "name", "academic_period_name")
			->field("AcademicPeriod", "start", "academic_period_start")
			->field("AcademicPeriod", "end", "academic_period_end")
			->field("AcademicPeriod", "weeks", "academic_period_weeks")
			->field("AcademicPeriod", "weeks_break", "academic_period_weeks_break")
			->executeSingleRow();
	}
	/** Retrieve the given period
	 * @param integer $period_id the requested period
	 * @return array the row from the database
	 */
	public function getBatchPeriod($period_id, $join_academic_period = false) {
		$q = SQLQuery::create()->select("BatchPeriod")->where("id",$period_id);
		if ($join_academic_period) {
			$q->join("BatchPeriod","AcademicPeriod",array("academic_period"=>"id"));
			$q->fieldsOfTable("BatchPeriod");
			$q->field("AcademicPeriod", "id", "academic_period_id");
			$q->field("AcademicPeriod","name","academic_period_name");
			$q->field("AcademicPeriod","start","academic_period_start");
			$q->field("AcademicPeriod","end","academic_period_end");
			$q->field("AcademicPeriod","year","academic_period_year");
			$q->field("AcademicPeriod","weeks","academic_period_weeks");
			$q->field("AcademicPeriod","weeks_break","academic_period_weeks_break");
		}
		return $q->executeSingleRow();
	}
	/** Retrieve periods for a batch
	 * @param integer $batch_id the requested batch
	 * @return array the list of periods from the database
	 */
	public function getBatchPeriodsWithAcademicPeriods($batch_id) {
		return SQLQuery::create()
		->select("BatchPeriod")
		->whereValue("BatchPeriod", "batch", $batch_id)
		->join("BatchPeriod","AcademicPeriod",array("academic_period"=>"id"))
		->fieldsOfTable("BatchPeriod")
		->field("AcademicPeriod", "id", "academic_period_id")
		->field("AcademicPeriod", "name", "academic_period_name")
		->field("AcademicPeriod", "start", "academic_period_start")
		->field("AcademicPeriod", "end", "academic_period_end")
		->field("AcademicPeriod", "weeks", "academic_period_weeks")
		->field("AcademicPeriod", "weeks_break", "academic_period_weeks_break")
		->execute();
	}
	
	/** Retrieve the list of existing batches of students
	 * @param integer[]|null list of StudentBatch ID to retrieve, or null for all batches
	 * @return array the result from the database
	 */
	public function getBatches($ids = null) {
		$q = SQLQuery::create()->select("StudentBatch")->orderBy("StudentBatch", "start_date", true);
		if ($ids <> null) $q->whereIn("StudentBatch", "id", $ids);
		return $q->execute();
	}
	/** Retrieve the given batch
	 * @param integer $batch_id the requested batch
	 * @return array the row from the database
	 */
	public function getBatch($batch_id) {
		return SQLQuery::create()->select("StudentBatch")->whereValue("StudentBatch", "id", $batch_id)->executeSingleRow();
	}
	
	/** Retrieve batches having an end_date greater than today
	 * @return array list of rows from StudentBatch table
	 */
	public function getCurrentBatches() {
		$q = SQLQuery::create()->select("StudentBatch")->orderBy("StudentBatch", "start_date", true);
		$q->where("end_date >= CURRENT_DATE()");
		return $q->execute();
	}
	/** Retrieve batches having an end_date less than today
	 * @return array list of rows from StudentBatch table
	 */
	public function getAlumniBatches() {
		$q = SQLQuery::create()->select("StudentBatch")->orderBy("StudentBatch", "start_date", true);
		$q->where("end_date < CURRENT_DATE()");
		return $q->execute();
	}
	
	/**
	 * Join the batch table to an SQLQuery
	 * @param SQLQuery $q the query where to add the join
	 * @param string $from_table name of the table to use for the join
	 * @param string $from_table_foreign_key name of the foreign key to use for the join
	 */
	public function joinBatch(&$q, $from_table, $from_table_foreign_key) {
		$q->join($from_table, "StudentBatch", array($from_table_foreign_key=>"id"));
	}
	
	/** Retrieve the list of existing specializations
	 * @return array the result from the database
	 */
	public function getSpecializations() {
		return SQLQuery::create()->select("Specialization")->execute();
	}
	/** Retrieve the given specialization
	 * @param integer $id the requested specialization
	 * @return array the row from the database
	 */
	public function getSpecialization($id) {
		return SQLQuery::create()->select("Specialization")->whereValue("Specialization", "id", $id)->executeSingleRow();
	}
	
	/**
	 * Retrieve the list of specializations available for the given batch (which is present in at least one period)
	 * @param integer $batch_id the requested batch
	 * @return array the list of specialization id
	 */
	public function getBatchSpecializations($batch_id) {
		return SQLQuery::create()
			->select("BatchPeriod")
			->whereValue("BatchPeriod", "batch", $batch_id)
			->join("BatchPeriod", "BatchPeriodSpecialization", array("id"=>"period"))
			->whereNotNull("BatchPeriodSpecialization", "specialization")
			->groupBy("BatchPeriodSpecialization", "specialization")
			->field("BatchPeriodSpecialization", "specialization", "specialization")
			->executeSingleField();
	}
	/**
	 * Retrieve the list of specializations available for the given batch (which is present in at least one period)
	 * @param integer $batch_id the requested batch
	 * @return array the result from the database: id and name
	 */
	public function getBatchSpecializationsWithName($batch_id) {
		return SQLQuery::create()
			->select("BatchPeriod")
			->whereValue("BatchPeriod", "batch", $batch_id)
			->join("BatchPeriod", "BatchPeriodSpecialization", array("id"=>"period"))
			->whereNotNull("BatchPeriodSpecialization", "specialization")
			->groupBy("BatchPeriodSpecialization", "specialization")
			->field("BatchPeriodSpecialization", "specialization", "id")
			->join("BatchPeriodSpecialization", "Specialization", array("specialization"=>"id"))
			->field("Specialization", "name", "name")
			->execute();
	}
	
	/**
	 * Retrieve the list of specializations for a given period
	 * @param integer $period_id the requested period
	 * @return array the list of specializations' id
	 */
	public function getBatchPeriodSpecializations($period_id) {
		return SQLQuery::create()
			->select("BatchPeriodSpecialization")
			->whereValue("BatchPeriodSpecialization","period", $period_id)
			->field("BatchPeriodSpecialization", "specialization")
			->executeSingleField();
	}
	/**
	 * Retrieve the list of specializations for a given period
	 * @param integer $period_id the requested period
	 * @return array the list of specializations from database: id and name
	 */
	public function getBatchPeriodSpecializationsWithName($period_id) {
		return SQLQuery::create()
			->select("BatchPeriodSpecialization")
			->whereValue("BatchPeriodSpecialization","period", $period_id)
			->join("BatchPeriodSpecialization", "Specialization", array("specialization"=>"id"))
			->field("BatchPeriodSpecialization", "specialization", "id")
			->field("Specialization", "name", "name")
			->execute();
	}
	
	/** Retrieve the specializations for the given periods
	 * @param array $periods_ids list of requested periods
	 * @return array rows from database associating a period id to a specialization id
	 */
	public function getBatchPeriodsSpecializations($periods_ids) {
		if (count($periods_ids) == 0) return array();
		return SQLQuery::create()->select("BatchPeriodSpecialization")->whereIn("BatchPeriodSpecialization","period",$periods_ids)->execute();
	}
	/** Retrieve the specializations for the given periods
	 * @param array $periods_ids list of requested periods
	 * @return array rows from database associating a period id to a specialization id
	 */
	public function getBatchPeriodsSpecializationsWithName($periods_ids) {
		if (count($periods_ids) == 0) return array();
		return SQLQuery::create()
			->select("BatchPeriodSpecialization")
			->whereIn("BatchPeriodSpecialization","period",$periods_ids)
			->join("BatchPeriodSpecialization", "Specialization", array("specialization"=>"id"))
			->field("BatchPeriodSpecialization", "period", "period")
			->field("BatchPeriodSpecialization", "specialization", "id")
			->field("Specialization", "name", "name")
			->execute();
	}
	
	/** Retrieve periods for a batch
	 * @param integer $batch_id the requested batch
	 * @return array the list of periods from the database
	 */
	public function getBatchPeriods($batch_id) {
		return SQLQuery::create()->select("BatchPeriod")->whereValue("BatchPeriod", "batch", $batch_id)->execute();
	}
	
	/** Return all BatchPeriod rows corresponding to the given academic periods
	 * @param array $academic_periods_ids list of AcademicPeriod ID
	 * @return array rows from the BatchPeriod table
	 */
	public function getBatchPeriodsForAcademicPeriods($academic_periods_ids) {
		return SQLQuery::create()
			->select("BatchPeriod")
			->whereIn("BatchPeriod","academic_period",$academic_periods_ids)
			->execute();
	}
	
	/**
	 * Retrieve the class information from database
	 * @param integer $class_id the requested class
	 * @return array the row from the database
	 */
	public function getAcademicClass($class_id) {
		return SQLQuery::create()->select("AcademicClass")->where("id", $class_id)->executeSingleRow();
	}
	/**
	 * Retrieve the classes for a given period
	 * @param integer $period_id the requested period
	 * @param integer|null|false $restrict_specialization specify if we should only get classes for a specific specialization in the period
	 * @return array the result from the database
	 */
	public function getAcademicClassesForPeriod($period_id, $restrict_specialization = false) {
		$q = SQLQuery::create()->select("AcademicClass")->where("period", $period_id);
		if ($restrict_specialization !== false) {
			if ($restrict_specialization === null)
				$q->whereNull("AcademicClass","specialization");
			else
				$q->where("specialization", $restrict_specialization);
		}
		return $q->execute();
	}
	
	/** Retrieve classes
	 * @param integer $batch_id StudentBatch ID
	 * @param integer|null $period_id BatchPeriod ID, or null for any period
	 * @param integer|null|boolean $restrict_specialization Specialization ID, or null to only get classes which are not in a specialization, or false to get all periods
	 * @return array rows from the AcademicClass table
	 */
	public function getAcademicClasses($batch_id, $period_id = null, $restrict_specialization = false) {
		if ($period_id <> null) return $this->getAcademicClassesForPeriod($period_id, $restrict_specialization);
		$periods = $this->getBatchPeriods($batch_id);
		if (count($periods) == 0) return array();
		$ids = array();
		foreach ($periods as $period) array_push($ids, $period["id"]);
		$q = SQLQuery::create()->select("AcademicClass")->whereIn("AcademicClass", "period", $ids);
		if ($restrict_specialization !== false) {
			if ($restrict_specialization === null)
				$q->whereNull("AcademicClass","specialization");
			else
				$q->where("specialization", $restrict_specialization);
		}
		return $q->execute();
	}
	
	/**
	 * Join the academic class table to an SQLQuery
	 * @param SQLQuery $q the query where to add the join
	 * @param string $from_table name of the table to use for the join
	 * @param string $from_table_foreign_key name of the foreign key to use for the join
	 * @param integer|null $filter_period_id if specified, only classes in the given period will be joined
	 */
	public function joinAcademicClass(&$q, $from_table, $from_table_foreign_key, $filter_period_id = null) {
		$q->join($from_table, "AcademicClass", array($from_table_foreign_key=>"id"));
		if ($filter_period_id <> null)
			$q->whereValue("AcademicClass", "period", $filter_period_id);
	}
	
	/** Retrieve the list of subject categories
	 * @return array the result from the database
	 */
	public function getSubjectCategories() {
		return SQLQuery::create()->select("CurriculumSubjectCategory")->execute();
	}
	/**
	 * Retrieve a list of subjects for the given criteria
	 * @param integer $batch_id restrict the result to subjects for this batch
	 * @param integer|null $period_id if specified, only subjects in this period will be returned
	 * @param integer|null $spe_id if specified, only subjects in this specialization will be returned
	 * @param integer|null $category_id if specified, only subjects in this category will be returned
	 * @return array the result from the database
	 */
	public function getSubjects($batch_id, $period_id = null, $spe_id = null, $category_id = null) {
		$q = SQLQuery::create()->select("CurriculumSubject");
		if ($period_id <> null) $q->whereValue("CurriculumSubject", "period", $period_id);
		else {
			$periods = $this->getBatchPeriods($batch_id);
			if (count($periods) == 0) return array();
			$ids = array();
			foreach ($periods as $period) array_push($ids, $period["id"]);
			$q->whereIn("CurriculumSubject", "period", $ids);
		}
		if ($spe_id <> null) $q->whereValue("CurriculumSubject", "specialization", $spe_id);
		if ($category_id <> null) $q->whereValue("CurriculumSubject", "category", $category_id);
		$q->orderBy("CurriculumSubject","code");
		return $q->execute();
	}
	
	public function getSubjectsById($subjects_ids) {
		return SQLQuery::create()->select("CurriculumSubject")->whereIn("CurriculumSubject", "id", $subjects_ids)->execute();
	}
	
	public function getSubjectQuery($subject_id) {
		return SQLQuery::create()->select("CurriculumSubject")->whereValue("CurriculumSubject", "id", $subject_id);
	}
	public function getSubjectsQuery($subjects_ids) {
		return SQLQuery::create()->select("CurriculumSubject")->whereIn("CurriculumSubject", "id", $subjects_ids);
	}
	public function joinSubjects(&$q, $join_table, $join_key) {
		$q->join($join_table, "CurriculumSubject", array($join_key=>"id"));
	}
	
	/** Retrieve teacher assignments for the given subjects
	 * @param integer[] $subjects_ids list of CurriculumSubject ID 
	 * @return array rows from TeacherAssignment table
	 */
	public function getTeachersAssigned($subjects_ids) {
		if (count($subjects_ids) == 0) return array();
		$q = SQLQuery::create()->select("TeacherAssignment")->whereIn("TeacherAssignment", "subject", $subjects_ids);
		return $q->execute();
	}
	/** Retrieve teacher assignments for the given classes
	 * @param integer[] $classes_ids list of AcademicClass ID 
	 * @return array rows from TeacherAssignment table
	 */
	public function getTeachersAssignedForClasses($classes_ids) {
		if (count($subjects_ids) == 0) return array();
		$q = SQLQuery::create()->select("TeacherAssignment")->whereIn("TeacherAssignment", "class", $classes_ids);
		return $q->execute();
	}
	/** Retrieve teacher assignments for the given periods
	 * @param integer[] $periods_ids list of BatchPeriod ID
	 * @param boolean $join_subject true to join the CurriculumSubject table to every assignment 
	 * @return array rows from joined tables TeacherAssignment, BatchPeriod and AcademicClass (and optionally CurriculumSubject)
	 */
	public function getTeacherAssignedForBatchPeriods($periods_ids, $join_subject=false) {
		$q = SQLQuery::create()
			->select("BatchPeriod")
			->whereIn("BatchPeriod","id",$periods_ids)
			->join("BatchPeriod","AcademicClass",array("id"=>"period"))
			->join("AcademicClass","TeacherAssignment",array("id"=>"class"))
			->fieldsOfTable("TeacherAssignment")
			->field("BatchPeriod","id","batch_period_id")
			->field("AcademicClass","specialization","specialization")
			;
		if ($join_subject) {
			$q->join("TeacherAssignment","CurriculumSubject",array("subject"=>"id"));
			$q->field("CurriculumSubject", "hours", "hours");
			$q->field("CurriculumSubject", "hours_type", "hours_type");
		}
		return $q->execute();
	}
	
}
?>