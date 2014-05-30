<?php 
class service_eligibility_rules_get_json_all_rules extends Service {
	
	public function getRequiredRights() { return array("see_exam_subject"); }
	public function documentation() {
		echo "Get a json object containing all the eligibility rules set into the database";
	}
	public function inputDocumentation() {
		echo "No";
	}
	public function outputDocumentation() {
		echo "{array} containing all the retrieved JSON topic objects";
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		require_once("component/selection/SelectionJSON.inc");
		echo SelectionJSON::getJSONAllEligibilityRules();
	}
	
}