<?php 
class service_assign_teacher extends Service {
	
	public function getRequiredRights() { return array("edit_curriculum"); }
	
	public function documentation() { echo "Assign a teacher to a subject and classes"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>people_id</code>: teacher</li>";
		echo "<li><code>subject_teaching_id</code>: SubjectTeaching</li>";
		echo "<li><code>hours</code>: optional, number of hours</li>";
		echo "<li><code>hours_type</code>: optional, hours type (Per week or Per period)</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$people_id = $input["people_id"];
		$subject_teaching_id = $input["subject_teaching_id"];
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
		$subject_id = SQLQuery::create()->select("SubjectTeaching")->whereValue("SubjectTeaching","id",$subject_teaching_id)->field("subject")->executeSingleValue();
		if ($subject_id == null) {
			SQLQuery::rollbackTransaction();
			PNApplication::error("Invalid subject");
			return;
		}
		$subject = PNApplication::$instance->curriculum->getSubjectQuery($subject_id)->executeSingleRow();
		$period = PNApplication::$instance->curriculum->getAcademicPeriodAndBatchPeriod($subject["period"]);
		$nb_weeks = intval($period["weeks"])-intval($period["weeks_break"]);
		// TODO check dates of teacher
		$assigned = SQLQuery::create()
			->select("TeacherAssignment")
			->whereValue("TeacherAssignment", "subject_teaching", $subject_teaching_id)
			->execute();
		
		$subject_period = intval($subject["hours"]);
		if ($subject["hours_type"] == "Per week") $subject_period *= $nb_weeks;
		$already_assigned_period = 0;
		$previous = null;
		foreach ($assigned as $a) {
			if ($a["people"] == $people_id) { $previous = $a; continue; }
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
			SQLQuery::create()
				->updateByKey("TeacherAssignment", 
					array("people"=>$people_id,"subject_Teaching"=>$subject_teaching_id),
					array("hours"=>$hours<>null?$hours:null,"hours_type"=>$hours<>null?$hours_type:null)
				);
		else
			SQLQuery::create()->insert("TeacherAssignment", array(
				"people"=>$people_id,
				"subject_Teaching"=>$subject_teaching_id,
				"hours"=>$hours<>null?$hours:null,
				"hours_type"=>$hours<>null?$hours_type:null
			));
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		} else
			SQLQuery::rollbackTransaction();
	}
	
}
?>