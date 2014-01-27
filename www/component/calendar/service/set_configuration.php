<?php 
class service_set_configuration extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Save user's configuration about a calendar"; }
	public function input_documentation() {
		echo "<code>calendar</code>: calendar id<br/>";
		echo "<code>show</code>: optional, set the visibility of the calendar<br/>";
		echo "<code>color</code>: optional, set the color of the calendar<br/>";
	}
	public function output_documentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		if (!$component->canReadCalendar($input["calendar"])) {
			PNApplication::error("You don't have access to this calendar");
			return;
		}
		SQLQuery::start_transaction();
		$check = SQLQuery::create()->bypass_security()
			->select("UserCalendarConfiguration")
			->where_value("UserCalendarConfiguration", "calendar", $input["calendar"])
			->where_value("UserCalendarConfiguration", "user", PNApplication::$instance->user_management->user_id)
			->execute_single_row();
		if ($check == null) {
			// configuration does not exist yet
			$data = array();
			$data["calendar"] = $input["calendar"];
			$data["user"] = PNApplication::$instance->user_management->user_id;
			if (isset($input["show"])) $data["show"] = $input["show"]; else $data["show"] = true;
			if (isset($input["color"])) $data["color"] = $input["color"];
			SQLQuery::create()->bypass_security()->insert("UserCalendarConfiguration", $data);
		} else {
			// configuration exists
			$key = array(
				"calendar"=>$input["calendar"],
				"user"=>PNApplication::$instance->user_management->user_id
			);
			$data = array();
			if (isset($input["show"])) $data["show"] = $input["show"];
			if (isset($input["color"])) $data["color"] = $input["color"];
			SQLQuery::create()->bypass_security()->update_by_key("UserCalendarConfiguration", $key, $data);
		}
		if (!PNApplication::has_errors()) {
			SQLQuery::end_transaction();
			echo "true";
		} else {
			SQLQuery::cancel_transaction();
			echo "false";
		}
	}
	
}
?>