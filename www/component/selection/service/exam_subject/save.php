<?php 
class service_exam_subject_save extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		if(isset($input["exam"])){
			//prepare the inputs
			$rows_exam_table = array();
			$rows_parts_table = array();
			$questions_by_new_part = array();
			$questions_by_old_part = array();
			$parts_to_insert_indexes = array();
			
			$id = $input["exam"]["id"];
			$rows_exam_table["name"] = $input["exam"]["name"];
			$rows_exam_table["max_score"] = $input["exam"]["max_score"];
			
			if(isset($input["exam"]["parts"])){
				foreach($input["exam"]["parts"] as $part){
					array_push($rows_parts_table, array(
						"id" => $part["id"],
						"exam_subject" => $id,
						"index" => $part["index"],
						"max_score" => $part["max_score"],
						"name" => $part["name"]
					));
					
					if(isset($part["questions"]) && count($part["questions"]) > 0){
						foreach($part["questions"] as $q){
							if($part["id"] == -1 || $part["id"] == "-1"){
								if(!isset($questions_by_new_part[$part["index"]][0])){
									$questions_by_new_part[$part["index"]] = array();
									array_push($parts_to_insert_indexes,$part["index"]); // must store the index because the parts can be set not in order in the exam object
								}
								array_push($questions_by_new_part[$part["index"]],array(
									"exam_subject_part" => $part["id"],
									"index" => $q["index"],
									"max_score" => $q["max_score"],
									"correct_answer" => $q["correct_answer"],
									"choices" => $q["choices"],
								));
								
							} else {
								if(!isset($questions_by_old_part[$part["id"]][0]))
									$questions_by_old_part[$part["id"]] = array();
								array_push($questions_by_old_part[$part["id"]],array(
									"exam_subject_part" => $part["id"],
									"index" => $q["index"],
									"max_score" => $q["max_score"],
									"correct_answer" => $q["correct_answer"],
									"choices" => $q["choices"],
								));
							}
						}
					}
				}
			}
			$subject = PNApplication::$instance->selection->saveExam($id, $rows_exam_table, $rows_parts_table, $questions_by_old_part, $questions_by_new_part, $parts_to_insert_indexes);
			if(PNApplication::has_errors())
				echo "false";
			else if($subject <> null)
				echo $subject;
			else
				echo "false";
		} else echo "false";
	}
	
}
?>