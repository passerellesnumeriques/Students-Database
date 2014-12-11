<?php 
class service_si_save_belongings extends Service {
	
	public function getRequiredRights() { return array("edit_social_investigation"); }
	
	public function documentation() { echo "Save belongings"; }
	public function inputDocumentation() { echo "applicant, belongings"; }
	public function outputDocumentation() { echo "list of {given_id,new_id} for the newly created belongings"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		
		$applicant_id = $input["applicant"];
		$belongings = @$input["belongings"];
		
		$existing = SQLQuery::create()->select("SIBelonging")->whereValue("SIBelonging","applicant",$applicant_id)->execute();
		$to_insert = array();
		$to_update = array();
		$given_ids = array();
		foreach ($belongings as $b) {
			if ($b["description"] <> "") {
				$exist = null;
				for ($i = 0; $i < count($existing); $i++)
					if ($existing[$i]["id"] == $b["id"]) {
						$exist = $existing[$i];
						array_splice($existing, $i, 1);
						break;
					}
				if ($exist <> null) {
					unset($b["id"]);
					$b["applicant"] = $applicant_id;
					array_push($to_update, array(array($exist["id"]),$b));
				} else {
					array_push($given_ids, $b["id"]);
					unset($b["id"]);
					$b["applicant"] = $applicant_id;
					array_push($to_insert, $b);
				}
			}
		}
		if (count($to_update) > 0)
			SQLQuery::create()->updateByKeys("SIBelonging", $to_update);
		if (count($to_insert) > 0)
			$new_ids = SQLQuery::create()->insertMultiple("SIBelonging", $to_insert);
		if (count($existing) > 0)
			SQLQuery::create()->removeRows("SIBelonging", $existing);
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "[";
			for ($i = 0; $i < count($given_ids); $i++) {
				if ($i > 0) echo ",";
				echo "{given_id:".$given_ids[$i].",new_id:".$new_ids[$i]."}";
			}
			echo "]";
		}
	}
	
}
?>