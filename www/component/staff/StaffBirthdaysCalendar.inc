<?php 
require_once("component/calendar/CustomCalendarPlugin.inc");
class StaffBirthdaysCalendar implements CustomCalendarPlugin {
	
	public function getId() { return "staff_birthdays"; }
	public function getName() { return "Staff Birthdays"; }
	public function getIcon() { return "/static/people/birthday.gif"; }
	public function getDefaultColor() { return "FF80FF"; }
	public function canAccess() {
		if (in_array("staff",PNApplication::$instance->user_management->people_types))
			return true;
		if (PNApplication::$instance->user_management->has_right("consult_staff_list"))
			return true;
		return false;
	}
	public function getEvents() {
		$q = SQLQuery::create()
			->select("Staff")
			->join("Staff", "StaffPosition", array("people"=>"people",null=>array("end"=>null)))
			;
		$q->bypassSecurity(); // we already checked before
		PNApplication::$instance->people->joinPeople($q, "Staff", "people");
		$q->field("People","id","people_id");
		$q->field("People","first_name","first_name");
		$q->field("People","last_name","last_name");
		$q->field("People","birth","birth");
		$q->whereNotNull("People", "birth");
		$list = $q->execute();
		$events = array();
		foreach ($list as $staff) {
			$event = array(
				"id"=>$staff["people_id"],
				"uid"=>PNApplication::$instance->local_domain."_Staff_Birthday_".$staff["people_id"],
				"start"=>strtotime($staff["birth"]),
				"end"=>strtotime($staff["birth"])+24*60*60-1,
				"all_day"=>true,
				"last_modified"=>strtotime($staff["birth"]),
				"title"=>$staff["first_name"]." ".$staff["last_name"],
				"description"=>"Birthday of ".$staff["first_name"]." ".$staff["last_name"].", PN Staff !",
				"frequency"=>array(
					"frequency"=>"YEARLY",
					"until"=>null
				)
			);
			array_push($events, $event);
		}
		return $events;
	}
	
}
?>