<?php 
class service_eligibility_rules_save_rules extends Service {
	
	public function get_required_rights() {}//TODO
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
		require_once("component/selection/SelectionJSON.inc");
		if(isset($input["all_rules"]) && isset($input["db_lock"])){
			$rules_to_insert = array();
			$rules_to_update = array();
			$root_rule_id = null;
			foreach($input["all_rules"] as $rule){
				//Get the root rule (can only have one)
				if($rule["id"] == -1 || $rule["id"] == "-1"){
					//This is an insert
					unset($rule["id"]);
					$topics_to_insert = $this->getAllTopicsFieldsValues($rule);
					array_push($rules_to_insert, array(
					"rule_table" => SelectionJSON::EligibilityRule2DB($rule),
					"rule_topic_table_insert" => $topics_to_insert
					));
				} else {
					//This is an update
					//Get the topics already set to this rule
					$topics_in_db = SQLQuery::create()
						->bypassSecurity()
						->select("EligibilityRuleExamTopic")
						->field("EligibilityRuleExamTopic","exam_topic_for_eligibility_rule","topic")
						->whereValue("EligibilityRuleExamTopic", "eligibility_rule", $rule["id"])
						->executeSingleField();
					if($topics_in_db <> null){
						$topics_to_insert = array();
						$topics_to_remove = array();
						$topics_to_update = array();
						$all_topics_to_set_in_db = array();
						foreach($rule["topics"] as $topic){
							if(in_array($topic["topic"]["id"], $topics_in_db))
								array_push($topics_to_update, SelectionJSON::EligibilityRuleTopic2DB($topic));
							else
								array_push($topics_to_insert, SelectionJSON::EligibilityRuleTopic2DB($topic,$rule["id"]));
							array_push($all_topics_to_set_in_db, $topic["topic"]["id"]);
						}
// 						var_dump($topics_to_update);
						foreach ($topics_in_db as $t){
							if(!in_array($t,$all_topics_to_set_in_db))
								array_push($topics_to_remove, $t);
						}
						array_push($rules_to_update, array(
							"rule_table" => SelectionJSON::EligibilityRule2DB($rule),
							"rule_topic_table_insert" => $topics_to_insert,
							"rule_topic_table_update" => $topics_to_update,
							"rule_topic_table_remove" => $topics_to_remove,
						));
						
					} else {//All the topics must be inserted
						$topics_to_insert = $this->getAllTopicsFieldsValues($rule,$rule["id"]);
						array_push($rules_to_update, array(
							"rule_table" => SelectionJSON::EligibilityRule2DB($rule),
							"rule_topic_table_insert" => $topics_to_insert
						));
					}
				}
			}
// 			var_dump($rules_to_insert);
// 			var_dump($rules_to_update);
			$saved = PNApplication::$instance->selection->saveRules($rules_to_update, $rules_to_insert, $input["db_lock"]);
// 			var_dump($saved);
			if($saved)
				echo SelectionJSON::getJSONAllEligibilityRules();
			else 
				echo "false";
		} else 
			echo "false";
	}
	
	private function getAllTopicsFieldsValues($rule,$id = null){
		$topics = array();
		foreach($rule["topics"] as $t){//Collect all the topics
			array_push($topics, SelectionJSON::EligibilityRuleTopic2DB($t,$id));
		}
		return $topics;
	}
	
}