<?php 
class service_get extends Service {
	
	public function get_required_rights() {
		return array();
	}
	
	public function documentation() { echo "Return events for the given calendar"; }
	public function input_documentation() { echo "<code>id</code>: the id of the calendar"; }
	public function output_documentation() { echo "array of CalendarEvent"; }
		
	public function execute(&$component, $input) {
		$calendar_id = $input["id"];
		$since = @$input["since"];
		
		if (!$component->canReadCalendar($calendar_id)) {
			PNApplication::error("Access denied to calendar");
			return;
		}
		
		$events = SQLQuery::create()->bypassSecurity()->select("CalendarEvent")->where("calendar", $calendar_id)->join("CalendarEvent", "CalendarEventFrequency", array("id"=>"event"))->execute();
		//$attendees = SQLQuery::create()->bypassSecurity()->select("CalendarEvent")->where("calendar", $calendar_id)->join("CalendarEvent", "CalendarEventAttendee", array("id"=>"event"))->execute();
		echo "[";
		$first = true;
		foreach ($events as $ev) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$ev["id"];
			echo ",calendar_id:".$ev["calendar"];
			echo ",uid:".json_encode($ev["uid"]);
			echo ",start:".$ev["start"];
			echo ",end:".$ev["end"];
			echo ",all_day:".($ev["all_day"] == "1" ? "true" : "false");
			echo ",last_modified:".json_encode($ev["last_modified"]);
			echo ",title:".json_encode($ev["title"]);
			echo ",description:".json_encode($ev["description"]);
			echo ",location_freetext:".json_encode($ev["location_freetext"]);
			echo ",organizer:".json_encode($ev["organizer"]);
			echo ",participation:".json_encode($ev["participation"]);
			echo ",role:".json_encode($ev["role"]);
			echo ",app_link:".json_encode($ev["app_link"]);
			echo ",app_link_name:".json_encode($ev["app_link_name"]);
			echo "}";
		}
		echo "]";
	}	
	
}
?>