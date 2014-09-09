<?php 
class page_teacher_assignments extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$people_id = $_GET["people"];
		if (!PNApplication::$instance->user_management->has_right("consult_curriculum")) {
			if (!in_array("teacher", PNApplication::$instance->user_management->people_types) ||
				$people_id <> PNApplication::$instance->user_management->people_id) {
				PNApplication::error("Access denied");
				return;
			}
		}
		
		$assigned = SQLQuery::create()
			->select("TeacherAssignment")
			->whereValue("TeacherAssignment","people",$people_id)
			->join("TeacherAssignment","CurriculumSubject",array("subject"=>"id"))
			->fieldsOfTable("TeacherAssignment")
			->field("CurriculumSubject","code","subject_code")
			->field("CurriculumSubject","name","subject_name")
			->join("CurriculumSubject","BatchPeriod",array("period"=>"id"))
			->field("BatchPeriod","id","batch_period")
			->field("BatchPeriod","name","batch_period_name")
			->join("BatchPeriod","StudentBatch",array("batch"=>"id"))
			->field("StudentBatch","id","batch_id")
			->field("StudentBatch","name","batch_name")
			->execute();
		$classes_ids = array();
		$subjects_ids = array();
		foreach ($assigned as $a) {
			if (!in_array($a["class"], $classes_ids)) array_push($classes_ids, $a["class"]);
			if (!in_array($a["subject"], $subjects_ids)) array_push($subjects_ids, $a["subject"]);
		}
		if (count($classes_ids) > 0) {
			$where1 = "`SubjectClassMerge`.`class1` IN (";
			$where2 = "`SubjectClassMerge`.`class2` IN (";
			$first = true;
			foreach ($classes_ids as $cid) {
				if ($first) $first = false; else { $where1 .= ","; $where2 .= ","; }
				$where1 .= "'$cid'";
				$where2 .= "'$cid'";
			}
			$where1 .= ")";
			$where2 .= ")";
			$classes_merges = SQLQuery::create()
				->select("SubjectClassMerge")
				->where($where1." OR ".$where2)
				->whereIn("SubjectClassMerge","subject",$subjects_ids)
				->execute();
			foreach ($classes_merges as $cm) {
				if (!in_array($cm["class1"], $classes_ids)) array_push($classes_ids, $cm["class1"]);
				if (!in_array($cm["class2"], $classes_ids)) array_push($classes_ids, $cm["class2"]);
			}
			$classes = SQLQuery::create()->select("AcademicClass")->whereIn("AcademicClass","id",$classes_ids)->execute();
		} else {
			$classes_merges = array();
			$classes = array();
		}
		
		$current_period = PNApplication::$instance->curriculum->getCurrentAcademicPeriod();
		$periods_ids = array();
		foreach ($assigned as $a) if (!in_array($a["batch_period"], $periods_ids)) array_push($periods_ids, $a["batch_period"]);
		if (count($periods_ids) > 0)
			$batch_periods = PNApplication::$instance->curriculum->getBatchPeriodsById($periods_ids);
		else
			$batch_periods = array();
		$all_periods = PNApplication::$instance->curriculum->getAcademicPeriods();
		$all_years = PNApplication::$instance->curriculum->getAcademicYears();
		
		$current_year = null;
		foreach ($all_years as $y) if ($y["id"] == $current_period["year"]) { $current_year = $y; break; }
		
		$dates = SQLQuery::create()->select("TeacherDates")->whereValue("TeacherDates","people",$people_id)->execute();
		
		$this->requireJavascript("section.js");
		
		echo "<div style='padding:5px'>";
		$present = false;
		foreach ($dates as $d) 
			if (strtotime($d["start"]) < strtotime($current_period["end"]) && 
				strtotime($d["end"]) > strtotime($current_period["start"])) {
			$present = true;
			break;
		}
		if ($present) {
			echo "<div id='current' title='Current Period: ".str_replace("'","\\'","Academic Year ".$current_year["name"]." - ".$current_period["name"])."' collapsable='true'>";
			echo "<div style='padding:5px'>";
			$this->generatePeriod($current_period, $assigned, $batch_periods);
			echo "</div>";
			echo "</div>";
			$this->onload("sectionFromHTML('current');");
		}
		$present = false;
		foreach ($dates as $d) if ($d["end"] == null) { $present = true; break; }
		if ($present) {
			$periods = array();
			foreach ($all_periods as $p) if (strtotime($p["start"]) > time()) array_push($periods, $p);
			if (count($periods) > 0) {
				echo "<div id='future' title='Future assignments' collapsable='true'>";
				echo "<div style='padding:5px'>";
				$this->generatePeriods($periods, $assigned, $batch_periods, $all_years);
				echo "</div>";
				echo "</div>";
				$this->onload("sectionFromHTML('future');");
			}
		}
		$periods = array();
		foreach ($all_periods as $p)
			if (strtotime($p["end"]) < time()) {
				$present = false;
				foreach ($dates as $d) 
					if (strtotime($d["start"]) < strtotime($p["end"]) && 
						strtotime($d["end"]) > strtotime($p["start"])) {
					$present = true;
					break;
				}
				if ($present)
					array_push($periods, $p);
			}
		if (count($periods) > 0) {
			echo "<div id='past' title='In the past' collapsable='true'>";
			echo "<div style='padding:5px'>";
			$this->generatePeriods($periods, $assigned, $batch_periods, $all_years, $classes_merges, $classes);
			echo "</div>";
			echo "</div>";
			$this->onload("sectionFromHTML('past');");
		}
		echo "</div>";
	}
	
	private function generatePeriods($academic_periods, $assigned, $batch_periods, $all_years, $classes_merges, $classes) {
		foreach ($academic_periods as $ap) {
			$year = null;
			foreach ($all_years as $y) if ($y["id"] == $ap["year"]) { $year = $y; break; }
			echo "<div class='page_section_title2'>";
			echo "Academic Year ".toHTML($year["name"]).", ".toHTML($ap["name"]);
			echo "</div>";
			$this->generatePeriod($ap, $assigned, $batch_periods, $classes_merges, $classes);
		}
	}

	private function generatePeriod($academic_period, $assigned, $batch_periods, $classes_merges, $classes) {
		$list = array();
		foreach ($assigned as $a) {
			foreach ($batch_periods as $bp) {
				if ($bp["academic_period"] == $academic_period["id"]) {
					if ($a["batch_period"] <> $bp["id"]) continue;
					$found = false;
					for ($i = 0; $i < count($list); $i++) {
						if ($list[$i]["subject"] == $a["subject"]) {
							if (!in_array($a["class"], $list[$i]["classes"]))
								array_push($list[$i]["classes"], $a["class"]);
							$found = true;
							break;
						}
					}
					if (!$found) {
						$a["classes"] = array($a["class"]);
						array_push($list, $a);
					}
					break;
				}
			}
		}
		if (count($list) == 0) {
			echo "<i>No subject assigned during this period</i>";
		} else {
			$can_go_to_grades = false;
			if (PNApplication::$instance->user_management->has_right("consult_students_grades"))
				$can_go_to_grades = true;
			else if ($_GET["people"] == PNApplication::$instance->user_management->people_id)
				$can_go_to_grades = true;
			foreach ($list as $subject) {
				echo "<div>";
				if ($can_go_to_grades) echo "<a href='/dynamic/transcripts/page/subject_grades?subject=".$subject["subject"]."' class='black_link'>";
				echo toHTML($subject["subject_code"])." - ".toHTML($subject["subject_name"]);
				if ($can_go_to_grades) echo "</a>";
				$assigned_classes = $subject["classes"];
				foreach ($classes_merges as $cm) {
					$oc = null;
					if (in_array($cm["class1"], $assigned_classes)) $oc = $cm["class2"];
					else if (in_array($cm["class2"], $assigned_classes)) $oc = $cm["class1"];
					if ($oc <> null && !in_array($oc, $assigned_classes)) array_push($assigned_classes, $oc);
				}
				echo " (";
				echo "Batch ".toHTML($subject["batch_name"]);
				echo ", Class";
				if (count($assigned_classes) > 1) echo "es";
				echo " ";
				$first = true;
				foreach ($assigned_classes as $ac) {
					if ($first) $first = false; else echo "+";
					foreach ($classes as $c) if ($c["id"] == $ac) { echo toHTML($c["name"]); break; }
				}
				echo ")";
				echo "</div>";
			}
		}
	}
}
?>