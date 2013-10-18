<?php 
class service_save_event extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Save or create an event"; }
	public function input_documentation() { echo "<code>event</code>: Event object, with same format as service get"; }
	public function output_documentation() { echo "On success, returns the id and uid of the event"; }
	
	/**
	 * @param $component calendar 
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		$event = $input["event"];
		$calendar_id = $event["calendar"];
		if (!$component->canWriteCalendar($calendar_id)) {
			PNApplication::error("Access denied: you cannot modify this calendar.");
			return;
		}
		if (isset($event["id"])) {
			// this is an update
			// check the event belongs to the calendar
			$res = SQLQuery::create()->bypass_security()->select("CalendarEvent")->where("id",$event["id"])->execute_single_row();
			if ($res == null) {
				PNApplication::error("Invalid event id: does not exist");
				return;
			}
			if ($res["calendar"] <> $calendar_id) {
				PNApplication::error("Invalid event id: does not belong to the given calendar");
				return;
			}
			if (isset($event["uid"]) && $event["uid"] <> $res["uid"]) {
				PNApplication::error("Event id and uid do not match");
				return;
			}
			$data = array();
			$data["start"] = $event["start"];
			$data["end"] = $event["end"];
			$data["all_day"] = $event["all_day"];
			$data["last_modified"] = time();
			$data["title"] = $event["title"];
			$data["description"] = $event["description"];
			SQLQuery::create()->bypass_security()->update_by_key("CalendarEvent", $event["id"], $data);
			if (PNApplication::has_errors()) return;
			$event_id = $event["id"];
			$event_uid = $res["uid"];
		} else {
			// this is a new event
			$data["calendar"] = $calendar_id;
			$data["uid"] = $calendar_id."-".time()."-".rand(0, 100000)."@pn.".PNApplication::$instance->current_domain;
			$data["start"] = $event["start"];
			$data["end"] = $event["end"];
			$data["all_day"] = $event["all_day"];
			$data["last_modified"] = time();
			$data["title"] = $event["title"];
			$data["description"] = $event["description"];
			$event_id = SQLQuery::create()->bypass_security()->insert("CalendarEvent", $data);
			$event_uid = $data["uid"];
			if (PNApplication::has_errors()) return;
		}
		if (isset($event["frequency"])) {
			// TODO
		}
		echo "{id:".$event_id.",uid:".json_encode($event_uid)."}";
	}
	
}
?>