<?php 
class service_staff_import_status extends Service {
	
	public function getRequiredRights() { return array("manage_staff_status"); }
	
	public function documentation() { echo "Import status of staff from another campaign"; }
	public function inputDocumentation() { echo "from"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$from = $input["from"];
		$q = PNApplication::$instance->staff->getCurrentStaffsQuery();
		$current_staff = $q->execute();
		$current_staff_ids = array();
		foreach ($current_staff as $s) array_push($current_staff_ids, $s["people_id"]);
		
		$previous = SQLQuery::create()->selectSubModel("SelectionCampaign", $from)->select("StaffStatus")->whereIn("StaffStatus","people",$current_staff_ids)->execute();
		if (count($previous) == 0) {
			PNApplication::error("There were no staff status information in this campaign.");
			return;
		}
		
		$existing = SQLQuery::create()->select("StaffStatus")->field("people")->executeSingleField();
		
		$to_update = array();
		$to_insert = array();
		
		foreach ($previous as $p) {
			$id = $p["people"];
			// make booleans ok for request
			$p["is"] = $p["is"] == 1 ? 1 : 0;
			$p["exam"] = $p["exam"] == 1 ? 1 : 0;
			$p["interview"] = $p["interview"] == 1 ? 1 : 0;
			$p["si"] = $p["si"] == 1 ? 1 : 0;
			// update or insert
			if (in_array($id, $existing)) {
				unset($p["people"]);
				array_push($to_update, array(array($id),$p));
			} else
				array_push($to_insert, $p);
		}
		
		if (count($to_update) > 0)
			SQLQuery::create()->updateByKeys("StaffStatus", $to_update);
		if (count($to_insert) > 0)
			SQLQuery::create()->insertMultiple("StaffStatus", $to_insert);
		
		echo "true";
	}
	
}
?>