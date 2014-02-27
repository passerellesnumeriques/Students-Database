<?php 
class CurriculumJSON {
	
	public static function SpecializationJSON($spe) {
		$s = "{";
		$s .= "id:".$spe["id"];
		$s .= ",name:".json_encode($spe["name"]);
		$s .= "}";
		return $s;
	}
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
	
	public static function BatchesJSON() {
		$batches = SQLQuery::create()->select("StudentBatch")->execute();
		$periods = SQLQuery::create()->select("AcademicPeriod")->execute();
		$periods_spe = SQLQuery::create()->select("AcademicPeriodSpecialization")->execute();
		$classes = SQLQuery::create()->select("AcademicClass")->execute();
		$s = "[";
		$first_batch = true;
		foreach ($batches as $batch) {
			if ($first_batch) $first_batch = false; else $s .= ",";
			$s .= "{";
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
				$s .= ",start_date:".json_encode($period["start_date"]);
				$s .= ",end_date:".json_encode($period["end_date"]);
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
		}
		$s .= "]";
		return $s;
	}
	
	public static function SubjectCategoryJSON($category) {
		$s = "{";
		$s .= "id:".$category["id"];
		$s .= ",name:".json_encode($category["name"]);
		$s .= "}";
		return $s;
	}
	
	public static function SubjectCategoriesJSON($categories) {
		$s .= "[";
		$first = true;
		foreach ($categories as $category) {
			if ($first) $first = false; else $s .= ",";
			$s .= self::SubjectCategoryJSON($category);
		}
		$s .= "]";
		return $s;
	}
	
	public static function SubjectJSON($subject) {
		$s = "{";
		$s .= "id:".$subject["id"];
		$s .= ",code:".json_encode($subject["code"]);
		$s .= ",name:".json_encode($subject["name"]);
		$s .= ",category_id:".$subject["category"];
		$s .= ",period_id:".$subject["period"];
		$s .= ",specialization:".json_encode($subject["specialization"]);
		$s .= ",hours:".json_encode($subject["hours"]);
		$s .= ",hours_type:".json_encode($subject["hours_type"]);
		$s .= "}";
		return $s;
	}
	
	public static function SubjectsJSON($subjects) {
		$s .= "[";
		$first = true;
		foreach ($subjects as $subject) {
			if ($first) $first = false; else $s .= ",";
			$s .= self::SubjectJSON($subject);
		}
		$s .= "]";
		return $s;
	}
}
?>