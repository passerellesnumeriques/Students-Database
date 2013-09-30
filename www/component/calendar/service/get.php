<?php 
class service_get extends Service {
	
	public function get_required_rights() {
		return array();
	}
	
	public function documentation() {
?>Return events for the given calendar<?php 
	}
	public function input_documentation() { 
?><ul>
<li><code>id</code>: the id of the calendar</li>
</ul><?php 
	}
	public function output_documentation() {
?><?php 
		}
		
	public function execute(&$component, $input) {
		$calendar_id = $input["id"];
		$since = @$input["since"];
		
		if (!$component->canReadCalendar($calendar_id)) {
			PNApplication::error("Access denied");
			return;
		}
		
		$events = SQLQuery::create()->bypass_security()->select("CalendarEvent")->where("calendar", $calendar_id)->join("CalendarEvent", "CalendarEventFrequency", array("id"=>"event"))->execute();
		//$attendees = SQLQuery::create()->bypass_security()->select("CalendarEvent")->where("calendar", $calendar_id)->join("CalendarEvent", "CalendarEventAttendee", array("id"=>"event"))->execute();
		echo "{events:[";
		$first = true;
		foreach ($events as $ev) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$ev["id"];
			echo ",uid:".json_encode($ev["uid"]);
			echo ",start:".json_encode($ev["start"]);
			echo ",end:".json_encode($ev["end"]);
			echo ",all_day:".json_encode($ev["all_day"]);
			echo ",last_modified:".json_encode($ev["last_modified"]);
			echo ",title:".json_encode($ev["title"]);
			echo ",description:".json_encode($ev["description"]);
			echo ",location_freetext:".json_encode($ev["location_freetext"]);
			// TODO continue
			echo "}";
		}
		echo "]}";
	}	
	
}
?>