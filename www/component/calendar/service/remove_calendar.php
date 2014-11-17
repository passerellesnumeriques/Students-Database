<?php 
class service_remove_calendar extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Remove a calendar owned by the current user"; }
	public function inputDocumentation() {
		echo "<code>id</code>: calendar id<br/>";
	}
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$check = SQLQuery::create()->bypassSecurity()
			->select("UserCalendar")
			->whereValue("UserCalendar", "calendar", $input["id"])
			->whereValue("UserCalendar", "user", PNApplication::$instance->user_management->user_id)
			->executeSingleRow();
		if ($check == null) {
			// not owned
			PNApplication::error("You are not allowed to remove this calendar");
			echo "false";
			return;
		}
		SQLQuery::create()->bypassSecurity()->removeKey("Calendar", $input["id"]);
		echo "true";
	}
	
}
?>