<?php 
class service_si_save_fishing extends Service {
	
	public function getRequiredRights() { return array("edit_social_investigation"); }
	
	public function documentation() { echo "Save fishing information"; }
	public function inputDocumentation() { echo "applicant, fishing"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		
		$applicant_id = $input["applicant"];
		$info = @$input["fishing"];
		
		$has_fishing = $info <> null && ($info["boat"] <> null || $info["net"] <> null || $info["income"] > 0 || $info["income_freq"] <> null || $info["other"] <> null);
		
		$existing = SQLQuery::create()->select("SIFishing")->whereValue("SIFishing","applicant",$applicant_id)->executeSingleRow();
		if ($existing == null) {
			if ($has_fishing) {
				// create
				$info["applicant"] = $applicant_id;
				SQLQuery::create()->insert("SIFishing", $info);
			}
		} else if ($has_fishing) {
			unset($info["applicant"]);
			SQLQuery::create()->updateByKey("SIFishing", $applicant_id, $info);
		} else
			SQLQuery::create()->removeKey("SIFishing", $applicant_id);
		

		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>