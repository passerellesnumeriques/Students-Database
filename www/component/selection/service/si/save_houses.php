<?php 
class service_si_save_houses extends Service {
	
	public function getRequiredRights() { return array("edit_social_investigation"); }
	
	public function documentation() { echo "Save houses information"; }
	public function inputDocumentation() { echo "applicant, houses"; }
	public function outputDocumentation() { echo "list of ids on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		
		$existing_ids = SQLQuery::create()->select("SIHouse")->whereValue("SIHouse","applicant",$input["applicant"])->field("id")->executeSingleField();
		$ids = array();
		foreach ($input["houses"] as $house) {
			$house["applicant"] = $input["applicant"];
			if (@$house["id"] > 0) {
				$id = $house["id"];
				unset($house["id"]);
				$found = false;
				for ($i = 0; $i < count($existing_ids); $i++)
					if ($existing_ids[$i] == $id) {
						$found = true;
						array_splice($existing_ids, $i, 1);
						break;
					}
				if (!$found) {
					PNApplication::error("Invalid house id");
					return;
				}
				SQLQuery::create()->updateByKey("SIHouse", $id, $house);
				array_push($ids, $id);
			} else {
				unset($house["id"]);
				$id = SQLQuery::create()->insert("SIHouse", $house);
				array_push($ids, $id);
			}
		}
		if (count($existing_ids) > 0)
			SQLQuery::create()->removeKeys("SIHouse", $existing_ids);
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo json_encode($ids);
		}
	}
	
}
?>