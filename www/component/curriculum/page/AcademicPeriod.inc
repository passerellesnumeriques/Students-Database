<?php 
class AcademicPeriod {
	
	public static function fromID($academic_period_id) {
		return new AcademicPeriod(SQLQuery::create()->select("AcademicPeriod")->whereValue("AcademicPeriod","id",$academic_period_id)->executeSingleRow());
	}
	
	public function __construct($academic_period) {
		$this->academic_period = $academic_period;
		$this->academic_period_id = $academic_period["id"];
		// load batch periods corresponding to the academic period
		$this->batch_periods = SQLQuery::create()
			->select("BatchPeriod")
			->whereValue("BatchPeriod","academic_period",$this->academic_period_id)
			->join("BatchPeriod", "StudentBatch", array("batch"=>"id"))
			->fieldsOfTable("BatchPeriod")
			->field("StudentBatch","name","batch_name")
			->orderBy("StudentBatch","start_date")
			->execute();
		$this->batch_periods_ids = array();
		foreach ($this->batch_periods as $bp) array_push($this->batch_periods_ids, $bp["id"]);
		// load subjects matching a batch period, with necessary categories and specializations
		if (count($this->batch_periods_ids) > 0)
			$this->subjects = SQLQuery::create()
				->select("CurriculumSubject")
				->whereIn("CurriculumSubject","period",$this->batch_periods_ids)
				->execute();
		else
			$this->subjects = array();
		$subjects_ids = array();
		$categories_ids = array();
		$specializations_ids = array();
		foreach ($this->subjects as $s) {
			array_push($subjects_ids, $s["id"]);
			if (!in_array($s["category"], $categories_ids)) array_push($categories_ids, $s["category"]);
			if ($s["specialization"] <> null && !in_array($s["specialization"], $specializations_ids)) array_push($specializations_ids, $s["specialization"]);
		}
		if (count($categories_ids) == 0)
			$this->categories = array();
		else
			$this->categories = SQLQuery::create()
				->select("CurriculumSubjectCategory")
				->whereIn("CurriculumSubjectCategory", "id", $categories_ids)
				->execute();
		if (count($specializations_ids) == 0)
			$this->specializations = array();
		else
			$this->specializations = SQLQuery::create()
				->select("Specialization")
				->whereIn("Specialization", "id", $specializations_ids)
				->execute();
		// load classes
		if (count($this->batch_periods_ids) > 0)
			$this->classes = SQLQuery::create()
				->select("AcademicClass")
				->whereIn("AcademicClass", "period", $this->batch_periods_ids)
				->execute();
		else
			$this->classes = array();
		// load teachers present during this period
		$q = SQLQuery::create()
			->select("TeacherDates")
			->where("`start` <= '".$this->academic_period["start"]."'")
			->where("(`end` IS NULL OR `end` > '".$this->academic_period["start"]."')")
			;
		PNApplication::$instance->people->joinPeople($q, "TeacherDates", "people");
		$this->teachers = $q->execute();
		$this->teachers_query = $q;
		// load merge of classes
		if (count($subjects_ids) > 0)
			$this->classes_merge = SQLQuery::create()
				->select("SubjectClassMerge")
				->whereIn("SubjectClassMerge","subject",$subjects_ids)
				->execute();
		else
			$this->classes_merge = array();
		// load teachers assignments
		if (count($subjects_ids) > 0)
			$this->teachers_assignments = SQLQuery::create()
				->select("TeacherAssignment")
				->whereIn("TeacherAssignment", "subject", $subjects_ids)
				->execute();
		else
			$this->teachers_assignments = array();
	}
	
	public $academic_period;
	public $academic_period_id;
	
	public $batch_periods;
	public $batch_periods_ids;
	
	public $subjects;
	public $categories;
	public $specializations;
	
	public $classes;
	public $classes_merge;
	
	public $teachers;
	public $teachers_query;
	public $teachers_assignments;
	
	public function getSpecializationName($spe_id) {
		foreach ($this->specializations as $s) if ($s["id"] == $spe_id) return $s["name"];
	}
	
	public function getClassesFor($batch_period_id, $spe_id) {
		$list = array();
		foreach ($this->classes as $c)
			if ($c["period"] == $batch_period_id && $c["specialization"] == $spe_id)
				array_push($list, $c);
		return $list;
	}
	public function getSubjectsForBatch($batch_period_id) {
		$list = array();
		foreach ($this->subjects as $s)
			if ($s["period"] == $batch_period_id)
				array_push($list, $s);
		return $list;
	}
	public function getSubjectsFor($batch_period_id, $spe_id) {
		$list = array();
		foreach ($this->subjects as $s)
			if ($s["period"] == $batch_period_id && ($s["specialization"] == null || $s["specialization"] == $spe_id))
				array_push($list, $s);
		return $list;
	}
	
	/*
	 * Return a list of (subject=>...,classes=>array(array(class1_id,class2_id...),array(...))
	 */
	public function getMergedClasses($subjects, $classes) {
		$list = array();
		foreach ($subjects as $s) {
			$sub = array("subject"=>$s,"classes"=>array());
			foreach ($classes as $c) {
				// look for merge
				$found = false;
				foreach ($this->classes_merge as $cm) {
					if ($cm["subject"] <> $s["id"]) continue;
					if ($cm["class1"] == $c["id"]) $merge = $cm["class2"];
					else if ($cm["class2"] == $c["id"]) $merge = $cm["class1"];
					else continue;
					for ($i = 0; $i < count($sub["classes"]); $i++) {
						if (in_array($merge, $sub["classes"][$i])) {
							$found = true;
							array_push($sub["classes"][$i], $c["id"]);
							break;
						}
					}
					if ($found) break;
				}
				if (!$found) {
					array_push($sub["classes"], array($c["id"]));
				}
			}
			array_push($list, $sub);
		}
		return $list;
	}
	
	public function teachersJSON() {
		require_once("component/people/PeopleJSON.inc");
		return PeopleJSON::Peoples($this->teachers);
	}
}
?>