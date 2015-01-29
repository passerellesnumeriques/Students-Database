<?php 
require_once("component/calendar/CustomCalendarPlugin.inc");
class StudentsBirthdaysCalendar implements CustomCalendarPlugin {
	
	public function getId() { return "students_birthdays"; }
	public function getName() { return "Students Birthdays"; }
	public function getIcon() { return "/static/people/birthday.gif"; }
	public function getDefaultColor() { return "FF80FF"; }
	public function canAccess() {
		if (in_array("staff",PNApplication::$instance->user_management->people_types))
			return true;
		if (in_array("student",PNApplication::$instance->user_management->people_types))
			return true;
		if (PNApplication::$instance->user_management->hasRight("consult_students_list"))
			return true;
		return false;
	}
	public function getEvents() {
		$current_batches = PNApplication::$instance->curriculum->getCurrentBatches();
		$batches_ids = array();
		foreach ($current_batches as $b) array_push($batches_ids, $b["id"]);
		if (count($batches_ids) > 0) {
			$q = SQLQuery::create()
				->select("Student")
				->whereIn("Student","batch",$batches_ids)
				;
			$q->bypassSecurity(); // we already checked before
			PNApplication::$instance->people->joinPeople($q, "Student", "people");
			$q->field("People","id","people_id");
			$q->field("People","first_name","first_name");
			$q->field("People","last_name","last_name");
			$q->field("People","birth","birth");
			$q->whereNotNull("People", "birth");
			$list = $q->execute();
			$events = array();
			foreach ($list as $student) {
				$event = array(
					"id"=>$student["people_id"],
					"uid"=>PNApplication::$instance->local_domain."_Student_Birthday_".$student["people_id"],
					"start"=>\datamodel\ColumnDate::toTimestamp($student["birth"]),
					"end"=>\datamodel\ColumnDate::toTimestamp($student["birth"])+24*60*60-1,
					"all_day"=>true,
					"last_modified"=>\datamodel\ColumnDate::toTimestamp($student["birth"]),
					"title"=>$student["first_name"]." ".$student["last_name"],
					"description"=>"Birthday of student ".$student["first_name"]." ".$student["last_name"],
					"frequency"=>"YEARLY",
					"app_link_name"=>"Open profile of ".$student["first_name"]." ".$student["last_name"],
					"app_link"=>"popup:/dynamic/people/page/profile?people=".$student["people_id"],
					"attendees"=>array(
						array(
							"name"=>$student["first_name"]." ".$student["last_name"],
							"role"=>"FOR_INFO",
							"participation"=>"UNKNOWN",
							"people"=>$student["people_id"]
						)
					)
				);
				array_push($events, $event);
			}
		} else $events = array();
		return $events;
	}
	
}
?>