<?php 
class service_rename_calendar extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Rename a calendar owned by the current user"; }
	public function inputDocumentation() {
		echo "<code>id</code>: calendar id<br/>";
		echo "<code>name</code>: new name<br/>";
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
			PNApplication::error("You are not allowed to rename this calendar");
			echo "false";
			return;
		}
		SQLQuery::create()->bypassSecurity()->updateByKey("Calendar", $input["id"], array("name"=>$input["name"]));
		echo "true";
	}
	
}
?>