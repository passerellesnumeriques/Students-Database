<?php

class transcripts extends Component {

	public function getPluginImplementations() {
		require_once("GradesPeopleProfilePagePlugin.inc");
		return array(
			new GradesPeopleProfilePagePlugin()
		);
	}
	
	public function set_subject_grading($subject_id, $only_final_grades, $max_grade, $passing_grade) {
		SQLQuery::startTransaction();
		try {
			$current = SQLQuery::create()->select("CurriculumSubjectGrading")->whereValue("CurriculumSubjectGrading", "subject", $subject_id)->executeSingleRow();
			if ($current == null) {
				// first time the subject is configured
				SQLQuery::create()->insert("CurriculumSubjectGrading", array(
					"subject"=>$subject_id,
					"only_final_grade"=>$only_final_grades,
					"max_grade"=>$max_grade,
					"passing_grade"=>$passing_grade
				));
			} else {
				$current["only_final_grade"] = $current["only_final_grade"] == 1;
				if (!$current["only_final_grade"] && $only_final_grades) {
					// if only_final_grade is set to true, we need to remove all evaluations informatino
					$types = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "subject", $subject_id)->execute();
					SQLQuery::create()->removeRows("CurriculumSubjectEvaluationType", $types);
				}
				if (($current["only_final_grade"] && !$only_final_grades) ||
					$current["max_grade"] <> $max_grade) {
					// if only_final_grade is set to false,
					// or max_grade changed, 
					// we need to remove all final grades of the students
					$students = SQLQuery::create()->select("StudentSubjectGrade")->whereValue("StudentSubjectGrade", "subject", $subject_id)->execute();
					$keys = array();
					foreach ($students as $s)
						array_push($keys, array("people"=>$s["people"],"subject"=>$subject_id));
					SQLQuery::create()->removeKeys("StudentSubjectGrade", $keys);
				}
				// update the configuration
				SQLQuery::create()->updateByKey("CurriculumSubjectGrading", $subject_id, array(
					"only_final_grade"=>$only_final_grades,
					"max_grade"=>$max_grade,
					"passing_grade"=>$passing_grade
				));
				
				if ($current["max_grade"] <> $max_grade) {
					// max_grade changed, we need to update students grades
					if ($only_final_grades) {
						// just make sure no grade are higher
						$students = SQLQuery::create()->select("StudentSubjectGrade")->whereValue("StudentSubjectGrade", "subject", $subject_id)->execute();
						$keys = array();
						foreach ($students as $s)
							if ($s["grade"] <> null && $s["grade"] > $max_grade)
								array_push($keys, array("people"=>$s["people"], "subject"=>$subject_id));
						SQLQuery::create()->updateAllKeys("StudentSubjectGrade", $keys, array("grade"=>null));
					} else {
						// we need to re-compute all the grades
						$this->compute_subject_grades($subject_id);
					}
				}
			}
		} catch (Exception $e) {
			SQLQuery::rollbackTransaction();
			throw $e;
		}
		SQLQuery::commitTransaction();
	}
	
	public function update_students_final_grade($subject_id, $students_grades) {
		$subject_info = SQLQuery::create()->select("CurriculumSubjectGrading")->whereValue("CurriculumSubjectGrading", "subject", $subject_id)->executeSingleRow();
		if (!$subject_info["only_final_grade"])
			throw new Exception("You cannot update students final grades on a subject which is configured with evaluations: the final grade is automatically computed");
		$students_ids = array();
		foreach ($students_grades as $s) array_push($students_ids, $s["people"]);
		$existing = SQLQuery::create()
			->select("StudentSubjectGrade")
			->whereValue("StudentSubjectGrade", "subject", $subject_id)
			->whereIn("StudentSubjectGrade", "people", $students_ids)
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
				array(array("people"=>$s["people"],"subject"=>$subject_id)), // key
				array("grade"=>$grade) // value
			)); 
		}
		if (count($list) > 0)
			SQLQuery::create()->updateByKeys("StudentSubjectGrade", $list);
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
			SQLQuery::create()->insertMultiple("StudentSubjectGrade", $list);
	}
	
	public function create_evaluation_type($subject_id, $name, $weight) {
		$id = SQLQuery::create()->insert("CurriculumSubjectEvaluationType", array(
			"subject"=>$subject_id,
			"name"=>$name,
			"weight"=>$weight
		));
		// must reset all final grades
		$students = SQLQuery::create()->select("StudentSubjectGrade")->whereValue("StudentSubjectGrade", "subject", $subject_id)->execute();
		$keys = array();
		foreach ($students as $s)
			array_push($keys, array("people"=>$s["people"],"subject"=>$subject_id));
		SQLQuery::create()->updateAllKeys("StudentSubjectGrade", $keys, array("grade"=>null));
		
		return $id;
	}
	public function update_evaluation_type($type_id, $name, $weight) {
		$type = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "id", $type_id)->executeSingleRow();
		if ($type == null)
			throw new Exception("Invalid evaluation type id");
		if ($type["name"] == $name && $type["weight"] == $weight)
			return; // no change
		SQLQuery::create()->updateByKey("CurriculumSubjectEvaluationType", $type_id, array("name"=>$name,"weight"=>$weight));
		if ($type["weight"] <> $weight) {
			// weight changed, we need to recompute all grades
			$this->compute_subject_grades($type["subject"]);
		}
	}
	public function create_evaluation($type_id, $name, $weight, $max_grade) {
		$type = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "id", $type_id)->executeSingleRow();
		if ($type == null)
			throw new Exception("Invalid evaluation type id");
		$id = SQLQuery::create()->insert("CurriculumSubjectEvaluation", array(
			"type"=>$type_id,
			"name"=>$name,
			"weight"=>$weight,
			"max_grade"=>$max_grade
		));
		// we must reset all grades of this evaluation type, and the final grades
		$students = SQLQuery::create()->select("StudentSubjectEvaluationTypeGrade")->whereValue("StudentSubjectEvaluationTypeGrade", "type", $type_id)->execute();
		$keys = array();
		foreach ($students as $s)
			array_push($keys, array("people"=>$s["people"],"type"=>$type_id));
		SQLQuery::create()->updateAllKeys("StudentSubjectEvaluationTypeGrade", $keys, array("grade"=>null));
		$students = SQLQuery::create()->select("StudentSubjectGrade")->whereValue("StudentSubjectGrade", "subject", $type["subject"])->execute();
		$keys = array();
		foreach ($students as $s)
			array_push($keys, array("people"=>$s["people"],"subject"=>$type["subject"]));
		SQLQuery::create()->updateAllKeys("StudentSubjectGrade", $keys, array("grade"=>null));
		return $id;
	}
	public function update_evaluation($eval_id, $name, $weight, $max_grade) {
		$eval = SQLQuery::create()->select("CurriculumSubjectEvaluation")->whereValue("CurriculumSubjectEvaluation", "id", $eval_id)->executeSingleRow();
		if ($eval == null)
			throw new Exception("Invalid evaluation id");
		if ($eval["name"] == $name && $eval["weight"] == $weight && $eval["max_grade"] == $max_grade)
			return; // no change
		SQLQuery::create()->updateByKey("CurriculumSubjectEvaluation", $eval_id, array(
			"name"=>$name,
			"weight"=>$weight,
			"max_grade"=>$max_grade
		));
		if ($weight <> $eval["weight"] || $max_grade <> $eval["max_grade"]) {
			// we need to recompute all the grades
			$type = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "id", $eval["type"])->executeSingleRow();
			$this->compute_subject_grades($type["subject"]);
		}
	}
	
	public function remove_evaluation($subject_id, $type_id, $eval_id) {
		$subject_info = SQLQuery::create()->select("CurriculumSubjectGrading")->whereValue("CurriculumSubjectGrading", "subject", $subject_id)->executeSingleRow();
		if ($subject_info == null) throw new Exception("Invalid subject id");
		$type = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "id", $type_id)->executeSingleRow();
		if ($type == null) throw new Exception("Invalid evaluation type id");
		if ($type["subject"] <> $subject_id) throw new Exception("Subject id does not match with the subject found in the evaluation type");
		$eval = SQLQuery::create()->select("CurriculumSubjectEvaluation")->whereValue("CurriculumSubjectEvaluation", "id", $eval_id)->executeSingleRow();
		if ($eval == null) throw new Exception("Invalid evaluation id");
		if ($eval["type"] <> $type_id) throw new Exception("Evaluation type id does not match with the type found in the evaluation");
		
		SQLQuery::create()->removeKey("CurriculumSubjectEvaluation", $eval_id);
		$this->compute_subject_grades($subject_id);
	}
	public function remove_evaluation_type($subject_id, $type_id) {
		$subject_info = SQLQuery::create()->select("CurriculumSubjectGrading")->whereValue("CurriculumSubjectGrading", "subject", $subject_id)->executeSingleRow();
		if ($subject_info == null) throw new Exception("Invalid subject id");
		$type = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "id", $type_id)->executeSingleRow();
		if ($type == null) throw new Exception("Invalid evaluation type id");
		if ($type["subject"] <> $subject_id) throw new Exception("Subject id does not match with the subject found in the evaluation type");
		
		SQLQuery::create()->removeKey("CurriculumSubjectEvaluationType", $type_id);
		$this->compute_subject_grades($subject_id);
	}
	
	public function update_students_evaluation_grades($subject_id, $students_evaluations_grades) {
		$types = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "subject", $subject_id)->execute();
		$types_ids = array();
		foreach ($types as $type) array_push($types_ids, $type["id"]);
		$evaluations = SQLQuery::create()->select("CurriculumSubjectEvaluation")->whereIn("CurriculumSubjectEvaluation", "type", $types_ids)->execute();
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
		SQLQuery::create()->removeKeys("StudentSubjectEvaluationGrade", $keys_evals);
		SQLQuery::create()->removeKeys("StudentSubjectEvaluationTypeGrade", $keys_types);
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
		if (count($rows) > 0)
			SQLQuery::create()->insertMultiple("StudentSubjectEvaluationGrade", $rows);
		$this->compute_subject_grades($subject_id);
	}
	
	public function compute_subject_grades($subject_id) {
		// get subject, evaluation types, and evaluations
		$subject_info = SQLQuery::create()->select("CurriculumSubjectGrading")->whereValue("CurriculumSubjectGrading", "subject", $subject_id)->executeSingleRow();
		if ($subject_info == null) throw new Exception("Invalid subject id");
		$subject = PNApplication::$instance->curriculum->getSubjectQuery($subject_id)->executeSingleRow();
		$types = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "subject", $subject_id)->execute();
		$types_ids = array();
		foreach ($types as $type) array_push($types_ids, $type["id"]);
		$evaluations = SQLQuery::create()->select("CurriculumSubjectEvaluation")->whereIn("CurriculumSubjectEvaluation", "type", $types_ids)->execute();
		// put evaluations inside their respective types
		foreach ($types as &$type) {
			$type["evaluations"] = array();
			foreach ($evaluations as $eval)
				if ($eval["type"] == $type["id"])
					array_push($type["evaluations"], $eval);
		}
		// get all students attached to this subject, with their grades
		$classes = PNApplication::$instance->curriculum->getAcademicClassesForPeriod($subject["period"], $subject["specialization"]);
		$classes_ids = array();
		foreach ($classes as $c) array_push($classes_ids, $c["id"]);
		$students = PNApplication::$instance->students->getStudentsForClasses($classes_ids, false);
		$students_ids = array();
		foreach ($students as $s) array_push($students_ids, $s["people"]);
		$evaluations_ids = array();
		foreach ($evaluations as $eval) array_push($evaluations_ids, $eval["id"]);
		$students_grades = SQLQuery::create()
			->select("StudentSubjectEvaluationGrade")
			->whereIn("StudentSubjectEvaluationGrade", "evaluation", $evaluations_ids)
			->whereIn("StudentSubjectEvaluationGrade", "people", $students_ids)
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
		SQLQuery::create()->removeKeys("StudentSubjectEvaluationTypeGrade", $types_to_remove);
		SQLQuery::create()->insertMultiple("StudentSubjectEvaluationTypeGrade", $types_to_insert);
		SQLQuery::create()->removeKeys("StudentSubjectGrade", $final_to_remove);
		SQLQuery::create()->insertMultiple("StudentSubjectGrade", $final_to_insert);
	}
	
}
?>