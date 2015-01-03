<?php 
class page_teacher_assignments extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$people_id = $_GET["people"];
		if (!PNApplication::$instance->user_management->hasRight("consult_curriculum")) {
			if (!in_array("teacher", PNApplication::$instance->user_management->people_types) ||
				$people_id <> PNApplication::$instance->user_management->people_id) {
				PNApplication::error("Access denied");
				return;
			}
		}
		
		$q = SQLQuery::create()
			->select("TeacherAssignment")
			->whereValue("TeacherAssignment","people",$people_id)
			->join("TeacherAssignment","SubjectTeaching",array("subject_teaching"=>"id"))
			;
		PNApplication::$instance->curriculum->joinSubjects($q, "SubjectTeaching", "subject", true, false, true);
		$assigned = $q->fieldsOfTable("TeacherAssignment")
			->field("SubjectTeaching","id","subject_teaching_id")
			->field("SubjectTeaching","subject","subject_id")
			->field("CurriculumSubject","code","subject_code")
			->field("CurriculumSubject","name","subject_name")
			->field("BatchPeriod","id","batch_period")
			->field("BatchPeriod","name","batch_period_name")
			->field("BatchPeriod","academic_period","academic_period_id")
			->field("StudentBatch","id","batch_id")
			->field("StudentBatch","name","batch_name")
			->execute();

		$subject_teaching_ids = array();
		foreach ($assigned as $a) array_push($subject_teaching_ids, $a["subject_teaching"]);
		if (count($subject_teaching_ids) > 0)
			$assigned_groups = SQLQuery::create()->select("SubjectTeachingGroups")->whereIn("SubjectTeachingGroups","subject_teaching",$subject_teaching_ids)->execute();
		else
			$assigned_groups = array();
		
		$groups_ids = array();
		foreach ($assigned_groups as $ag) if (!in_array($ag["group"], $groups_ids)) array_push($groups_ids, $ag["group"]);
		if (count($groups_ids) > 0)
			$groups = PNApplication::$instance->students_groups->getGroupsById($groups_ids);
		else
			$groups = array();
		
		$group_type_ids = array();
		foreach ($groups as $g) if (!in_array($g["type"], $group_type_ids)) array_push($group_type_ids, $g["type"]);
		if (count($group_type_ids) > 0)
			$group_types = PNApplication::$instance->students_groups->getGroupTypes($group_type_ids);
		else 
			$group_types = array();
		
		$current_period = PNApplication::$instance->curriculum->getCurrentAcademicPeriod();
		$all_periods = PNApplication::$instance->curriculum->getAcademicPeriods();
		$all_years = PNApplication::$instance->curriculum->getAcademicYears();
		
		$current_year = null;
		foreach ($all_years as $y) if ($y["id"] == $current_period["year"]) { $current_year = $y; break; }
		
		$dates = SQLQuery::create()->select("TeacherDates")->whereValue("TeacherDates","people",$people_id)->execute();
		
		$this->requireJavascript("section.js");
		
		// --- Current Period ---
		echo "<div style='padding:5px'>";
		$present = false;
		foreach ($dates as $d) 
			if (strtotime($d["start"]) < strtotime($current_period["end"]) && 
				($d["end"] == null || strtotime($d["end"]) > strtotime($current_period["start"]))) {
			$present = true;
			break;
		}
		if ($present) {
			echo "<div id='current' title='Current Period: ".str_replace("'","\\'","Academic Year ".$current_year["name"]." - ".$current_period["name"])."' collapsable='true'>";
			echo "<div style='padding:5px'>";
			$this->generatePeriod($current_period, $assigned, $assigned_groups, $groups, $group_types, $all_years);
			echo "</div>";
			echo "</div>";
			$this->onload("sectionFromHTML('current');");
		}
		// --- Future ---
		$present = false;
		foreach ($dates as $d) if ($d["end"] == null) { $present = true; break; }
		if ($present) {
			$periods = array();
			foreach ($all_periods as $p) if (strtotime($p["start"]) > time()) array_push($periods, $p);
			if (count($periods) > 0) {
				echo "<div id='future' title='Future assignments' collapsable='true'>";
				echo "<div style='padding:5px'>";
				$this->generatePeriods($periods, $assigned, $assigned_groups, $groups, $group_types, $all_years);
				echo "</div>";
				echo "</div>";
				$this->onload("sectionFromHTML('future');");
			}
		}
		// --- Past ---
		$periods = array();
		foreach ($all_periods as $p)
			if (strtotime($p["end"]) < time()) {
				$present = false;
				foreach ($dates as $d) 
					if (strtotime($d["start"]) < strtotime($p["end"]) && 
						($d["end"] == null || strtotime($d["end"]) > strtotime($p["start"]))) {
					$present = true;
					break;
				}
				if ($present)
					array_push($periods, $p);
			}
		if (count($periods) > 0) {
			echo "<div id='past' title='In the past' collapsable='true'>";
			echo "<div style='padding:5px'>";
			$this->generatePeriods($periods, $assigned, $assigned_groups, $groups, $group_types, $all_years);
			echo "</div>";
			echo "</div>";
			$this->onload("sectionFromHTML('past');");
		}
		echo "</div>";
	}
	
	/**
	 * Generate sections for periods
	 * @param array $academic_periods academic periods
	 * @param array $assigned assignments
	 * @param array $assigned_groups assigned groups
	 * @param array $groups groups details
	 * @param array $group_types groups types
	 * @param array $all_years academic years
	 */
	private function generatePeriods($academic_periods, $assigned, $assigned_groups, $groups, $group_types, $all_years) {
		foreach ($academic_periods as $ap) {
			$year = null;
			foreach ($all_years as $y) if ($y["id"] == $ap["year"]) { $year = $y; break; }
			echo "<div class='page_section_title2'>";
			echo "Academic Year ".toHTML($year["name"]).", ".toHTML($ap["name"]);
			echo "</div>";
			$this->generatePeriod($ap, $assigned, $assigned_groups, $groups, $group_types);
		}
	}

	/**
	 * Generate the section for a period
	 * @param array $academic_period academic period
	 * @param array $assigned assignments
	 * @param array $assigned_groups assigned groups
	 * @param array $groups groups details
	 * @param array $group_types groups types
	 */
	private function generatePeriod($academic_period, $assigned, $assigned_groups, $groups, $group_types) {
		$list = array();
		foreach ($assigned as $a) {
			if ($a["academic_period_id"] <> $academic_period["id"]) continue;
			array_push($list, $a);
		}
		if (count($list) == 0) {
			echo "<i>No subject assigned during this period</i>";
		} else {
			$can_go_to_grades = false;
			if (PNApplication::$instance->user_management->hasRight("consult_students_grades"))
				$can_go_to_grades = true;
			else if ($_GET["people"] == PNApplication::$instance->user_management->people_id)
				$can_go_to_grades = true;
			foreach ($list as $a) {
				echo "<div>";
				if ($can_go_to_grades) echo "<a href='/dynamic/transcripts/page/subject_grades?subject=".$a["subject_id"]."&grouping=".$a["subject_teaching_id"]."' class='black_link' title='Click to open grades for this subject'>";
				echo toHTML($a["subject_code"])." - ".toHTML($a["subject_name"]);
				if ($can_go_to_grades) echo "</a>";
				echo " (";
				echo "Batch ".toHTML($a["batch_name"]);
				echo ", ";
				$groups_list = array();
				foreach ($assigned_groups as $ag)
					if ($ag["subject_teaching"] == $a["subject_teaching_id"])
						foreach ($groups as $g)
							if ($g["id"] == $ag["group"]) {
								array_push($groups_list, $g);
								break;
							}
				$group_type_id = $groups_list[0]["type"];
				$group_type = null;
				foreach ($group_types as $gt) if ($gt["id"] == $group_type_id) { $group_type = $gt; break; }
				echo toHTML($group_type["name"])." ";
				usort($groups_list, function($c1,$c2) {
					return intval($c1["id"])-intval($c2["id"]);
				});
				$first = true;
				foreach ($groups_list as $g) {
					if ($first) $first = false; else echo " + ";
					echo toHTML($g["name"]);
				}
				echo ")";
				echo "</div>";
			}
		}
	}
}
?>