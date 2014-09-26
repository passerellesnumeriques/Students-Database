<?php 
class service_get extends Service {
	
	public function getRequiredRights() {
		return array();
	}
	
	public function documentation() { echo "Return events for the given calendar"; }
	public function inputDocumentation() { echo "<code>id</code>: the id of the calendar"; }
	public function outputDocumentation() { echo "array of CalendarEvent"; }
		
	public function execute(&$component, $input) {
		$calendar_id = $input["id"];
		$since = @$input["since"];
		
		if (is_numeric($calendar_id)) {
		
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
				echo "new CalendarEvent(";
				echo $ev["id"];
				echo ",'PN'";
				echo ",".$ev["calendar"];
				echo ",".json_encode($ev["uid"]);
				echo ",".$ev["start"];
				echo ",".$ev["end"];
				echo ",".($ev["all_day"] == "1" ? "true" : "false");
				echo ",".json_encode($ev["last_modified"]);
				echo ",".json_encode($ev["title"]);
				echo ",".json_encode($ev["description"]);
				echo ",".json_encode($ev["location_freetext"]);
				echo ",".json_encode($ev["organizer"]);
				echo ",".json_encode($ev["participation"]);
				echo ",".json_encode($ev["role"]);
				echo ",null"; // TODO frequency
				echo ",".json_encode($ev["app_link"]);
				echo ",".json_encode($ev["app_link_name"]);
				echo ")";
			}
			echo "]";
		} else {
			$plugin = null;
			require_once("component/calendar/CustomCalendarPlugin.inc");
			foreach (PNApplication::$instance->components as $c)
				foreach ($c->getPluginImplementations() as $pi)
					if ($pi instanceof CustomCalendarPlugin)
						if ($pi->getId() == $calendar_id) { $plugin = $pi; break; }
			if ($plugin == null) {
				PNApplication::error("Unknown calendar");
				return;
			}
			if (!$plugin->canAccess()) {
				PNApplication::error("Access denied to calendar ".$plugin->getName());
				return;
			}
			echo "[";
			$first = true;
			foreach ($plugin->getEvents() as $ev) {
				if ($first) $first = false; else echo ",";
				echo "new CalendarEvent(";
				echo $ev["id"];
				echo ",'PN'";
				echo ",".json_encode($plugin->getId());
				echo ",".json_encode($ev["uid"]);
				echo ",".$ev["start"];
				echo ",".$ev["end"];
				echo ",".json_encode($ev["all_day"]);
				echo ",".json_encode($ev["last_modified"]);
				echo ",".json_encode($ev["title"]);
				echo ",".json_encode($ev["description"]);
				echo ",".json_encode(@$ev["location_freetext"]);
				echo ",".json_encode(@$ev["organizer"]);
				echo ",".json_encode(@$ev["participation"]);
				echo ",".json_encode(@$ev["role"]);
				echo ",".json_encode(@$ev["frequency"]);
				echo ",".json_encode(@$ev["app_link"]);
				echo ",".json_encode(@$ev["app_link_name"]);
				echo ")";
			}
			echo "]";
		}
	}	
	
}
?>