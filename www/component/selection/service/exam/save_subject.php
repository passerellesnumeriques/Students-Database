<?php 
require_once("component/selection/SelectionJSON.inc");
class service_exam_save_subject extends Service {
	
	public function get_required_rights() { return array("manage_exam_subject"); }
	public function documentation() {
		echo "Save / insert an exam subject object into the database";
	}
	public function input_documentation() {
		echo "<code>exam</code> the exam subject JSON structure. <br/>Notes:";
		?>
		<ul>
			<li>all the ids set to -1 (subject, part, question) are considered as new by this service, so are inserted into the DB instead of updated</li>
			<li>The questions are always removed first from DB then the input questions are inserted (no update)</li>
		</ul>
		<?php
	}
	public function output_documentation() {
		?>
		<ul>
			<li><code>false</code> if an error occured</li>
			<li><code>exam_subject</code> else exam_subject object</li>
		</ul>
		<?php
	}
	
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
			if($id == -1 || $id == "-1")
				unset($input["exam"]["id"]);
			$rows_exam_table = SelectionJSON::ExamSubject2DB($input["exam"]);
			
			if(isset($input["exam"]["parts"])){
				foreach($input["exam"]["parts"] as $part){
					$part["exam_subject"] = $id;
					$array_part = SelectionJSON::ExamSubjectPart2DB($part);
					array_push($rows_parts_table,$array_part); 					
					if(isset($part["questions"]) && count($part["questions"]) > 0){
						foreach($part["questions"] as $q){
							if(isset($q["id"]))
								unset($q["id"]);//The method saveExam need questions without ids
							$q["exam_subject_part"] = $part["id"];
							$array_question = SelectionJSON::ExamSubjectQuestion2DB($q);
							if($part["id"] == -1 || $part["id"] == "-1"){
								if(!isset($questions_by_new_part[$part["index"]][0])){
									$questions_by_new_part[$part["index"]] = array();
									array_push($parts_to_insert_indexes,$part["index"]); // must store the index because the parts can be set not in order in the exam object
								}
								array_push($questions_by_new_part[$part["index"]],$array_question);
							} else {
								if(!isset($questions_by_old_part[$part["id"]][0]))
									$questions_by_old_part[$part["id"]] = array();
								array_push($questions_by_old_part[$part["id"]], $array_question);
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