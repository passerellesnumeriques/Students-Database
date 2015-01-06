<?php 
class service_si_save_farm extends Service {
	
	public function getRequiredRights() { return array("edit_social_investigation"); }
	
	public function documentation() { echo "Save farm information"; }
	public function inputDocumentation() { echo "applicant, farm, productions"; }
	public function outputDocumentation() { echo "list of {given_id,new_id} for the productions for newly created ones"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		
		$applicant_id = $input["applicant"];
		$farm = @$input["farm"];
		$productions = @$input["productions"];
		
		$has_farm = $farm <> null && ($farm["land_size"] > 0 || $farm["land_status"] <> null || $farm["land_cost"] > 0 || $farm["land_comment"] <> null || $farm["income"] > 0 || $farm["income_freq"] <> null || $farm["comment"] <> null || count($productions) > 0);
		
		$existing_farm = SQLQuery::create()->select("SIFarm")->whereValue("SIFarm","applicant",$applicant_id)->executeSingleRow();
		if ($existing_farm == null) {
			if ($has_farm) {
				// create farm
				$farm["applicant"] = $applicant_id;
				SQLQuery::create()->insert("SIFarm", $farm);
			}
		} else if ($has_farm) {
			unset($farm["applicant"]);
			SQLQuery::create()->updateByKey("SIFarm", $applicant_id, $farm);
		} else
			SQLQuery::create()->removeKey("SIFarm", $applicant_id);
		
		$existing_productions = SQLQuery::create()->select("SIFarmProduction")->whereValue("SIFarmProduction","applicant",$applicant_id)->execute();
		$to_insert = array();
		$to_update = array();
		$given_ids = array();
		foreach ($productions as $p) {
			if ($p["nb"] > 0 || $p["income"] > 0 || $p["income_freq"] <> null || $p["comment"] <> null) {
				$existing = null;
				for ($i = 0; $i < count($existing_productions); $i++)
					if ($existing_productions[$i]["id"] == $p["id"]) {
						$existing = $existing_productions[$i];
						array_splice($existing_productions, $i, 1);
						break;
					}
				if ($existing <> null) {
					unset($p["id"]);
					$p["applicant"] = $applicant_id;
					array_push($to_update, array(array($existing["id"]),$p));
				} else {
					array_push($given_ids, $p["id"]);
					unset($p["id"]);
					$p["applicant"] = $applicant_id;
					array_push($to_insert, $p);
				}
			}
		}
		if (count($to_update) > 0)
			SQLQuery::create()->updateByKeys("SIFarmProduction", $to_update);
		if (count($to_insert) > 0)
			$new_ids = SQLQuery::create()->insertMultiple("SIFarmProduction", $to_insert);
		if (count($existing_productions) > 0)
			SQLQuery::create()->removeRows("SIFarmProduction", $existing_productions);
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