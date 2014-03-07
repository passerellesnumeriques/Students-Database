<?php class service_get_event extends Service {		public function get_required_rights() {		return array();	}		public function documentation() { echo "Return event details of the given event id"; } 	public function input_documentation() {		echo "<ul>";			echo "<li><code>id</code>: the id of the event</li>";			echo "<li><code>calendar_id</code>: the id of the calendar</li>";		echo "</ul>"; 	}	public function output_documentation() { echo "Returns a CalendarEvent object"; }			public function execute(&$component, $input) {		$calendar_id = $input["calendar_id"];		$id = $input["id"];		if (!$component->canReadCalendar($calendar_id)) {			PNApplication::error("Access denied");			return;		}		$event = SQLQuery::create()					->bypassSecurity()					->select("CalendarEvent")					->where("id = '".$id."'")					->executeSingleRow();		if(PNApplication::has_errors()){			echo "false";			return;		}		echo "{";
		echo "id:".$event["id"];		echo ",calendar_provider_id:'PN'";
		echo ",calendar_id:".$event["calendar"];
		echo ",uid:".json_encode($event["uid"]);
		echo ",start:".$event["start"];
		echo ",end:".$event["end"];
		echo ",all_day:".($event["all_day"] == "1" ? "true" : "false");
		echo ",last_modified:".json_encode($event["last_modified"]);
		echo ",title:".json_encode($event["title"]);
		echo ",description:".json_encode($event["description"]);
		echo ",location_freetext:".json_encode($event["location_freetext"]);
		echo ",organizer:".json_encode($event["organizer"]);
		echo ",participation:".json_encode($event["participation"]);
		echo ",role:".json_encode($event["role"]);
		echo ",app_link:".json_encode($event["app_link"]);		echo ",app_link_name:".json_encode($event["app_link_name"]);		echo "}";
	}}