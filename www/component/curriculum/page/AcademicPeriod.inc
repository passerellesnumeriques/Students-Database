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
			->whereValue("BatchPeriod","academic_period",$academic_period_id)
			->join("BatchPeriod", "StudentBatch", array("batch"=>"id"))
			->fieldsOfTable("BatchPeriod")
			->field("StudentBatch","name","batch_name")
			->orderBy("StudentBatch","start_date")
			->execute();
		$this->batch_periods_ids = array();
		foreach ($this->batch_periods as $bp) array_push($this->batch_periods_ids, $bp["id"]);
		// load subjects matching a batch period, with necessary categories and specializations
		$this->subjects = SQLQuery::create()
			->select("CurriculumSubject")
			->whereIn("CurriculumSubject","period",$batch_periods_ids)
			->execute();
		$categories_ids = array();
		$specializations_ids = array();
		foreach ($this->subjects as $s) {
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
		// load teachers present during this period
		$q = SQLQuery::create()
			->select("TeacherDates")
			->where("`start` <= '".$this->academic_period["start"]."'")
			->where("(`end` IS NULL OR `end` > '".$this->academic_period["start"]."'")
			;
		PNApplication::$instance->people->joinPeople($q, "TeacherDates", "people");
		$this->teachers = $q->execute();
	}
	
	public $academic_period;
	public $academic_period_id;
	
	public $batch_periods;
	public $batch_periods_ids;
	
	public $subjects;
	public $categories;
	public $specializations;
	
	public $teachers;
	
}
?>