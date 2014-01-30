<?php 
class service_rename_calendar extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Rename a calendar owned by the current user"; }
	public function input_documentation() {
		echo "<code>id</code>: calendar id<br/>";
		echo "<code>name</code>: new name<br/>";
	}
	public function output_documentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$check = SQLQuery::create()->bypass_security()
			->select("UserCalendar")
			->where_value("UserCalendar", "calendar", $input["id"])
			->where_value("UserCalendar", "user", PNApplication::$instance->user_management->user_id)
			->execute_single_row();
		if ($check == null) {
			// not owned
			PNApplication::error("You are not allowed to rename this calendar");
			echo "false";
			return;
		}
		SQLQuery::create()->bypass_security()->update_by_key("Calendar", $input["id"], array("name"=>$input["name"]));
		echo "true";
	}
	
}
?>