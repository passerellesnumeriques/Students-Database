<?php 
class service_save_subject_evaluations extends Service {
	
	public function getRequiredRights() { return array(); } // TODO
	
	public function documentation() { echo "Save subject grading information, including students' grades"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>subject_id</code>: id of the subject</li>";
		echo "<li><code>types</code>: corresponds to the CurriculumSubjectEvaluationType table, including <code>evaluations</code> corresponding to the CurriculumSubjectEvaluation table. Every id less than 0 is considered as a new entry, else an entry to update</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { 
		echo "On success, returns <code>types</code> and <code>evaluations</code>, as a mapping of new ids: <code>[{input_id,output_id},...]</code> where input_id is a given id less than 0, and output_id is the id of the created entry in database."; 
	}
	
	public function execute(&$component, $input) {
		set_time_limit(120);
		SQLQuery::startTransaction();
		$subject = SQLQuery::create()->select("CurriculumSubjectGrading")->whereValue("CurriculumSubjectGrading", "subject", $input["subject_id"])->executeSingleRow();
		if ($subject == null) {
			PNApplication::error("No information about this subject regarding grades");
			SQLQuery::commitTransaction();
			return;
		}
		if ($subject["only_final_grade"] == 1) {
			PNApplication::error("This subject is configured to have only final grades: you cannot change the evaluations");
			SQLQuery::commitTransaction();
			return;
		}
		$output_types_ids = array();
		$output_evaluations_ids = array();
		// update list of evaluations
		$existing_types = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "subject", $input["subject_id"])->execute();
		foreach ($input["types"] as $type) {
			if ($type["id"] < 0) {
				// new evaluation type
				$id = $component->create_evaluation_type($input["subject_id"], $type["name"], $type["weight"]);
				array_push($output_types_ids, array("input_id"=>$type["id"],"output_id"=>$id));
				$type["id"] = $id;
			} else {
				$component->update_evaluation_type($type["id"], $type["name"], $type["weight"]);
				for ($i = 0; $i < count($existing_types); $i++)
					if ($existing_types[$i]["id"] == $type["id"]) {
						array_splice($existing_types, $i, 1);
						break;
					}
			}
			set_time_limit(120);
			$existing_evaluations = SQLQuery::create()->select("CurriculumSubjectEvaluation")->whereValue("CurriculumSubjectEvaluation", "type", $type["id"])->execute();
			foreach ($type["evaluations"] as $eval) {
				if ($eval["id"] < 0) {
					// new evaluation
					$id = $component->create_evaluation($type["id"], $eval["name"], $eval["weight"], $eval["max_grade"]);
					array_push($output_evaluations_ids, array("input_id"=>$eval["id"],"output_id"=>$id));
					$eval["id"] = $id;
				} else {
					$component->update_evaluation($eval["id"], $eval["name"], $eval["weight"], $eval["max_grade"]);
					for ($i = 0; $i < count($existing_evaluations); $i++)
						if ($existing_evaluations[$i]["id"] == $eval["id"]) {
							array_splice($existing_evaluations, $i, 1);
							break;
						}
				}
			}
			// remove remaining evaluations
			foreach ($existing_evaluations as $eval)
				$component->remove_evaluation($input["subject_id"],$type["id"], $eval["id"]);
		}
		set_time_limit(120);
		// remove remainig types
		foreach ($existing_types as $type)
			$component->remove_evaluation_type($input["subject_id"],$type["id"]);
		set_time_limit(120);
		SQLQuery::commitTransaction();
		echo "{types:".json_encode($output_types_ids).",evaluations:".json_encode($output_evaluations_ids)."}";
	}
	
}
?>