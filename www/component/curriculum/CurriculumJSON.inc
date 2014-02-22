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
	
	public static function CurriculumJSON() {
		$batches = SQLQuery::create()->select("StudentBatch")->execute();
		$periods = SQLQuery::create()->select("AcademicPeriod")->execute();
		$periods_spe = SQLQuery::create()->select("AcademicPeriodSpecialization")->execute();
		$classes = SQLQuery::create()->select("StudentClass")->execute();
		$s = "[";
		$first_bacth = true;
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
	}
	
}
?>