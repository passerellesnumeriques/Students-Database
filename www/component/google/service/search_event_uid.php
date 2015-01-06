<?php 
class service_search_event_uid extends Service {
	
	public function getRequiredRights() { return array("admin_google"); }
	
	public function documentation() { echo "Search an event by UID on Google"; }
	public function inputDocumentation() { echo "uid"; }
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		require_once("component/google/lib_api/PNGoogleCalendar.inc");
		$gcal = new PNGoogleCalendar();
		$list = $gcal->getGoogleCalendars();
		$s = "";
		foreach ($list as $cal) {
			$events = $gcal->getAllEvents($cal->getId(), true);
			foreach ($events as $ev) {
				if (strtolower($ev->iCalUID) == strtolower($input["uid"]))
					$s .= "Found ".$ev->iCalUID." in Google calendar ID ".$cal->getId()." (status:".$ev->status.")<br/>";
			}
		}
		$events = SQLQuery::create()->bypassSecurity()->select("CalendarEvent")->where("`uid` LIKE '".SQLQuery::escape($input["uid"])."'")->execute();
		foreach ($events as $e)
			$s .= "Found ".$e["uid"]." in PN Calendar ID ".$e["calendar"]."<br/>";
		echo json_encode($s);
	}
	
}
?>