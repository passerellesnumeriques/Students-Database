<?php 
class service_eligibility_rules_save_topic extends Service {
	
	public function get_required_rights() { return array("manage_exam_subject"); }
	public function documentation() {echo "Save an existing or a new exam topic for eligibility rules into the database";}
	public function input_documentation() {
		?>
		<ul>
		<li> <code>topic</code> JSON topic object to save</li>
		<li><code>db_lock</code> the database lock id (when the topics are managed, all the ExamTopicForEligibilityRule table must be locked)</li>
		</ul>
		<?php
	}
	public function output_documentation() {
		?>
		<ul>
		<li><code>topic</code> a new topic JSON object if the saving was well performed</li>
		<li><code>false</code> if any error occured</li>
		</ul>
		<?php
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
			
			if($db_topic["id"] != -1 && $db_topic["id"] != "-1"){
				$parts_from_db = SQLQuery::create()
					->select("ExamPartTopic")
					->field("ExamPartTopic","exam_subject_part","id")
					->whereValue("ExamPartTopic", "exam_topic_for_eligibility_rule", $db_topic["id"])
					->executeSingleField();
				$parts_to_remove = array();
				$parts_to_add = array();
				
				//Get the parts to remove
				foreach($parts_from_db as $p){
					if(!in_array($p, $db_parts))
						array_push($parts_to_remove, $p);
				}
				//Get the parts to add
				foreach ($db_parts as $p){
					if(!in_array($p, $parts_from_db))
						array_push($parts_to_add, $p);
				}
				//For the other parts, nothing to do
					
				//Call the save method
				$id = PNApplication::$instance->selection->saveTopic($db_topic, $parts_to_add, $parts_to_remove, $db_full_subjects, $input["db_lock"]);
			} else {
				//all the parts must be added
				
				//Call the save method
				$id = PNApplication::$instance->selection->saveTopic($db_topic, $db_parts, array(), $db_full_subjects, $input["db_lock"]);
			}
			
			
			if($id)
				echo SelectionJSON::ExamTopicForEligibilityRulesFromID($id);
			else
				echo "false";
		} else {
			echo "false";
		}
	}
	
}