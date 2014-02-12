<?php 
class service_eligibility_rules_save_topic extends Service {
	
	public function get_required_rights() { return array(); }//TODO
	public function documentation() {}//TODO
	public function input_documentation() {
	//TODO
	}
	public function output_documentation() {
		//TODO
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		if(isset($input["topic"]) && isset($input["db_lock"])){
			$db_parts = array();
			$db_topic = array();
			$db_full_subjects = array();
			
			//Get the data for ExamTopicForEligibilityRule table
			$db_topic["id"] = $input["topic"]["id"];
			$db_topic["name"] = $input["topic"]["name"];
			$db_topic["max_score"] = $input["topic"]["max_score"];
			$db_topic["number_questions"] = $input["topic"]["number_questions"];
			
			//Get all the parts
			foreach ($input["topic"]["subjects"] as $subject){
				if($subject["full_subject"]) //add to full subjects list
					array_push($db_full_subjects, $subject["id"]);
				foreach ($subject["parts"] as $part){
					array_push($db_parts, $part["id"]);
				}
			}
			
// 			//Call the save method
			$id = PNApplication::$instance->selection->saveTopic($db_topic, $db_parts, $db_full_subjects, $input["db_lock"]);
			if($id)
				echo SelectionJSON::ExamTopicForEligibilityRulesFromID($id);
			else
				echo "false";
		} else {
			echo "false";
		}
	}
	
}