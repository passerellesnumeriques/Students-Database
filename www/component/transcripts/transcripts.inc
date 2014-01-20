<?php
require_once("component/people/PeoplePlugin.inc"); 
class transcripts extends Component implements PeoplePlugin {

	public function set_subject_grading($subject_id, $weight, $only_final_grades, $max_grade, $passing_grade) {
		SQLQuery::start_transaction();
		try {
			$current = SQLQuery::create()->select("CurriculumSubjectGrading")->where_value("CurriculumSubjectGrading", "subject", $subject_id)->execute_single_row();
			if ($current == null) {
				// first time the subject is configured
				SQLQuery::create()->insert("CurriculumSubjectGrading", array(
					"subject"=>$subject_id,
					"weight"=>$weight,
					"only_final_grade"=>$only_final_grades,
					"max_grade"=>$max_grade,
					"passing_grade"=>$passing_grade
				));
			} else {
				$current["only_final_grade"] = $current["only_final_grade"] == 1;
				if (!$current["only_final_grade"] && $only_final_grades) {
					// if only_final_grade is set to true, we need to remove all evaluations informatino
					$types = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->where_value("CurriculumSubjectEvaluationType", "subject", $subject_id)->execute();
					SQLQuery::create()->remove_rows("CurriculumSubjectEvaluationType", $types);
				}
				if (($current["only_final_grade"] && !$only_final_grades) ||
					$current["max_grade"] <> $max_grade) {
					// if only_final_grade is set to false,
					// or max_grade changed, 
					// we need to remove all final grades of the students
					$students = SQLQuery::create()->select("StudentSubjectGrade")->where_value("StudentSubjectGrade", "subject", $subject_id)->execute();
					$keys = array();
					foreach ($students as $s)
						array_push($keys, array("people"=>$s["people"],"subject"=>$subject_id));
					SQLQuery::create()->remove_keys("StudentSubjectGrade", $keys);
				}
				// update the configuration
				SQLQuery::create()->update_by_key("CurriculumSubjectGrading", $subject_id, array(
					"weight"=>$weight,
					"only_final_grade"=>$only_final_grades,
					"max_grade"=>$max_grade,
					"passing_grade"=>$passing_grade
				));
				
				if ($current["max_grade"] <> $max_grade) {
					// max_grade changed, we need to update students grades
					if ($only_final_grades) {
						// just make sure no grade are higher
						$students = SQLQuery::create()->select("StudentSubjectGrade")->where_value("StudentSubjectGrade", "subject", $subject_id)->execute();
						$keys = array();
						foreach ($students as $s)
							if ($s["grade"] <> null && $s["grade"] > $max_grade)
								array_push($keys, array("people"=>$s["people"], "subject"=>$subject_id));
						SQLQuery::create()->update_all_keys("StudentSubjectGrade", $keys, array("grade"=>null));
					} else {
						// we need to re-compute all the grades
						$this->compute_subject_grades($subject_id);
					}
				}
			}
		} catch (Exception $e) {
			SQLQuery::cancel_transaction();
			throw $e;
		}
		SQLQuery::end_transaction();
	}
	
	public function update_students_final_grade($subject_id, $students_grades) {
		$subject_info = SQLQuery::create()->select("CurriculumSubjectGrading")->where_value("CurriculumSubjectGrading", "subject", $subject_id)->execute_single_row();
		if (!$subject_info["only_final_grade"])
			throw new Exception("You cannot update students final grades on a subject which is configured with evaluations: the final grade is automatically computed");
		$students_ids = array();
		foreach ($students_grades as $s) array_push($students_ids, $s["people"]);
		$existing = SQLQuery::create()
			->select("StudentSubjectGrade")
			->where_value("StudentSubjectGrade", "subject", $subject_id)
			->where_in("StudentSubjectGrade", "people", $students_ids)
			->execute();
		$list = array();
		foreach ($existing as $s) {
			$found = false;
			for ($i = 0; $i < count($students_ids); $i++) {
				if ($students_ids[$i] == $s["people"]) {
					$found = true;
					array_splice($students_ids, $i, 1);
					break;
				}
			}
			if (!$found) continue;
			$grade = null;
			foreach ($students_grades as $sg) if ($sg["people"] == $s["people"]) { $grade = $sg["final_grade"]; break; }
			array_push($list, array(
				array("people"=>$s["people"],"subject"=>$subject_id),
				array("grade"=>$grade)
			)); 
		}
		if (count($list) > 0)
			SQLQuery::create()->update_by_keys("StudentSubjectGrade", $list);
		// remaining ids are new
		$list = array();
		foreach ($students_ids as $people_id) {
			$grade = null;
			foreach ($students_grades as $sg) if ($sg["people"] == $people_id) { $grade = $sg["final_grade"]; break; }
			array_push($list, array(
				"people"=>$people_id,
				"subject"=>$subject_id,
				"grade"=>$grade
			));
		}
		if (count($list) > 0)
			SQLQuery::create()->insert_multiple("StudentSubjectGrade", $list);
	}
	
	public function create_evaluation_type($subject_id, $name, $weight) {
		$id = SQLQuery::create()->insert("CurriculumSubjectEvaluationType", array(
			"subject"=>$subject_id,
			"name"=>$name,
			"weight"=>$weight
		));
		// must reset all final grades
		$students = SQLQuery::create()->select("StudentSubjectGrade")->where_value("StudentSubjectGrade", "subject", $subject_id)->execute();
		$keys = array();
		foreach ($students as $s)
			array_push($keys, array("people"=>$s["people"],"subject"=>$subject_id));
		SQLQuery::create()->update_all_keys("StudentSubjectGrade", $keys, array("grade"=>null));
		
		return $id;
	}
	public function update_evaluation_type($type_id, $name, $weight) {
		$type = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->where_value("CurriculumSubjectEvaluationType", "id", $type_id)->execute_single_row();
		if ($type == null)
			throw new Exception("Invalid evaluation type id");
		if ($type["name"] == $name && $type["weight"] == $weight)
			return; // no change
		SQLQuery::create()->update_by_key("CurriculumSubjectEvaluationType", $type_id, array("name"=>$name,"weight"=>$weight));
		if ($type["weight"] <> $weight) {
			// weight changed, we need to recompute all grades
			$this->compute_subject_grades($type["subject"]);
		}
	}
	public function create_evaluation($type_id, $name, $weight, $max_grade) {
		$type = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->where_value("CurriculumSubjectEvaluationType", "id", $type_id)->execute_single_row();
		if ($type == null)
			throw new Exception("Invalid evaluation type id");
		$id = SQLQuery::create()->insert("CurriculumSubjectEvaluation", array(
			"type"=>$type_id,
			"name"=>$name,
			"weight"=>$weight,
			"max_grade"=>$max_grade
		));
		// we must reset all grades of this evaluation type, and the final grades
		$students = SQLQuery::create()->select("StudentSubjectEvaluationTypeGrade")->where_value("StudentSubjectEvaluationTypeGrade", "type", $type_id)->execute();
		$keys = array();
		foreach ($students as $s)
			array_push($keys, array("people"=>$s["people"],"type"=>$type_id));
		SQLQuery::create()->update_all_keys("StudentSubjectEvaluationTypeGrade", $keys, array("grade"=>null));
		$students = SQLQuery::create()->select("StudentSubjectGrade")->where_value("StudentSubjectGrade", "subject", $type["subject"])->execute();
		$keys = array();
		foreach ($students as $s)
			array_push($keys, array("people"=>$s["people"],"subject"=>$type["subject"]));
		SQLQuery::create()->update_all_keys("StudentSubjectGrade", $keys, array("grade"=>null));
		return $id;
	}
	public function update_evaluation($eval_id, $name, $weight, $max_grade) {
		$eval = SQLQuery::create()->select("CurriculumSubjectEvaluation")->where_value("CurriculumSubjectEvaluation", "id", $eval_id)->execute_single_row();
		if ($eval == null)
			throw new Exception("Invalid evaluation id");
		if ($eval["name"] == $name && $eval["weight"] == $weight && $eval["max_grade"] == $max_grade)
			return; // no change
		SQLQuery::create()->update_by_key("CurriculumSubjectEvaluation", $eval_id, array(
			"name"=>$name,
			"weight"=>$weight,
			"max_grade"=>$max_grade
		));
		if ($weight <> $eval["weight"] || $max_grade <> $eval["max_grade"]) {
			// we need to recompute all the grades
			$type = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->where_value("CurriculumSubjectEvaluationType", "id", $eval["type"])->execute_single_row();
			$this->compute_subject_grades($type["subject"]);
		}
	}
	
	public function remove_evaluation($subject_id, $type_id, $eval_id) {
		$subject_info = SQLQuery::create()->select("CurriculumSubjectGrading")->where_value("CurriculumSubjectGrading", "subject", $subject_id)->execute_single_row();
		if ($subject_info == null) throw new Exception("Invalid subject id");
		$type = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->where_value("CurriculumSubjectEvaluationType", "id", $type_id)->execute_single_row();
		if ($type == null) throw new Exception("Invalid evaluation type id");
		if ($type["subject"] <> $subject_id) throw new Exception("Subject id does not match with the subject found in the evaluation type");
		$eval = SQLQuery::create()->select("CurriculumSubjectEvaluation")->where_value("CurriculumSubjectEvaluation", "id", $eval_id)->execute_single_row();
		if ($eval == null) throw new Exception("Invalid evaluation id");
		if ($eval["type"] <> $type_id) throw new Exception("Evaluation type id does not match with the type found in the evaluation");
		
		SQLQuery::create()->remove_key("CurriculumSubjectEvaluation", $eval_id);
		$this->compute_subject_grades($subject_id);
	}
	public function remove_evaluation_type($subject_id, $type_id) {
		$subject_info = SQLQuery::create()->select("CurriculumSubjectGrading")->where_value("CurriculumSubjectGrading", "subject", $subject_id)->execute_single_row();
		if ($subject_info == null) throw new Exception("Invalid subject id");
		$type = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->where_value("CurriculumSubjectEvaluationType", "id", $type_id)->execute_single_row();
		if ($type == null) throw new Exception("Invalid evaluation type id");
		if ($type["subject"] <> $subject_id) throw new Exception("Subject id does not match with the subject found in the evaluation type");
		
		SQLQuery::create()->remove_key("CurriculumSubjectEvaluationType", $type_id);
		$this->compute_subject_grades($subject_id);
	}
	
	public function update_students_evaluation_grades($subject_id, $students_evaluations_grades) {
		$types = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->where_value("CurriculumSubjectEvaluationType", "subject", $subject_id)->execute();
		$types_ids = array();
		foreach ($types as $type) array_push($types_ids, $type["id"]);
		$evaluations = SQLQuery::create()->select("CurriculumSubjectEvaluation")->where_in("CurriculumSubjectEvaluation", "type", $types_ids)->execute();
		// remove all previous grades for the given students
		$keys_types = array();
		$keys_evals = array();
		foreach ($students_evaluations_grades as $s) {
			$people_id = $s["people"];
			foreach ($types as $type)
				array_push($keys_types, array("people"=>$people_id,"type"=>$type["id"]));
			foreach ($evaluations as $eval)
				array_push($keys_evals, array("people"=>$people_id,"evaluation"=>$eval["id"]));
		}
		SQLQuery::create()->remove_keys("StudentSubjectEvaluationGrade", $keys_evals);
		SQLQuery::create()->remove_keys("StudentSubjectEvaluationTypeGrade", $keys_types);
		$rows = array();
		foreach ($students_evaluations_grades as $s) {
			foreach ($s["grades"] as $eval_grade) {
				array_push($rows, array(
					"people"=>$s["people"],
					"evaluation"=>$eval_grade["evaluation"],
					"grade"=>$eval_grade["grade"]
				));
			}
		}
		SQLQuery::create()->insert_multiple("StudentSubjectEvaluationGrade", $rows);
		$this->compute_subject_grades($subject_id);
	}
	
	public function compute_subject_grades($subject_id) {
		// get subject, evaluation types, and evaluations
		$subject_info = SQLQuery::create()->select("CurriculumSubjectGrading")->where_value("CurriculumSubjectGrading", "subject", $subject_id)->execute_single_row();
		if ($subject_info == null) throw new Exception("Invalid subject id");
		$subject = SQLQuery::create()->select("CurriculumSubject")->where_value("CurriculumSubject", "id", $subject_id)->execute_single_row();
		$types = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->where_value("CurriculumSubjectEvaluationType", "subject", $subject_id)->execute();
		$types_ids = array();
		foreach ($types as $type) array_push($types_ids, $type["id"]);
		$evaluations = SQLQuery::create()->select("CurriculumSubjectEvaluation")->where_in("CurriculumSubjectEvaluation", "type", $types_ids)->execute();
		// put evaluations inside their respective types
		foreach ($types as &$type) {
			$type["evaluations"] = array();
			foreach ($evaluations as $eval)
				if ($eval["type"] == $type["id"])
					array_push($type["evaluations"], $eval);
		}
		// get all students attached to this subject, with their grades
		$classes = SQLQuery::create()
			->select("AcademicClass")
			->where_value("AcademicClass", "period", $subject["period"])
			->where_value("AcademicClass", "specialization", $subject["specialization"])
			->execute();
		$classes_ids = array();
		foreach ($classes as $c) array_push($classes_ids, $c["id"]);
		$students = SQLQuery::create()
			->select("StudentClass")
			->where_in("StudentClass", "class", $classes_ids)
			->execute();
		$students_ids = array();
		foreach ($students as $s) array_push($students_ids, $s["people"]);
		$evaluations_ids = array();
		foreach ($evaluations as $eval) array_push($evaluations_ids, $eval["id"]);
		$students_grades = SQLQuery::create()
			->select("StudentSubjectEvaluationGrade")
			->where_in("StudentSubjectEvaluationGrade", "evaluation", $evaluations_ids)
			->where_in("StudentSubjectEvaluationGrade", "people", $students_ids)
			->execute();
		// compute grades, for each student
		$types_to_remove = array();
		$types_to_insert = array();
		$final_to_remove = array();
		$final_to_insert = array();
		foreach ($students as $student) {
			$final_grade = 0;
			$final_weights = 0;
			foreach ($types as &$type) {
				$type_grade = 0;
				$type_weights = 0;
				foreach ($type["evaluations"] as $eval) {
					// look for the student grade
					$eg = null;
					foreach ($students_grades as $sg)
						if ($sg["people"] == $student["people"] && $sg["evaluation"] == $eval["id"]) {
							$eg = $sg;
							break;
						}
					if ($eg == null || $eg["grade"] === null) {
						// no grade, we cannot compute
						$type_grade = null;
						break;
					} else {
						$type_grade += $eg["grade"] * 100 / $eval["max_grade"] * $eval["weight"];
						$type_weights += $eval["weight"];
					}
				}
				if ($type_weights == 0) $type_grade = null;
				if ($type_grade === null) {
					$final_grade = null;
				} else {
					$type_grade = $type_grade * $subject_info["max_grade"] / 100 / $type_weights;
					if ($final_grade !== null) {
						$final_grade += $type_grade * $type["weight"];
						$final_weights += $type["weight"];
					}
				}
				// set the type grade
				array_push($types_to_remove, array("people"=>$student["people"],"type"=>$type["id"]));
				array_push($types_to_insert, array("people"=>$student["people"],"type"=>$type["id"],"grade"=>$type_grade));
			}
			if ($final_weights == 0) $final_grade = null;
			if ($final_grade !== null)
				$final_grade /= $final_weights;
			// set the final grade
			array_push($final_to_remove, array("people"=>$student["people"],"subject"=>$subject_id));
			array_push($final_to_insert, array("people"=>$student["people"],"subject"=>$subject_id,"grade"=>$final_grade));
		}
		SQLQuery::create()->remove_keys("StudentSubjectEvaluationTypeGrade", $types_to_remove);
		SQLQuery::create()->insert_multiple("StudentSubjectEvaluationTypeGrade", $types_to_insert);
		SQLQuery::create()->remove_keys("StudentSubjectGrade", $final_to_remove);
		SQLQuery::create()->insert_multiple("StudentSubjectGrade", $final_to_insert);
	}
	
	/* PeoplePlugin */

	public function preparePeopleProfilePagesRequest(&$q, $people_id) {
		$alias = $q->table_id();
		$people_alias = $q->get_table_alias("People");
		$q->join($people_alias, "Student", array("id"=>"people"), $alias);
		$q->field($alias, "batch");
	}
	public function getPeopleProfilePages($people_id, $people, $q) {
		if ($people["batch"] == null) return array(); // not a student
		return array(
			"grades"=>array("/static/transcripts/grades.gif", "Grades", "/dynamic/transcripts/page/student_grades?people=".$people_id, 30),
		);
	}
	public function getPeopleProfileGeneralInfoSections($people_id, $people, $q) { return array(); }

	
	public function getCreatePeopleSupportedTypes() {}
	public function isCreatePeopleAllowed($people_type) {}
	public function getCreatePeoplePages($people_types) {}
	public function createPeople($people_id, $people_types, $input, &$create_data) {}
	public function rollbackCreatePeople($people_id, $people_types, $input, &$create_data) {}
	
	public function getPeopleImportDataDisplay() {}
	public function importPeopleData($input, $people_id) {}

	public function filterPeopleReadAccess(&$q, $people_table_alias) { return null; }
	public function prepareSelectPeopleWriteAccess(&$q, $people_table_alias) {}
	public function filterPeopleWriteAccess($people_rows) { return $people_rows; }
	public function prepareSelectPeopleRemoveAccess(&$q, $people_table_alias, &$locks) {}
	public function filterPeopleRemoveAccess($people_rows) { return $people_rows; }
}
?>