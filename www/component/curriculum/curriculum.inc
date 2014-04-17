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
	
	public function getAcademicYears() {
		return SQLQuery::create()->select("AcademicYear")->orderBy("AcademicYear","year")->execute();
	}
	
	public function getAcademicPeriods() {
		return SQLQuery::create()->select("AcademicPeriod")->orderBy("AcademicPeriod", "start")->execute();
	}
	
	public function getAcademicPeriodFromBatchPeriod($batch_period_id) {
		return SQLQuery::create()
			->select("BatchPeriod")
			->whereValue("BatchPeriod","id",$batch_period_id)
			->join("BatchPeriod", "AcademicPeriod", array("academic_period"=>"id"))
			->fieldsOfTable("AcademicPeriod")
			->executeSingleRow();
	}
	public function getAcademicPeriodAndBatchPeriod($batch_period_id) {
		return SQLQuery::create()
			->select("BatchPeriod")
			->whereValue("BatchPeriod","id",$batch_period_id)
			->join("BatchPeriod", "AcademicPeriod", array("academic_period"=>"id"))
			->executeSingleRow();
	}
	
	/** Retrieve the list of existing batches of students
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
	
	public function getCurrentBatches() {
		$q = SQLQuery::create()->select("StudentBatch")->orderBy("StudentBatch", "start_date", true);
		$q->where("end_date >= CURRENT_DATE()");
		return $q->execute();
	}
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
			->join("BatchPeriod", "AcademicPeriodSpecialization", array("id"=>"period"))
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
		return SQLQuery::create()->select("BatchPeriodSpecialization")->whereIn("BatchPeriodSpecialization","period",$periods_ids)->execute();
	}
	
	/** Retrieve the given period
	 * @param integer $period_id the requested period
	 * @return array the row from the database
	 */
	public function getBatchPeriod($period_id) {
		return SQLQuery::create()->select("BatchPeriod")->where("id",$period_id)->executeSingleRow();
	}
	/** Retrieve periods for a batch
	 * @param integer $batch_id the requested batch
	 * @return array the list of periods from the database
	 */
	public function getBatchPeriods($batch_id) {
		return SQLQuery::create()->select("BatchPeriod")->whereValue("BatchPeriod", "batch", $batch_id)->execute();
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
			$q->where("specialization", $restrict_specialization);
		}
		return $q->execute();
	}
	
	public function getAcademicClasses($batch_id, $period_id = null, $restrict_specialization = false) {
		if ($period_id <> null) return $this->getAcademicClassesForPeriod($period_id, $restrict_specialization);
		$periods = $this->getBatchPeriods($batch_id);
		if (count($periods) == 0) return array();
		$ids = array();
		foreach ($periods as $period) array_push($ids, $period["id"]);
		$q = SQLQuery::create()->select("AcademicClass")->whereIn("AcademicClass", "period", $ids);
		if ($restrict_specialization !== false) {
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
		return $q->execute();
	}
	
	public function getTeachersAssigned($subjects_ids) {
		if (count($subjects_ids) == 0) return array();
		$q = SQLQuery::create()->select("TeacherAssignment")->whereIn("TeacherAssignment", "subject", $subjects_ids);
		return $q->execute();
	}
	
}
?>