<?php
/**
 * Handles curriculum information: students batches, academic periods, specializations, and subjects. However, nothing is linked to a student, this is done by the student component 
 */ 
class curriculum extends Component {
	
	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "StudentsPlugin":
				require_once("CurriculumStudentsPlugin.inc");
				return array(new CurriculumStudentsPlugin());
			case "AttachedDocumentsPlugin":
				require_once("CurriculumDocumentsPlugin.inc");
				return array(new CurriculumAttachedDocumentsPlugin());
		}
		return array();
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
	
	/**
	 * Retrieve an AcademicPeriod from the database
	 * @param integer $academic_period_id the id
	 * @return array the row from the AcademicPeriod table
	 */
	public function getAcademicPeriod($academic_period_id) {
		return SQLQuery::create()->select("AcademicPeriod")->whereValue("AcademicPeriod","id",$academic_period_id)->executeSingleRow();
	}
	
	/**
	 * Retrieve the current (today) academic period
	 * @param boolean $return_next_one_if_no_current if true, and there is no current period, return the next one in the future
	 * @return array|null the row from the AcademicPeriod table, or null if there is no current period, or no next period
	 */
	public function getCurrentAcademicPeriod($return_next_one_if_no_current = false) {
		$now = date("Y-m-d",time());
		$p = SQLQuery::create()
			->select("AcademicPeriod")
			->where("start","<=",$now)
			->where("end",">=",$now)
			->executeSingleRow();
		if ($p <> null) return $p;
		// next one
		$p = SQLQuery::create()
			->select("AcademicPeriod")
			->where("`start` >= '".$now."'")
			->orderBy("AcademicPeriod","start")
			->limit(0, 1)
			->executeSingleRow();
		if ($p <> null) return $p;
		// last one
		return SQLQuery::create()
			->select("AcademicPeriod")
			->orderBy("AcademicPeriod","start", false)
			->limit(0, 1)
			->executeSingleRow();
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
	
	/**
	 * Retrieve the academic period which is before the given date (start &lt; before_date)
	 * @param string $before_date SQL date
	 * @return array|null the row of the AcademicPeriod table
	 */
	public function getPreviousAcademicPeriod($before_date) {
		return SQLQuery::create()
			->select("AcademicPeriod")
			->where("start","<",$before_date)
			->orderBy("AcademicPeriod", "start", false)
			->limit(0,1)
			->executeSingleRow();
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
	/**
	 * Get the BatchPeriod corresponding to the given academic period, for the given batch
	 * @param integer $batch_id the batch ID
	 * @param integer $academic_period_id the academic period id
	 * @return array|null the row from the BatchPeriod table
	 */
	public function getBatchPeriodFromAcademicPeriod($batch_id, $academic_period_id) {
		return SQLQuery::create()
			->select("BatchPeriod")
			->whereValue("BatchPeriod", "batch", $batch_id)
			->whereValue("BatchPeriod", "academic_period", $academic_period_id)
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
	 * @param boolean $join_academic_period if true, and additional join to AcademicPeriod is done, and all its fields are prefixed by academic_period_
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
	 * @param integer[]|null $ids list of StudentBatch ID to retrieve, or null for all batches
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
	
	/** Retrieve batches having an end_date &gt;= today
	 * @param boolean $exclude_future if true, do not include the batch who didn't start yet
	 * @param boolean $bypass_security avoid security checks
	 * @return array list of rows from StudentBatch table
	 */
	public function getCurrentBatches($exclude_future = false, $bypass_security = false) {
		$q = SQLQuery::create()->bypassSecurity($bypass_security)->select("StudentBatch")->orderBy("StudentBatch", "start_date", true);
		$q->where("end_date >= CURRENT_DATE()");
		if ($exclude_future) $q->where("start_date <= CURRENT_DATE()");
		return $q->execute();
	}
	/** Retrieve batches having an end_date less than today
	 * @param boolean $bypass_security avoid security checks
	 * @return array list of rows from StudentBatch table
	 */
	public function getAlumniBatches($bypass_security = false) {
		$q = SQLQuery::create()->bypassSecurity($bypass_security)->select("StudentBatch")->orderBy("StudentBatch", "start_date", true);
		$q->where("end_date < CURRENT_DATE()");
		return $q->execute();
	}
	/**
	 * Retrieve the future batches (integration date &gt;= today)
	 * @param boolean $bypass_security to avoid security checks
	 * @return array rows of the StudentBatch table
	 */
	public function getFutureBatches($bypass_security = false) {
		$q = SQLQuery::create()->bypassSecurity($bypass_security)->select("StudentBatch")->orderBy("StudentBatch", "start_date", true);
		$q->where("start_date >= CURRENT_DATE()");
		return $q->execute();
	}
	/** Retrieve all rows from the StudentBatch table
	 * @param boolean $bypass_security avoid security checks
	 * @return array rows
	 */
	public function getAllBatches($bypass_security = false) {
		return SQLQuery::create()->bypassSecurity($bypass_security)->select("StudentBatch")->orderBy("StudentBatch", "start_date", true)->execute();
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
	/** Retrieve the name of the given specialization
	 * @param integer $id the requested specialization
	 * @return string the name
	 */
	public function getSpecializationName($id) {
		return SQLQuery::create()->select("Specialization")->whereValue("Specialization", "id", $id)->field("name")->executeSingleValue();
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
	/**
	 * Retrieve rows from the BatchPeriod table
	 * @param integer[] $periods_ids ids
	 * @return array rows
	 */
	public function getBatchPeriodsById($periods_ids, $join_academic_period = false, $order_by_date = false) {
		$q = SQLQuery::create()->select("BatchPeriod")->whereIn("BatchPeriod", "id", $periods_ids);
		if ($order_by_date) $join_academic_period = true;
		if ($join_academic_period) {
			$q->join("BatchPeriod","AcademicPeriod",array("academic_period"=>"id"));
			$q->fieldsOfTable("BatchPeriod");
			$q->field("AcademicPeriod","name","academic_period_name");
			$q->field("AcademicPeriod","start");
			$q->field("AcademicPeriod","end");
		}
		if ($order_by_date !== false) {
			$q->orderBy("AcademicPeriod","start",$order_by_date == "asc");
		}
		return $q->execute();
	}
	
	/**
	 * Join to the BatchPeriod table
	 * @param SQLQuery $q the query to join
	 * @param string $from_table the table containing a foreign key to BatchPeriod
	 * @param string $from_key the foreign key to BatchPeriod
	 */
	public function joinBatchPeriod(&$q, $from_table, $from_key) {
		$q->join($from_table, "BatchPeriod", array($from_key=>"id"));
	}
	
	/** Return all BatchPeriod rows corresponding to the given academic periods
	 * @param array $academic_periods_ids list of AcademicPeriod ID
	 * @param boolean $join_with_batch if true, and additional join to StudentBatch will be added, with the field batch_name
	 * @return array rows from the BatchPeriod table
	 */
	public function getBatchPeriodsForAcademicPeriods($academic_periods_ids, $join_with_batch = false) {
		$q = SQLQuery::create()->select("BatchPeriod")->whereIn("BatchPeriod","academic_period",$academic_periods_ids);
		if ($join_with_batch)
			$q->join("BatchPeriod", "StudentBatch", array("batch"=>"id"))
			  ->orderBy("StudentBatch","start_date")
			  ->fieldsOfTable("BatchPeriod")
			  ->field("StudentBatch","name","batch_name")
			  ;
		return $q->execute();
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
		$q = $this->getSubjectsQueryForBatch($batch_id, $period_id, $spe_id, $category_id);
		return $q->execute();
	}
	/**
	 * Retrieve a list of subjects for the given criteria
	 * @param integer $batch_id restrict the result to subjects for this batch
	 * @param integer|null $period_id if specified, only subjects in this period will be returned
	 * @param integer|null $spe_id if specified, only subjects in this specialization will be returned
	 * @param integer|null $category_id if specified, only subjects in this category will be returned
	 * @return array the result from the database
	 */
	public function getSubjectsQueryForBatch($batch_id, $period_id = null, $spe_id = null, $category_id = null) {
		$q = SQLQuery::create()->select("CurriculumSubject");
		if ($period_id <> null) $q->whereValue("CurriculumSubject", "period", $period_id);
		else {
			$periods = $this->getBatchPeriods($batch_id);
			if (count($periods) == 0) {
				$q->where("0");
				return $q;
			}
			$ids = array();
			foreach ($periods as $period) array_push($ids, $period["id"]);
			$q->whereIn("CurriculumSubject", "period", $ids);
		}
		if ($spe_id <> null) {
			$q->where("(`CurriculumSubject`.`specialization`='$spe_id' OR `CurriculumSubject`.`specialization` IS NULL)");
		}
		if ($category_id <> null) $q->whereValue("CurriculumSubject", "category", $category_id);
		$q->orderBy("CurriculumSubject","code");
		return $q;
	}
	
	/**
	 * Retrieve rows from the CurriculumSubject table
	 * @param integer[] $subjects_ids ids
	 * @return array rows
	 */
	public function getSubjectsById($subjects_ids) {
		return SQLQuery::create()->select("CurriculumSubject")->whereIn("CurriculumSubject", "id", $subjects_ids)->execute();
	}
	
	/** Get an SQLQuery retrieving the given subject
	 * @param integer $subject_id the subject to get
	 * @return SQLQuery the query
	 */
	public function getSubjectQuery($subject_id) {
		return SQLQuery::create()->select("CurriculumSubject")->whereValue("CurriculumSubject", "id", $subject_id);
	}
	/** Get an SQLQuery retrieving the given subjects
	 * @param integer[] $subjects_ids the subjects to get
	 * @return SQLQuery the query
	 */
	public function getSubjectsQuery($subjects_ids) {
		return SQLQuery::create()->select("CurriculumSubject")->whereIn("CurriculumSubject", "id", $subjects_ids);
	}
	/** Join to table CurriculumSubject
	 * @param SQLQuery $q the query to join
	 * @param string $join_table the table containing a foreign key to CurriculumSubject
	 * @param string $join_key the foreign key to CurriculumSubject
	 * @param boolean $join_with_batch_period if true, an additional join to the BatchPeriod of the subject will be added
	 * @param boolean $join_with_academic_period if true, an additional join to the AcademicPeriod of the subject will be added
	 * @param boolean $join_with_batch if true, ad additional join to the StudentBatch will be added
	 */
	public function joinSubjects(&$q, $join_table, $join_key, $join_with_batch_period = false, $join_with_academic_period = false, $join_with_batch = false) {
		$q->join($join_table, "CurriculumSubject", array($join_key=>"id"));
		if ($join_with_academic_period) $join_with_batch_period = true;
		if ($join_with_batch) $join_with_batch_period = true;
		if ($join_with_batch_period)
			$q->join("CurriculumSubject", "BatchPeriod", array("period"=>"id"));
		if ($join_with_academic_period)
			$q->join("BatchPeriod","AcademicPeriod",array("academic_period"=>"id"));
		if ($join_with_batch)
			$q->join("BatchPeriod","StudentBatch",array("batch"=>"id"));
	}
	
}
?>