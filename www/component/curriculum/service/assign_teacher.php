<?php 
class service_assign_teacher extends Service {
	
	public function getRequiredRights() { return array("edit_curriculum"); }
	
	public function documentation() { echo "Assign a teacher to a subject and classes"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>people_id</code>: teacher</li>";
		echo "<li><code>subject_id</code>: subject</li>";
		echo "<li><code>classes_ids</code>: the list of classes to assign</li>";
		echo "<li><code>hours</code>: optional, number of hours</li>";
		echo "<li><code>hours_type</code>: optional, hours type (Per week or Per period)</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$people_id = $input["people_id"];
		$subject_id = $input["subject_id"];
		$classes_ids = $input["classes_ids"];
		$hours = @$input["hours"];
		$hours_type = @$input["hours_type"];
		SQLQuery::startTransaction();
		$teacher = PNApplication::$instance->people->getPeople($people_id);
		if ($teacher == null) {
			SQLQuery::rollbackTransaction();
			PNApplication::error("Invalid teacher ID");
			return;
		}
		$types = PNApplication::$instance->people->parseTypes($teacher["types"]);
		if (!in_array("teacher", $types)) {
			SQLQuery::rollbackTransaction();
			PNApplication::error("This people is not a teacher");
			return;
		}
		// TODO check dates of teacher with classes
		$assigned = SQLQuery::create()
			->select("TeacherAssignment")
			->whereValue("TeacherAssignment", "subject", $subject_id)
			->whereIn("TeacherAssignment", "class", $classes_ids)
			->execute();
		$subject = SQLQuery::create()->select("CurriculumSubject")->whereValue("CurriculumSubject","id",$subject_id)->executeSingleRow();
		$period = SQLQuery::create()->select("BatchPeriod")->whereValue("BatchPeriod","id",$subject["period"])->join("BatchPeriod","AcademicPeriod",array("academic_period"=>"id"))->executeSingleRow();
		$nb_weeks = intval($period["weeks"])-intval($period["weeks_break"]);
		$subject_period = intval($subject["hours"]);
		if ($subject["hours_type"] == "Per week") $subject_period *= $nb_weeks;
		foreach ($classes_ids as $class_id) {
			$already_assigned_period = 0;
			$previous = null;
			foreach ($assigned as $a) {
				if ($a["people"] == $people_id) { $previous = $a; continue; }
				if ($a["class"] <> $class_id) continue;
				if ($a["hours"] == null || $hours == null) {
					SQLQuery::rollbackTransaction();
					PNApplication::error("A teacher is already assigned");
					return;
				}
				if ($a["hours_type"] == "Per week")
					$already_assigned_period += intval($a["hours"])*$nb_weeks;
				else
					$already_assigned_period += intval($a["hours"]);
			}
			if ($already_assigned_period >= $subject_period) {
				SQLQuery::rollbackTransaction();
				PNApplication::error("All hours are already assigned");;
				return;
			}
			if ($hours <> null) {
				$h = intval($hours);
				if ($hours_type == "Per week") $h *= $nb_weeks;
				if ($already_assigned_period + $h > $subject_period) {
					SQLQuery::rollbackTransaction();
					PNApplication::error("Assignment exceed number of hours for this subject");
					return;
				}
			}
			
			if ($previous <> null)
				SQLQuery::create()->removeRow("TeacherAssignment",$previous);

			$to_insert = array("people"=>$people_id,"subject"=>$subject_id,"class"=>$class_id);
			if ($hours <> null) {
				$to_insert["hours"] = $hours;
				$to_insert["hours_type"] = $hours_type;
			}
			SQLQuery::create()->insert("TeacherAssignment", $to_insert);
		}
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		} else
			SQLQuery::rollbackTransaction();
	}
	
}
?>