<?php 
class service_eligibility_rules_get_json_all_topics extends Service {
	
	public function getRequiredRights() { return array("see_exam_subject"); }
	public function documentation() {
		echo "Get a json object containing all the topics set into the database";
	}
	public function inputDocumentation() {
		echo "<code>exclude_id</code> (number|null) the topic id to exclude when the data is retrieved";
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
		if(isset($input["exclude_id"]))
			echo SelectionJSON::getJsonAllTopics($input["exclude_id"]);
		else
			echo SelectionJSON::getJsonAllTopics();
	}
	
}