<?php 
class service_todo_list extends Service {
	
	public function getRequiredRights() { return array("manage_batches"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		return;
		if (isset($input["batch_id"])) {
			$batch = PNApplication::$instance->curriculum->getBatch($input["batch_id"]);
			$h = "";
			$this->checkBatch($batch, $h);
			echo $h;
		} else if (isset($input["period_id"])) {
			$period = PNApplication::$instance->curriculum->getAcademicPeriod($input["period_id"]);
			$h = "";
			$this->checkPeriod($period, $h);
			echo $h;
		}
	}
	
	private function checkBatch($batch, &$html) {
		// is there any period ?
		$periods = PNApplication::$instance->curriculum->getAcademicPeriods($batch["id"]);
		if (count($periods) == 0) {
			$html .= "<li>No period yet: <a href='#' onclick='editBatch({id:".$batch["id"]."});return false;'>Edit</a></li>";
		}
		// is there any student ?
		$students = SQLQuery::create()->select("Student")->whereValue("Student", "batch", $batch["id"])->execute();
		if (count($students) == 0) {
			$html .= "<li>No student yet: <a href='list?batches=".$batch["id"]."' target='students_page'>Go to the list</a></li>";
		}
	}
	
	private function checkPeriod($period, &$html) {
		// is there any subject ?
		$subjects = PNApplication::$instance->curriculum->getSubjects($period["batch"], $period["id"]);
		if (count($subjects) == 0) {
			$html .= "<li>No subject in the curriculum: <a href='/dynamic/curriculum/page/curriculum?period=".$period["id"]."' target='students_page'>Edit</a></li>";
		}
		// if there are specializations, and the period already started, all students must be assigned to a specialization
		$now = time();
		$start = \datamodel\ColumnDate::toTimestamp($period["start_date"]);
		if ($start < $now) {
			// already started
			$periods = PNApplication::$instance->curriculum->getAcademicPeriods($period["batch"]);
			$periods_ids = array();
			foreach ($periods as $p) array_push($periods_ids, $p["id"]);
			$spes = PNApplication::$instance->curriculum->getAcademicPeriodsSpecializations($periods_ids);
			if (count($spes) > 0) {
				// there are specializations
				foreach ($periods as $p) {
					$period_spes = array();
					foreach ($spes as $s) if ($s["period"] == $p["id"]) array_push($period_spes, $s["specialization"]);
					if (count($period_spes) == 0) {
						// not in this period
						if ($p["id"] == $period["id"])
							break; // no specialization on our period
					} else {
						if ($p["id"] <> $period["id"])
							break; // the split into specializations is done before
						// we are there: our period has a split into specialization
						// check if all students are assigned
						$nb = SQLQuery::create()
							->select("Student")
							->whereValue("Student","batch",$period["batch"])
							->where("(`exclusion_date` IS NULL OR `exclusion_date` > '".$period["start_date"]."'")
							->whereNull("Student","specialization")
							->count()
							->executeSingleValue()
							;
						if ($nb > 0) {
							$html .= "<li>".$nb." student".($nb > 1 ? "s" : "")." are not yet assigned to a specialization: <a href='#' onclick=".json_encode("var p = new popup_window('Assign Specializations', theme.build_icon('/static/curriculum/curriculum_16.png',theme.icons_10.edit), '');p.setContentFrame('/dynamic/students/page/assign_specializations?batch=".$period["batch"]."&onsave=reload_list');p.show();").">Assign specializations</a></li>";
						}
						break;
					}
				}
			}
		}
	}
	
}
?>