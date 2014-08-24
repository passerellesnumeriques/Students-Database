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
			->execute();
		
		$current_period = PNApplication::$instance->curriculum->getCurrentAcademicPeriod();
		$periods_ids = array();
		foreach ($assigned as $a) if (!in_array($a["period"], $periods_ids)) array_push($periods_ids, $a["period"]);
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
			$this->generatePeriods($periods, $assigned, $batch_periods, $all_years);
			echo "</div>";
			echo "</div>";
			$this->onload("sectionFromHTML('past');");
		}
		echo "</div>";
	}
	
	private function generatePeriods($academic_periods, $assigned, $batch_periods, $all_years) {
		foreach ($academic_periods as $ap) {
			$year = null;
			foreach ($all_years as $y) if ($y["id"] == $ap["year"]) { $year = $y; break; }
			echo "<div class='page_section_title2'>";
			echo "Academic Year ".toHTML($year["name"]).", ".toHTML($ap["name"]);
			echo "</div>";
			$this->generatePeriod($ap, $assigned, $batch_periods);
		}
	}

	private function generatePeriod($academic_period, $assigned, $batch_periods) {
		$list = array();
		foreach ($assigned as $a) {
			foreach ($batch_periods as $bp) {
				if ($bp["academic_period"] == $academic_period["id"]) {
					array_push($list, $a);
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
				if ($can_go_to_grades) echo "<a href='/dynamic/transcripts/page/subject_grades?subject=".$subject["id"]."' class='black_link'>";
				echo $subject["code"]." - ".$subject["name"];
				if ($can_go_to_grades) echo "</a>";
				echo "</div>";
			}
		}
	}
}
?>