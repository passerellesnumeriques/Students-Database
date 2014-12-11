<?php 
class service_si_save_help_incomes extends Service {
	
	public function getRequiredRights() { return array("edit_social_investigation"); }
	
	public function documentation() { echo "Save help incomes"; }
	public function inputDocumentation() { echo "applicant, list"; }
	public function outputDocumentation() { echo "list of {given_id,new_id} for the newly created items"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		
		$applicant_id = $input["applicant"];
		$list = @$input["list"];
		
		$existing = SQLQuery::create()->select("SIHelpIncome")->whereValue("SIHelpIncome","applicant",$applicant_id)->execute();
		$to_insert = array();
		$to_update = array();
		$given_ids = array();
		foreach ($list as $item) {
			if ($item["who"] <> "") {
				$exist = null;
				for ($i = 0; $i < count($existing); $i++)
					if ($existing[$i]["id"] == $item["id"]) {
						$exist = $existing[$i];
						array_splice($existing, $i, 1);
						break;
					}
				if ($exist <> null) {
					unset($item["id"]);
					$item["applicant"] = $applicant_id;
					array_push($to_update, array(array($exist["id"]),$item));
				} else {
					array_push($given_ids, $item["id"]);
					unset($item["id"]);
					$item["applicant"] = $applicant_id;
					array_push($to_insert, $item);
				}
			}
		}
		if (count($to_update) > 0)
			SQLQuery::create()->updateByKeys("SIHelpIncome", $to_update);
		if (count($to_insert) > 0)
			$new_ids = SQLQuery::create()->insertMultiple("SIHelpIncome", $to_insert);
		if (count($existing) > 0)
			SQLQuery::create()->removeRows("SIHelpIncome", $existing);
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