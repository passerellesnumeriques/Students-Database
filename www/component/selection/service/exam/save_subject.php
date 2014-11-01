<?php 
require_once("component/selection/SelectionJSON.inc");
class service_exam_save_subject extends Service {
	
	public function getRequiredRights() { return array("manage_exam_subject"); }
	public function documentation() {
		echo "Save / insert an exam subject object into the database";
	}
	public function inputDocumentation() {
		echo "<code>exam</code> the ExamSubject JSON structure.<br/>";
		echo "<code>answers</code> A list of list of object <code>{q:xxx,a:yyy}</code>: each item of the list corresponds to one subject version, and is itself a list of question_id/answer.<br/>";
		echo "All ids less or equals to 0 are considered as new items.";
	}
	public function outputDocumentation() {
		echo "<code>exam</code> and <code>answers</code>: same as input, but ids are updated for the new items.";
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		$subject = &$input["exam"];
		
		if (!PNApplication::$instance->selection->canEditExamSubjects()) {
			PNApplication::error("Modification of exam subject denied: some results are already entered for some applicants");
			return;
		}
		
		// re-calculate scores and indexes on back-end side to make sure everything is coherent
		$total_score = 0;
		$part_index = 1;
		foreach ($subject["parts"] as &$part) {
			$part["index"] = $part_index++;
			$part_score = 0;
			$q_index = 1;
			foreach ($part["questions"] as &$q) {
				$part_score += floatval($q["max_score"]);
				$q["index"] = $q_index++;
			}
			$part["max_score"] = $part_score;
			$total_score += $part_score;
		}
		$subject["max_score"] = $total_score;
		
		// ExamSubject
		if ($subject["id"] <= 0) {
			$subject["id"] = SQLQuery::create()->bypassSecurity()->insert("ExamSubject", array("name"=>$subject["name"],"max_score"=>$subject["max_score"]));
		} else {
			$s = SQLQuery::create()->bypassSecurity()->select("ExamSubject")->whereValue("ExamSubject","id",$subject["id"])->executeSingleRow();
			if ($s == null) {
				PNApplication::error("Invalid subject");
				return;
			}
			if ($s["name"] <> $subject["name"] || $s["max_score"] <> $subject["max_score"])
				SQLQuery::create()->bypassSecurity()->updateByKey("ExamSubject", $subject["id"], array("name"=>$subject["name"],"max_score"=>$subject["max_score"]));
		}
		
		// versions
		$current_ids = SQLQuery::create()->bypassSecurity()->select("ExamSubjectVersion")->whereValue("ExamSubjectVersion","exam_subject",$subject["id"])->field("id")->executeSingleField();
		for ($i = 0; $i < count($subject["versions"]); $i++) {
			$id = $subject["versions"][$i];
			if ($id <= 0) {
				$id = SQLQuery::create()->bypassSecurity()->insert("ExamSubjectVersion", array("exam_subject"=>$subject["id"]));
				$subject["versions"][$i] = $id;
			} else {
				$found = false;
				for ($j = 0; $j < count($current_ids); $j++)
					if ($current_ids[$j] == $id) {
						array_splice($current_ids, $j, 1);
						$found = true;
						break;
					}
				if (!$found) {
					PNApplication::error("Invalid subject version id");
					return;
				}
			}
		}
		if (count($current_ids) > 0)
			SQLQuery::create()->bypassSecurity()->removeKeys("ExamSubjectVersion", $current_ids);
		
		// parts
		$current_parts = SQLQuery::create()->bypassSecurity()->select("ExamSubjectPart")->whereValue("ExamSubjectPart","exam_subject",$subject["id"])->execute();
		foreach ($subject["parts"] as &$part) {
			$id = $part["id"];
			if ($id <= 0) {
				$id = SQLQuery::create()->bypassSecurity()->insert("ExamSubjectPart", array("exam_subject"=>$subject["id"],"index"=>$part["index"],"max_score"=>$part["max_score"],"name"=>$part["name"]));
				$part["id"] = $id;
			} else {
				$found = false;
				for ($j = 0; $j < count($current_parts); $j++)
					if ($current_parts[$j]["id"] == $id) {
						$c = $current_parts[$j];
						array_splice($current_parts, $j, 1);
						$found = true;
						if ($c["name"] <> $part["name"] || $c["max_score"] <> $part["max_score"] || $c["index"] <> $part["index"])
							SQLQuery::create()->bypassSecurity()->updateByKey("ExamSubjectPart", $id, array("index"=>$part["index"],"max_score"=>$part["max_score"],"name"=>$part["name"]));
						break;
					}
				if (!$found) {
					PNApplication::error("Invalid subject part id");
					return;
				}
			}
			unset($part);
		}
		$remaining_parts_ids = array();
		foreach ($current_parts as $c) array_push($remaining_parts_ids, $c["id"]);
		if (count($remaining_parts_ids) > 0)
			SQLQuery::create()->bypassSecurity()->removeKeys("ExamSubjectPart", $remaining_parts_ids);
		
		// questions
		$questions_ids_mapping = array();
		$all_questions = array();
		foreach ($subject["parts"] as &$part) {
			$current_questions = SQLQuery::create()->bypassSecurity()->select("ExamSubjectQuestion")->whereValue("ExamSubjectQuestion","exam_subject_part",$part["id"])->execute();
			foreach ($part["questions"] as &$q) {
				$id = $q["id"];
				if ($id <= 0) {
					$new_id = SQLQuery::create()->bypassSecurity()->insert("ExamSubjectQuestion", array("exam_subject_part"=>$part["id"],"index"=>$q["index"],"max_score"=>$q["max_score"],"type"=>$q["type"],"type_config"=>$q["type_config"]));
					$questions_ids_mapping[$id] = $new_id;
					$all_questions[$new_id] = $q;
					$q["id"] = $new_id;
				} else {
					$questions_ids_mapping[$id] = $id;
					$all_questions[$id] = $q;
					$found = false;
					for ($j = 0; $j < count($current_questions); $j++)
						if ($current_questions[$j]["id"] == $id) {
							$c = $current_questions[$j];
							array_splice($current_questions, $j, 1);
							$found = true;
							if ($c["type"] <> $q["type"] || $c["type_config"] <> $q["type_config"] || $c["max_score"] <> $q["max_score"] || $c["index"] <> $q["index"])
								SQLQuery::create()->bypassSecurity()->updateByKey("ExamSubjectQuestion", $id, array("index"=>$q["index"],"max_score"=>$q["max_score"],"type"=>$q["type"],"type_config"=>$q["type_config"]));
							break;
						}
					if (!$found) {
						PNApplication::error("Invalid question id");
						return;
					}
				}
				unset($q);
			}
			$remaining_ids = array();
			foreach ($current_questions as $c) array_push($remaining_ids, $c["id"]);
			if (count($remaining_ids) > 0)
				SQLQuery::create()->bypassSecurity()->removeKeys("ExamSubjectQuestion", $remaining_ids);
			unset($part);
		}
		
		// answers
		// remove all
		$rows = SQLQuery::create()->bypassSecurity()->select("ExamSubjectAnswer")->whereIn("ExamSubjectAnswer","exam_subject_version",$subject["versions"])->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("ExamSubjectAnswer", $rows);
		// create
		$answers = &$input["answers"];
		$to_insert = array();
		for ($version_index = 0; $version_index < count($answers); $version_index++) {
			foreach ($answers[$version_index] as &$a) {
				$a["q"] = $questions_ids_mapping[$a["q"]];
				// check the answer is valid
				$question = $all_questions[$a["q"]];
				$valid = false;
				switch ($question["type"]) {
					case "mcq_single":
						$start = ord("A");
						$end = $start+intval($question["type_config"])-1;
						if (ord($a["a"]) >= $start && ord($a["a"]) <= $end)
							$valid = true;
						break;
				}
				if ($valid)
					array_push($to_insert, array("exam_subject_version"=>$subject["versions"][$version_index],"exam_subject_question"=>$a["q"],"answer"=>$a["a"]));
				unset($a);
			}
		}
		if (count($to_insert) > 0)
			SQLQuery::create()->bypassSecurity()->insertMultiple("ExamSubjectAnswer", $to_insert);
		
		if (PNApplication::$instance->selection->getOneConfigAttributeValue("set_correct_answer")) {
			// check we have the answer of all questions, and all versions
			$missing = array();
			foreach ($all_questions as $qid=>$q) {
				for ($version_index = 0; $version_index < count($answers); $version_index++) {
					$found = false;
					foreach ($answers[$version_index] as &$a)
						if ($a["q"] == $qid) { $found = true; break; }
					if (!$found) array_push($missing, array("qid"=>$qid,"version_index"=>$version_index));
				}
			}
			if (count($missing) > 0) {
				$msg = count($missing)." answer".(count($missing) > 1 ? "s are" : " is")." missing:<ul>";
				foreach ($missing as $m) {
					$msg .= "<li>";
					$msg .= "Question ".$all_questions[$m["qid"]]["index"];
					$p = null;
					foreach ($subject["parts"] as &$part) {
						foreach ($part["questions"] as &$q)
							if ($q["id"] == $m["qid"]) { $p = $part["index"]; break; }
						if ($p <> null) break;
					}
					$msg .= " in Part ".$p;
					if (count($answers) > 1) {
						$msg .= ", version ".chr(ord("A")+$m["version_index"]);
					}
					$msg .= "</li>";
				}
				$msg .= "</ul>";
				PNApplication::warning($msg);
			}
		}
		
		// check there is no empty exam extract (if we removed all its parts)
		$empty_extracts = SQLQuery::create()
			->select("ExamSubjectExtract")
			->join("ExamSubjectExtract", "ExamSubjectExtractParts", array("id"=>"extract"))
			->groupBy("ExamSubjectExtract","id")
			->whereNull("ExamSubjectExtractParts", "part") // no more part attached
			->field("ExamSubjectExtract", "id")
			->executeSingleField();
		if (count($empty_extracts) > 0)
			SQLQuery::create()->removeKeys("ExamSubjectExtract", $empty_extracts);
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "{exam:".json_encode($subject).",answers:".json_encode($answers)."}";
		}
	}
	
}
?>