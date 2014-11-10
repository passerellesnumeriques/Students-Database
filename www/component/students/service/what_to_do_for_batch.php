<?php 
class service_what_to_do_for_batch extends Service {
	
	public function getRequiredRights() { return array("manage_batches"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		$this->batch_id = $input["batch"];
		
		// no student in batch
		$student = SQLQuery::create()->select("Student")->whereValue("Student","batch",$this->batch_id)->limit(0, 1)->executeSingleRow();
		if ($student == null) {
			echo "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> No student in batch ".toHTML($this->getBatchName())."<br/>";
		}
		
		// no periods in batch
		$periods = PNApplication::$instance->curriculum->getBatchPeriods($this->batch_id);
		if (count($periods) == 0) {
			echo "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> No period defined in batch ".toHTML($this->getBatchName())."<br/>";
		}

		if (count($periods) > 0) {
			// no classes in period(s)/specialization(s)
			$problems = array();
			$periods_ids = array(); foreach ($periods as $p) array_push($periods_ids, $p["id"]);
			$periods_spes = PNApplication::$instance->curriculum->getBatchPeriodsSpecializations($periods_ids);
			foreach ($periods as $period) {
				$spe_list = array();
				foreach ($periods_spes as $ps) if ($ps["period"] == $period["id"]) array_push($spe_list, $ps["specialization"]);
				if (count($spe_list) == 0) {
					// no specialization
					$classes = PNApplication::$instance->students_groups->getGroups(1, $period["id"], null);
					if (count($classes) == 0)
						$problems[$period["name"]] = array();
				} else {
					foreach ($spe_list as $spe_id) {
						$classes = PNApplication::$instance->students_groups->getGroups(1, $period["id"], $spe_id);
						if (count($classes) == 0) {
							if (!isset($problems[$period["name"]])) $problems[$period["name"]] = array();
							array_push($problems[$period["name"]],$this->getSpecializationName($spe_id));
						}
					}
				}
			}
			if (count($problems) > 0) {
				echo "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> Batch ".toHTML($this->getBatchName()).": No class in ";
				$first = true;
				foreach ($problems as $period_name=>$spe_list) {
					if ($first) $first = false; else echo ", ";
					echo toHTML($period_name);
					if (count($spe_list) > 0) {
						echo " (specialization";
						if (count($spe_list) > 1) echo "s";
						echo " ";
						$first_spe = true;
						foreach ($spe_list as $spe_name) {
							if ($first_spe) $first_spe = false; else echo ",";
							echo toHTML($spe_name);
						}
						echo ")";
					}
				}
			}
		}
		
		if ($student <> null && count($periods) > 0) {
			// there are students in the batch, and periods are defined
			$past_periods = array();
			$current_period = null;
			$next_period = null;
			$now = time();
			foreach ($periods as $period) {
				$ap = PNApplication::$instance->curriculum->getAcademicPeriodFromBatchPeriod($period["id"]);
				$start = datamodel\ColumnDate::toTimestamp($ap["start"]);
				$end = datamodel\ColumnDate::toTimestamp($ap["end"]);
				if ($end < $now)
					array_push($past_periods, array($period,$ap));
				else if ($start < $now && $end > $now)
					$current_period = array($period,$ap);
				else if ($next_period == null || $start < datamodel\ColumnDate::toTimestamp($next_period[1]["start"]))
					$next_period = array($period,$ap);
			}
			
			$relevant_periods = array_merge($past_periods);
			if ($current_period <> null) array_push($relevant_periods, $current_period);
			else if ($next_period <> null) array_push($relevant_periods, $next_period);

			$all_students = SQLQuery::create()->select("Student")->whereValue("Student","batch",$this->batch_id)->execute();
			
			$spe_checked = false;
			foreach ($relevant_periods as $p) {
				// get students list for this period
				$students = array();
				foreach ($all_students as $s)
					if ($s["exclusion_date"] == null || datamodel\ColumnDate::toTimestamp($s["exclusion_date"]) > datamodel\ColumnDate::toTimestamp($p[1]["start"]))
						array_push($students, $s);
				// check if students are assigned to a specialization if needed
				if (!$spe_checked) {
					$spe_list = array();
					foreach ($periods_spes as $ps) if ($ps["period"] == $p[0]["id"]) array_push($spe_list, $ps["specialization"]);
					if (count($spe_list) > 0) {
						// this period contains specializations
						$spe_checked = true;
						$missing = array();
						foreach ($students as $s) if ($s["specialization"] == null) array_push($missing, $s["people"]);
						$this->studentsError($missing, "not assigned to a specialization for batch ".toHTML($this->getBatchName()));
					}
				}
				// check if students are assigned to each group type
				$all_groups = PNApplication::$instance->students_groups->getGroups(null,$p[0]["id"]);
				$groups_types_ids = array();
				foreach ($all_groups as $g) if (!in_array($g["type"], $groups_types_ids)) array_push($groups_types_ids, $g["type"]);
				foreach ($groups_types_ids as $group_type_id) {
					$groups_ids = array();
					foreach ($all_groups as $g) if ($g["type"] == $group_type_id) array_push($groups_ids, $g["id"]);
					$q = PNApplication::$instance->students_groups->getStudentsQueryForGroups($groups_ids);
					$q->field("StudentGroup","people");
					$ids = $q->executeSingleField();
					$missing = array();
					foreach ($students as $s) if (!in_array($s["people"], $ids)) array_push($missing, $s["people"]);
					if (count($missing) > 0) {
						$group_type = PNApplication::$instance->students_groups->getGroupType($group_type_id);
						$this->studentsError($missing, "not assigned to a group ".toHTML($group_type["name"])." for batch ".toHTML($this->getBatchName()).", ".toHTML($p[0]["name"]));
					}
				}
			}
		}
	}
	
	private $batch_id;
	private $batch = null;
	private $specializations = null;
	private function getBatchName() {
		if ($this->batch == null)
			$this->batch = PNApplication::$instance->curriculum->getBatch($this->batch_id);
		return $this->batch["name"];
	}
	private function getSpecializationName($spe_id) {
		if ($this->specializations === null)
			$this->specializations = PNApplication::$instance->curriculum->getSpecializations();
		foreach ($this->specializations as $s)
			if ($s["id"] == $spe_id)
				return $s["name"];
	}
	
	private function studentsError($ids, $msg) {
		if (count($ids) == 0) return;
		echo "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> ";
		$peoples = PNApplication::$instance->people->getPeoples($ids);
		$names = "";
		foreach ($peoples as $people)
			$names .= $people["last_name"]." ".$people["first_name"]."\r\n";
		echo "<span title=\"$names\">";
		echo count($ids)." student".(count($ids)>1 ? "s" : "");
		echo "</span>";
		echo " ".(count($ids) > 1 ? "are" : "is")." ";
		echo $msg;
		echo "<br/>";
	}
	
}
?>