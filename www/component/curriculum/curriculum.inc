<?php
/**
 * Handles curriculum information: students batches, academic periods, specializations, classes, and subjects. However, nothing is linked to a student, this is done by the student component 
 */ 
class curriculum extends Component {
	
	public function getPluginImplementations() {
		require_once("CurriculumStudentsPlugin.inc");
		return array(
			new CurriculumStudentsPlugin()
		);
	}
	
	public function getBatch($batch_id) {
		return SQLQuery::create()->select("StudentBatch")->whereValue("StudentBatch", "id", $batch_id)->executeSingleRow();;
	}
	
	public function getSpecializations() {
		return SQLQuery::create()->select("Specialization")->execute();
	}
	public function getSpecialization($id) {
		return SQLQuery::create()->select("Specialization")->whereValue("Specialization", "id", $id)->executeSingleRow();
	}
	
	public function getBatchSpecializations($batch_id) {
		return SQLQuery::create()
			->select("AcademicPeriod")
			->whereValue("AcademicPeriod", "batch", $batch_id)
			->join("AcademicPeriod", "AcademicPeriodSpecialization", array("id"=>"period"))
			->whereNotNull("AcademicPeriodSpecialization", "specialization")
			->groupBy("AcademicPeriodSpecialization", "specialization")
			->field("AcademicPeriodSpecialization", "specialization", "specialization")
			->executeSingleField();
	}
	public function getBatchSpecializationsWithName($batch_id) {
		return SQLQuery::create()
			->select("AcademicPeriod")
			->whereValue("AcademicPeriod", "batch", $batch_id)
			->join("AcademicPeriod", "AcademicPeriodSpecialization", array("id"=>"period"))
			->whereNotNull("AcademicPeriodSpecialization", "specialization")
			->groupBy("AcademicPeriodSpecialization", "specialization")
			->field("AcademicPeriodSpecialization", "specialization", "id")
			->join("AcademicPeriodSpecialization", "Specialization", array("specialization"=>"id"))
			->field("Specialization", "name", "name")
			->execute();
	}
	
	public function getAcademicPeriodSpecializations($period_id) {
		return SQLQuery::create()
			->select("AcademicPeriodSpecialization")
			->whereValue("AcademicPeriodSpecialization","period", $period_id)
			->field("AcademicPeriodSpecialization", "specialization")
			->executeSingleField();
	}
	public function getAcademicPeriodSpecializationsWithName($period_id) {
		return SQLQuery::create()
			->select("AcademicPeriodSpecialization")
			->whereValue("AcademicPeriodSpecialization","period", $period_id)
			->join("AcademicPeriodSpecialization", "Specialization", array("specialization"=>"id"))
			->field("AcademicPeriodSpecialization", "specialization", "id")
			->field("Specialization", "name", "name")
			->execute();
	}
	
	public function getAcademicPeriod($period_id) {
		return SQLQuery::create()->select("AcademicPeriod")->where("id",$period_id)->executeSingleRow();
	}
	public function getAcademicPeriods($batch_id) {
		return SQLQuery::create()->select("AcademicPeriod")->whereValue("AcademicPeriod", "batch", $batch_id)->orderBy("AcademicPeriod", "start_date", true)->execute();
	}
	
	public function getPeriodSpecializations($period_id) {
		return SQLQuery::create()->select("AcademicPeriodSpecialization")->whereValue("AcademicPeriodSpecialization","period",$period_id)->field("specialization")->executeSingleField();
	}
	public function getPeriodsSpecializations($periods_ids) {
		return SQLQuery::create()->select("AcademicPeriodSpecialization")->whereIn("AcademicPeriodSpecialization","period",$periods_ids)->execute();
	}
	
	public function getAcademicClass($class_id) {
		return SQLQuery::create()->select("AcademicClass")->where("id", $class_id)->executeSingleRow();
	}
	public function getAcademicClassesForPeriod($period_id, $restrict_specialization = false) {
		$q = SQLQuery::create()->select("AcademicClass")->where("period", $period_id);
		if ($restrict_specialization !== false) {
			$q->where("specialization", $restrict_specialization);
		}
		return $q->execute();
	}
	
	public function joinAcademicClass(&$q, $from_table, $from_table_foreign_key, $filter_period_id = null) {
		$q->join($from_table, "AcademicClass", array($from_table_foreign_key=>"id"));
		if ($filter_period_id <> null)
			$q->whereValue("AcademicClass", "period", $filter_period_id);
	}
	
	public function getSubjectCategories() {
		return SQLQuery::create()->select("CurriculumSubjectCategory")->execute();
	}
	public function getSubjects($batch_id, $period_id = null, $spe_id = null, $category_id = null) {
		$q = SQLQuery::create()->select("CurriculumSubject");
		if ($period_id <> null) $q->whereValue("CurriculumSubject", "period", $period_id);
		else {
			$periods = $this->getAcademicPeriods($batch_id);
			if (count($periods) == 0) return array();
			$ids = array();
			foreach ($periods as $period) array_push($ids, $period["id"]);
			$q->whereIn("CurriculumSubject", "period", $ids);
		}
		if ($spe_id <> null) $q->whereValue("CurriculumSubject", "specialization", $spe_id);
		if ($category_id <> null) $q->whereValue("CurriculumSubject", "category", $category_id);
		return $q->execute();
	}
	
}
?>