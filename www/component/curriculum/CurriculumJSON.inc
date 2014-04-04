<?php
/**
 * Functions to build JSON structure of the curriculum
 */ 
class CurriculumJSON {
	
	public static function AcademicCalendarJSON() {
		$years = PNApplication::$instance->curriculum->getAcademicYears();
		$periods = PNApplication::$instance->curriculum->getAcademicPeriods();
		$s = "[";
		$first_year = true;
		foreach ($years as $y) {
			if ($first_year) $first_year = false; else $s .= ",";
			$s .= "{";
			$s .= "id:".$y["id"];
			$s .= ",year:".$y["year"];
			$s .= ",name:".json_encode($y["name"]);
			$s .= ",periods:[";
			$first_period = true;
			foreach ($periods as $p) {
				if ($p["year"] <> $y["id"]) continue;
				if ($first_period) $first_period = false; else $s .= ",";
				$s .= self::AcademicPeriodJSONFromDB($p);
			}
			$s .= "]";
			$s .= "}";
		}
		$s .= "]";
		return $s;
	}
	
	public static function AcademicPeriodJSONFromDB($p) {
		$s = "{";
		$s .= "year_id:".$p["year"];
		$s .= ",id:".$p["id"];
		$s .= ",name:".json_encode($p["name"]);
		$s .= ",start:".json_encode($p["start"]);
		$s .= ",end:".json_encode($p["end"]);
		$s .= ",weeks:".$p["weeks"];
		$s .= ",weeks_break:".$p["weeks_break"];
		$s .= "}";
		return $s;
	}
	
	/**
	 * Build a JSON structure Specialization
	 * @param array $spe the row of the specialization from the database
	 * @return string the JSON structure
	 * @no_name_check
	 */
	public static function SpecializationJSON($spe) {
		$s = "{";
		$s .= "id:".$spe["id"];
		$s .= ",name:".json_encode($spe["name"]);
		$s .= "}";
		return $s;
	}
	/**
	 * Build a JavaScript array of Specialization
	 * @return string the JSON structure
	 * @no_name_check
	 */
	public static function SpecializationsJSON() {
		$s = "[";
		$first = true;
		foreach (PNApplication::$instance->curriculum->getSpecializations() as $spe) {
			if ($first) $first = false; else $s .= ",";
			$s .= self::SpecializationJSON($spe);
		}
		$s .= "]";
		return $s;
	}
	
	/**
	 * Build a Batch structure
	 * @param integer $id batch id to retrieve
	 * @return string the JSON structure
	 * @no_name_check
	 */
	public static function BatchJSON($id) {
		$batch = SQLQuery::create()->select("StudentBatch")->whereValue("StudentBatch","id",$id)->executeSingleRow();
		$periods = SQLQuery::create()->select("BatchPeriod")->whereValue("BatchPeriod","batch",$id)->execute();
		if (count($periods) > 0) {
			$periods_ids = array();
			foreach ($periods as $p) array_push($periods_ids, $p["id"]);
			$periods_spe = SQLQuery::create()->select("BatchPeriodSpecialization")->whereIn("BatchPeriodSpecialization","period",$periods_ids)->execute();
			$classes = SQLQuery::create()->select("AcademicClass")->whereIn("AcademicClass", "period", $periods_ids)->execute();
		} else {
			$periods_spe = array();
			$classes = array();
		}
		return self::BatchStructure($batch, $periods, $periods_spe, $classes);
	}
	
	/** Build a JavaScript array of Batch
	 * @return string the JSON structure
	 * @no_name_check
	 */
	public static function BatchesJSON() {
		$batches = SQLQuery::create()->select("StudentBatch")->execute();
		$periods = SQLQuery::create()->select("BatchPeriod")->execute();
		$periods_spe = SQLQuery::create()->select("BatchPeriodSpecialization")->execute();
		$classes = SQLQuery::create()->select("AcademicClass")->execute();
		$s = "[";
		$first_batch = true;
		foreach ($batches as $batch) {
			if ($first_batch) $first_batch = false; else $s .= ",";
			$s .= self::BatchStructure($batch, $periods, $periods_spe, $classes);
		}
		$s .= "]";
		return $s;
	}
	
	/** Build a Batch structure
	 * @param array $batch batch
	 * @param array $periods periods
	 * @param array $periods_spe specializations per period
	 * @param array $classes classes
	 * @return string the JSON structure
	 * @no_name_check
	 */
	private static function BatchStructure($batch, $periods, $periods_spe, $classes) {
		$s = "{";
		$s .= "id:".$batch["id"];
		$s .= ",name:".json_encode($batch["name"]);
		$s .= ",start_date:".json_encode($batch["start_date"]);
		$s .= ",end_date:".json_encode($batch["end_date"]);
		$s .= ",periods:[";
		$first_period = true;
		foreach ($periods as $period) {
			if ($period["batch"] <> $batch["id"]) continue;
			if ($first_period) $first_period = false; else $s .= ",";
			$s .= "{";
			$s .= "id:".$period["id"];
			$s .= ",name:".json_encode($period["name"]);
			$s .= ",academic_period:".$period["academic_period"];
			$s .= ",available_specializations:[";
			$first_spe = true;
			foreach ($periods_spe as $ps) {
				if ($ps["period"] <> $period["id"]) continue;
				if ($first_spe) $first_spe = false; else $s .= ",";
				$s .= $ps["specialization"];
			}
			$s .= "]";
			$s .= ",classes:[";
			$first_class = true;
			foreach ($classes as $c) {
				if ($c["period"] <> $period["id"]) continue;
				if ($first_class) $first_class = false; else $s .= ",";
				$s .= "{";
				$s .= "id:".$c["id"];
				$s .= ",name:".json_encode($c["name"]);
				$s .= ",spe_id:".json_encode($c["specialization"]);
				$s .= "}";
			}
			$s .= "]";
			$s .= "}";
		}
		$s .= "]";
		$s .= "}";
		return $s;
	}
	
	/** Build a JSON structure SubjectCategory
	 * @param array $category the category from the database
	 * @return string the JSON structure
	 * @no_name_check
	 */
	public static function SubjectCategoryJSON($category) {
		$s = "{";
		$s .= "id:".$category["id"];
		$s .= ",name:".json_encode($category["name"]);
		$s .= "}";
		return $s;
	}
	
	/** Build a JavaScript array of SubjectCategory
	 * @param array $categories the categories from the database
	 * @return string the JSON structure
	 * @no_name_check
	 */
	public static function SubjectCategoriesJSON($categories) {
		$s = "[";
		$first = true;
		foreach ($categories as $category) {
			if ($first) $first = false; else $s .= ",";
			$s .= self::SubjectCategoryJSON($category);
		}
		$s .= "]";
		return $s;
	}
	
	/** Build a JSON structure Subject
	 * @param array $subject the subject from the database
	 * @return string the JSON structure
	 * @no_name_check
	 */
	public static function SubjectJSON($subject) {
		$s = "{";
		$s .= "id:".$subject["id"];
		$s .= ",code:".json_encode($subject["code"]);
		$s .= ",name:".json_encode($subject["name"]);
		$s .= ",category_id:".$subject["category"];
		$s .= ",period_id:".$subject["period"];
		$s .= ",specialization_id:".json_encode($subject["specialization"]);
		$s .= ",hours:".json_encode($subject["hours"]);
		$s .= ",hours_type:".json_encode($subject["hours_type"]);
		$s .= ",coefficient:".json_encode($subject["coefficient"]);
		$s .= "}";
		return $s;
	}
	
	/** Build a JavaScript array of Subject
	 * @param array $subjects the subjects from the database
	 * @return string the JSON structure
	 * @no_name_check
	 */
	public static function SubjectsJSON($subjects) {
		$s = "[";
		$first = true;
		foreach ($subjects as $subject) {
			if ($first) $first = false; else $s .= ",";
			$s .= self::SubjectJSON($subject);
		}
		$s .= "]";
		return $s;
	}
	
	public static function TeacherAssignedJSON($assignment) {
		$s = "{";
		$s .= "people_id:".$assignment["people"];
		$s .= ",subject_id:".$assignment["subject"];
		$s .= ",class_id:".$assignment["class"];
		$s .= "}";
		return $s;
	}
	public static function TeachersAssignedJSON($assignments) {
		$s = "[";
		$first = true;
		foreach ($assignments as $a) {
			if ($first) $first = false; else $s .= ",";
			$s .= self::TeacherAssignedJSON($a);
		}
		$s .= "]";
		return $s;
	}
	
}
?>