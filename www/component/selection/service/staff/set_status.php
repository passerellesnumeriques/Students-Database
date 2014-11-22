<?php 
class service_staff_set_status extends Service {
	
	public function getRequiredRights() { return array("manage_staff_status"); }
	
	public function documentation() { echo "Set status of staff"; }
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$existing = SQLQuery::create()->select("StaffStatus")->field("people")->executeSingleField();
		$to_insert = array();
		foreach ($input["staffs"] as $s) {
			$fields = array();
			if (array_key_exists("is", $s)) $fields["is"] = $s["is"] ? 1 : 0;
			if (array_key_exists("exam", $s)) $fields["exam"] = $s["exam"] ? 1 : 0;
			if (array_key_exists("interview", $s)) $fields["interview"] = $s["interview"] ? 1 : 0;
			if (array_key_exists("si", $s)) $fields["si"] = $s["si"] ? 1 : 0;
			if (in_array($s["people"], $existing))
				SQLQuery::create()->updateByKey("StaffStatus", $s["people"], $fields);
			else {
				if (!isset($fields["is"])) $fields["is"] = 0;
				if (!isset($fields["exam"])) $fields["exam"] = 0;
				if (!isset($fields["interview"])) $fields["interview"] = 0;
				if (!isset($fields["si"])) $fields["si"] = 0;
				$fields["people"] = $s["people"];
				array_push($to_insert, $fields);
			}
		}
		if (count($to_insert) > 0)
			SQLQuery::create()->insertMultiple("StaffStatus", $to_insert);
		echo "true";
	}
	
}
?>