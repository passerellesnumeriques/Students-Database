<?php

class transcripts extends Component {

	public function getPluginImplementations() {
		require_once("GradesPeopleProfilePagePlugin.inc");
		require_once("TranscriptsNewsPlugin.inc");
		return array(
			new GradesPeopleProfilePagePlugin(),
			new TranscriptsStudentsPlugin()
		);
	}
	
	public function set_subject_grading($subject_id, $only_final_grades, $max_grade, $passing_grade) {
		SQLQuery::startTransaction();
		try {
			$current = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectGrading")->whereValue("CurriculumSubjectGrading", "subject", $subject_id)->executeSingleRow();
			if ($current == null) {
				// first time the subject is configured
				SQLQuery::create()->bypassSecurity()->insert("CurriculumSubjectGrading", array(
					"subject"=>$subject_id,
					"only_final_grade"=>$only_final_grades,
					"max_grade"=>$max_grade,
					"passing_grade"=>$passing_grade
				));
			} else {
				$current["only_final_grade"] = $current["only_final_grade"] == 1;
				if (!$current["only_final_grade"] && $only_final_grades) {
					// if only_final_grade is set to true, we need to remove all evaluations informatino
					$types = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "subject", $subject_id)->execute();
					SQLQuery::create()->bypassSecurity()->removeRows("CurriculumSubjectEvaluationType", $types);
				}
				if (($current["only_final_grade"] && !$only_final_grades) ||
					$current["max_grade"] <> $max_grade) {
					// if only_final_grade is set to false,
					// or max_grade changed, 
					// we need to remove all final grades of the students
					$students = SQLQuery::create()->bypassSecurity()->select("StudentSubjectGrade")->whereValue("StudentSubjectGrade", "subject", $subject_id)->execute();
					$keys = array();
					foreach ($students as $s)
						array_push($keys, array("people"=>$s["people"],"subject"=>$subject_id));
					SQLQuery::create()->bypassSecurity()->removeKeys("StudentSubjectGrade", $keys);
				}
				// update the configuration
				SQLQuery::create()->bypassSecurity()->updateByKey("CurriculumSubjectGrading", $subject_id, array(
					"only_final_grade"=>$only_final_grades,
					"max_grade"=>$max_grade,
					"passing_grade"=>$passing_grade
				));
				
				if ($current["max_grade"] <> $max_grade) {
					// max_grade changed, we need to update students grades
					if ($only_final_grades) {
						// just make sure no grade are higher
						$students = SQLQuery::create()->bypassSecurity()->select("StudentSubjectGrade")->whereValue("StudentSubjectGrade", "subject", $subject_id)->execute();
						$keys = array();
						foreach ($students as $s)
							if ($s["grade"] <> null && $s["grade"] > $max_grade)
								array_push($keys, array("people"=>$s["people"], "subject"=>$subject_id));
						SQLQuery::create()->bypassSecurity()->updateAllKeys("StudentSubjectGrade", $keys, array("grade"=>null));
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
		$subject_info = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectGrading")->whereValue("CurriculumSubjectGrading", "subject", $subject_id)->executeSingleRow();
		if (!$subject_info["only_final_grade"])
			throw new Exception("You cannot update students final grades on a subject which is configured with evaluations: the final grade is automatically computed");
		$students_ids = array();
		foreach ($students_grades as $s) array_push($students_ids, $s["people"]);
		$existing = SQLQuery::create()->bypassSecurity()
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
			SQLQuery::create()->bypassSecurity()->updateByKeys("StudentSubjectGrade", $list);
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
			SQLQuery::create()->bypassSecurity()->insertMultiple("StudentSubjectGrade", $list);
	}

	public function update_students_comments($subject_id, $students_comments) {
		$students_ids = array();
		foreach ($students_comments as $s) array_push($students_ids, $s["people"]);
		$existing = SQLQuery::create()->bypassSecurity()
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
			$comment = null;
			foreach ($students_comments as $sg) if ($sg["people"] == $s["people"]) { $comment = $sg["comment"]; break; }
			array_push($list, array(
				array(array("people"=>$s["people"],"subject"=>$subject_id)), // key
				array("comment"=>$comment) // value
			));
		}
		if (count($list) > 0)
			SQLQuery::create()->bypassSecurity()->updateByKeys("StudentSubjectGrade", $list);
		// remaining ids are new
		$list = array();
		foreach ($students_ids as $people_id) {
			$comment = null;
			foreach ($students_comments as $sg) if ($sg["people"] == $people_id) { $comment = $sg["comment"]; break; }
			array_push($list, array(
				"people"=>$people_id,
				"subject"=>$subject_id,
				"comment"=>$comment
			));
		}
		if (count($list) > 0)
			SQLQuery::create()->bypassSecurity()->insertMultiple("StudentSubjectGrade", $list);
	}
	
	public function create_evaluation_type($subject_id, $name, $weight) {
		$id = SQLQuery::create()->bypassSecurity()->insert("CurriculumSubjectEvaluationType", array(
			"subject"=>$subject_id,
			"name"=>$name,
			"weight"=>$weight
		));
		// must reset all final grades
		$students = SQLQuery::create()->bypassSecurity()->select("StudentSubjectGrade")->whereValue("StudentSubjectGrade", "subject", $subject_id)->execute();
		$keys = array();
		foreach ($students as $s)
			array_push($keys, array("people"=>$s["people"],"subject"=>$subject_id));
		SQLQuery::create()->bypassSecurity()->updateAllKeys("StudentSubjectGrade", $keys, array("grade"=>null));
		
		return $id;
	}
	public function update_evaluation_type($type_id, $name, $weight) {
		$type = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "id", $type_id)->executeSingleRow();
		if ($type == null)
			throw new Exception("Invalid evaluation type id");
		if ($type["name"] == $name && $type["weight"] == $weight)
			return; // no change
		SQLQuery::create()->bypassSecurity()->updateByKey("CurriculumSubjectEvaluationType", $type_id, array("name"=>$name,"weight"=>$weight));
		if ($type["weight"] <> $weight) {
			// weight changed, we need to recompute all grades
			$this->compute_subject_grades($type["subject"]);
		}
	}
	public function create_evaluation($type_id, $name, $weight, $max_grade) {
		$type = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "id", $type_id)->executeSingleRow();
		if ($type == null)
			throw new Exception("Invalid evaluation type id");
		$id = SQLQuery::create()->bypassSecurity()->insert("CurriculumSubjectEvaluation", array(
			"type"=>$type_id,
			"name"=>$name,
			"weight"=>$weight,
			"max_grade"=>$max_grade
		));
		// we must reset all grades of this evaluation type, and the final grades
		$students = SQLQuery::create()->bypassSecurity()->select("StudentSubjectEvaluationTypeGrade")->whereValue("StudentSubjectEvaluationTypeGrade", "type", $type_id)->execute();
		$keys = array();
		foreach ($students as $s)
			array_push($keys, array("people"=>$s["people"],"type"=>$type_id));
		SQLQuery::create()->bypassSecurity()->updateAllKeys("StudentSubjectEvaluationTypeGrade", $keys, array("grade"=>null));
		$students = SQLQuery::create()->bypassSecurity()->select("StudentSubjectGrade")->whereValue("StudentSubjectGrade", "subject", $type["subject"])->execute();
		$keys = array();
		foreach ($students as $s)
			array_push($keys, array("people"=>$s["people"],"subject"=>$type["subject"]));
		SQLQuery::create()->bypassSecurity()->updateAllKeys("StudentSubjectGrade", $keys, array("grade"=>null));
		return $id;
	}
	public function update_evaluation($eval_id, $name, $weight, $max_grade) {
		$eval = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectEvaluation")->whereValue("CurriculumSubjectEvaluation", "id", $eval_id)->executeSingleRow();
		if ($eval == null)
			throw new Exception("Invalid evaluation id");
		if ($eval["name"] == $name && $eval["weight"] == $weight && $eval["max_grade"] == $max_grade)
			return; // no change
		SQLQuery::create()->bypassSecurity()->updateByKey("CurriculumSubjectEvaluation", $eval_id, array(
			"name"=>$name,
			"weight"=>$weight,
			"max_grade"=>$max_grade
		));
		if ($weight <> $eval["weight"] || $max_grade <> $eval["max_grade"]) {
			// we need to recompute all the grades
			$type = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "id", $eval["type"])->executeSingleRow();
			$this->compute_subject_grades($type["subject"]);
		}
	}
	
	public function remove_evaluation($subject_id, $type_id, $eval_id) {
		$subject_info = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectGrading")->whereValue("CurriculumSubjectGrading", "subject", $subject_id)->executeSingleRow();
		if ($subject_info == null) throw new Exception("Invalid subject id");
		$type = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "id", $type_id)->executeSingleRow();
		if ($type == null) throw new Exception("Invalid evaluation type id");
		if ($type["subject"] <> $subject_id) throw new Exception("Subject id does not match with the subject found in the evaluation type");
		$eval = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectEvaluation")->whereValue("CurriculumSubjectEvaluation", "id", $eval_id)->executeSingleRow();
		if ($eval == null) throw new Exception("Invalid evaluation id");
		if ($eval["type"] <> $type_id) throw new Exception("Evaluation type id does not match with the type found in the evaluation");
		
		SQLQuery::create()->bypassSecurity()->removeKey("CurriculumSubjectEvaluation", $eval_id);
		$this->compute_subject_grades($subject_id);
	}
	public function remove_evaluation_type($subject_id, $type_id) {
		$subject_info = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectGrading")->whereValue("CurriculumSubjectGrading", "subject", $subject_id)->executeSingleRow();
		if ($subject_info == null) throw new Exception("Invalid subject id");
		$type = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "id", $type_id)->executeSingleRow();
		if ($type == null) throw new Exception("Invalid evaluation type id");
		if ($type["subject"] <> $subject_id) throw new Exception("Subject id does not match with the subject found in the evaluation type");
		
		SQLQuery::create()->bypassSecurity()->removeKey("CurriculumSubjectEvaluationType", $type_id);
		$this->compute_subject_grades($subject_id);
	}
	
	public function update_students_evaluation_grades($subject_id, $students_evaluations_grades) {
		$types = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "subject", $subject_id)->execute();
		$types_ids = array();
		foreach ($types as $type) array_push($types_ids, $type["id"]);
		if (count($types_ids) > 0)
			$evaluations = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectEvaluation")->whereIn("CurriculumSubjectEvaluation", "type", $types_ids)->execute();
		else
			$evaluations = array();
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
		SQLQuery::create()->bypassSecurity()->removeKeys("StudentSubjectEvaluationGrade", $keys_evals);
		SQLQuery::create()->bypassSecurity()->removeKeys("StudentSubjectEvaluationTypeGrade", $keys_types);
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
			SQLQuery::create()->bypassSecurity()->insertMultiple("StudentSubjectEvaluationGrade", $rows);
		$this->compute_subject_grades($subject_id);
	}
	
	public function compute_subject_grades($subject_id) {
		// get subject, evaluation types, and evaluations
		$subject_info = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectGrading")->whereValue("CurriculumSubjectGrading", "subject", $subject_id)->executeSingleRow();
		if ($subject_info == null) throw new Exception("Invalid subject id");
		$subject = PNApplication::$instance->curriculum->getSubjectQuery($subject_id)->executeSingleRow();
		$types = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType", "subject", $subject_id)->execute();
		$types_ids = array();
		foreach ($types as $type) array_push($types_ids, $type["id"]);
		if (count($types_ids) > 0)
			$evaluations = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectEvaluation")->whereIn("CurriculumSubjectEvaluation", "type", $types_ids)->execute();
		else
			$evaluations = array();
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
		if (count($evaluations_ids) > 0 && count($students_ids) > 0)
			$students_grades = SQLQuery::create()->bypassSecurity()
				->select("StudentSubjectEvaluationGrade")
				->whereIn("StudentSubjectEvaluationGrade", "evaluation", $evaluations_ids)
				->whereIn("StudentSubjectEvaluationGrade", "people", $students_ids)
				->execute();
		else
			$students_grades = array();
		// compute grades, for each student
		$types_to_remove = array();
		$types_to_insert = array();
		$finals = array();
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
			array_push($finals, array(array("people"=>$student["people"],"subject"=>$subject_id),array("grade"=>$final_grade)));
		}
		if (count($types_to_remove) > 0) {
			$q = SQLQuery::create()->bypassSecurity()->select("StudentSubjectEvaluationTypeGrade");
			$where = "";
			foreach ($types_to_remove as $r) {
				if ($where <> "") $where .= " OR ";
				$where .= "(`people`=".$r["people"]." AND `type`=".$r["type"].")";
			}
			$q->where($where);
			$to_remove = $q->execute();
			SQLQuery::create()->bypassSecurity()->removeRows("StudentSubjectEvaluationTypeGrade", $to_remove);
		}
		if (count($types_to_insert) > 0)
			SQLQuery::create()->bypassSecurity()->insertMultiple("StudentSubjectEvaluationTypeGrade", $types_to_insert);
		if (count($finals) > 0) {
			// look for existing entries
			$keys = array();
			foreach ($finals as $f) array_push($keys, $f[0]);
			if (count($keys) > 0)
				$existing = SQLQuery::create()->bypassSecurity()->select("StudentSubjectGrade")->whereKeyIn("StudentSubjectGrade",$keys)->execute();
			$update = array();
			for ($i = 0; $i < count($finals); $i++) {
				$found = false;
				foreach ($existing as $e) if ($e["people"] == $finals[$i][0]["people"] && $e["subject"] == $finals[$i][0]["subject"]) { $found = true; break; }
				if ($found) {
					array_push($update, array(array($finals[$i][0]),$finals[$i][1]));
					array_splice($finals, $i, 1);
					$i--;
				}
			}
			if (count($update) > 0)
				SQLQuery::create()->bypassSecurity()->updateByKeys("StudentSubjectGrade", $update);
			if (count($finals) > 0) {
				$to_insert = array();
				foreach ($finals as $f) array_push($to_insert, array_merge($f[0],$f[1]));
				SQLQuery::create()->bypassSecurity()->insertMultiple("StudentSubjectGrade", $to_insert);
			}
		}
	}
	
}
?>