<?php 
class service_common_centers_who extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Return a list of staff organized to add to an event"; }
	public function inputDocumentation() { echo "type, already_there"; }
	public function outputDocumentation() { echo "selection_team, staffs"; }
	
	public function execute(&$component, $input) {
		$q = PNApplication::$instance->staff->getCurrentStaffsQuery(true, true, true);
		$q->join("People","StaffStatus",array("id"=>"people"));
		$q->field("StaffStatus",$input["type"],"can_do");
		$staffs = $q->execute();
		echo "{";
		echo "selection_team:[";
		$first = true;
		foreach ($staffs as $staff) {
			if ($staff["staff_department"] <> "Selection") continue;
			if ($first) $first = false; else echo ",";
			echo "{people:".PeopleJSON::People($staff).",can_do:".json_encode($staff["can_do"] == 1)."}";
		}
		echo "],staffs:[";
		$first = true;
		foreach ($staffs as $staff) {
			if ($staff["staff_department"] == "Selection") continue;
			if ($first) $first = false; else echo ",";
			echo "{people:".PeopleJSON::People($staff).",can_do:".json_encode($staff["can_do"] == 1)."}";
		}
		echo "]";
		echo "}";
	}
	
}
?>