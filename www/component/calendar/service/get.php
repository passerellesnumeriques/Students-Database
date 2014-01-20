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
			PNApplication::error("Access denied");
			return;
		}
		
		$events = SQLQuery::create()->bypass_security()->select("CalendarEvent")->where("calendar", $calendar_id)->join("CalendarEvent", "CalendarEventFrequency", array("id"=>"event"))->execute();
		//$attendees = SQLQuery::create()->bypass_security()->select("CalendarEvent")->where("calendar", $calendar_id)->join("CalendarEvent", "CalendarEventAttendee", array("id"=>"event"))->execute();
		echo "[";
		$first = true;
		foreach ($events as $ev) {
			if ($first) $first = false; else echo ",";
			echo "new CalendarEvent(";
			echo $ev["id"];
			echo ",".$ev["calendar"];
			echo ",".json_encode($ev["uid"]);
			echo ",new Date(parseInt(".json_encode($ev["start"]).")*1000)";
			echo ",new Date(parseInt(".json_encode($ev["end"]).")*1000)";
			echo ",".($ev["all_day"] == "1" ? "true" : "false");
			echo ",".json_encode($ev["last_modified"]);
			echo ",".json_encode($ev["title"]);
			echo ",".json_encode($ev["description"]);
			echo ",".json_encode($ev["location_freetext"]);
			echo ",".json_encode($ev["organizer"]);
			echo ",".json_encode($ev["participation"]);
			echo ",".json_encode($ev["role"]);
			echo ")";
		}
		echo "]";
	}	
	
}
?>