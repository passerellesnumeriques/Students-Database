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
		require_once("component/calendar/CalendarJSON.inc");
		
		if (is_numeric($calendar_id)) {
		
			if (!$component->canReadCalendar($calendar_id)) {
				PNApplication::error("Access denied to calendar");
				return;
			}
			
			$events = SQLQuery::create()->bypassSecurity()->select("CalendarEvent")->where("calendar", $calendar_id)->join("CalendarEvent", "CalendarEventFrequency", array("id"=>"event"))->execute();
			CalendarJSON::addAttendees($events);
		} else {
			$plugin = null;
			require_once("component/calendar/CustomCalendarPlugin.inc");
			foreach (PNApplication::$instance->components as $c)
				foreach ($c->getPluginImplementations("CustomCalendarPlugin") as $pi)
					if ($pi->getId() == $calendar_id) { $plugin = $pi; break; }
			if ($plugin == null) {
				PNApplication::error("Unknown calendar");
				return;
			}
			if (!$plugin->canAccess()) {
				PNApplication::error("Access denied to calendar ".$plugin->getName());
				return;
			}
			$events = $plugin->getEvents();
			for ($i = count($events)-1; $i >= 0; $i--)
				$events[$i]["calendar"] = $plugin->getId();
		}
		echo "[";
		$first = true;
		foreach ($events as $ev) {
			if ($first) $first = false; else echo ",";
			echo CalendarJSON::JSON($ev);
		}
		echo "]";
	}	
	
}
?>