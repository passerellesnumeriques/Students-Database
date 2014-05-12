<?php 
class service_eligibility_rules_get_topic extends Service {
	
	public function getRequiredRights() { return array("see_exam_subject"); }
	public function documentation() {
		echo "Get a json topic object";
	}
	public function inputDocumentation() {
		echo "<code>id</code> (number) the topic id";
	}
	public function outputDocumentation() {
		echo "JSON topic object if could be retrieved, else return false";
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		require_once("component/selection/SelectionJSON.inc");
		if(isset($input["id"])){
			$topic = SelectionJSON::ExamTopicForEligibilityRulesFromID($input["id"]);
			if($topic)
				echo $topic;
			else
				echo "false";
		} else
			echo "false";
	}
	
}