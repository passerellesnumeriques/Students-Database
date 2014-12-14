<?php 
class service_exam_save_results extends Service {
	
	public function getRequiredRights() { return array("edit_exam_results"); }
	
	public function documentation() { echo "Save exam results and apply eligibility rules"; }
	public function inputDocumentation() {}
	public function outputDocumentation() { echo "{passers,interview_center_id,interview_center_name} with passers being the people id of the passers"; }
	
	public function execute(&$component, $input) {
		$results = $input["applicants"];
		$session_id = $input["session"];
		$room_id = $input["room"];
		$lock_id = $input["lock"];
		
		// check lock
		require_once("component/data_model/DataBaseLock.inc");
		$err = DataBaseLock::checkLock($lock_id, "ExamSubject_".PNApplication::$instance->selection->getCampaignId(), null, null);
		if ($err <> null) {
			PNApplication::error($err);
			return;
		}

		SQLQuery::startTransaction();
		
		// get the list of subjects with questions
		$_subjects = SQLQuery::create()->bypassSecurity()->select("ExamSubject")->execute();
		$_subjects_parts = SQLQuery::create()->bypassSecurity()->select("ExamSubjectPart")->execute();
		$_questions = SQLQuery::create()->bypassSecurity()->select("ExamSubjectQuestion")->execute();
		$subjects = array();
		foreach ($_subjects as $s) {
			$s["parts"] = array();
			$s["versions"] = array();
			$s["questions_ids"] = array();
			$subjects[$s["id"]] = $s;
		}
		$subjects_parts = array();
		foreach ($_subjects_parts as $sp) {
			$sp["questions"] = array();
			$subjects_parts[$sp["id"]] = $sp;
			$subjects[$sp["exam_subject"]]["parts"][$sp["id"]] = $sp;
		}
		$questions = array();
		foreach ($_questions as $q) {
			$q["answers"] = array();
			$questions[$q["id"]] = $q;
			$subjects[$subjects_parts[$q["exam_subject_part"]]["exam_subject"]]["parts"][$q["exam_subject_part"]]["questions"][$q["id"]] = $q;
			array_push($subjects[$subjects_parts[$q["exam_subject_part"]]["exam_subject"]]["questions_ids"], $q["id"]);
		}
		$_subjects_versions = SQLQuery::create()->bypassSecurity()->select("ExamSubjectVersion")->orderBy("ExamSubjectVersion","id")->execute();
		foreach ($_subjects_versions as $version)
			array_push($subjects[$version["exam_subject"]]["versions"], $version["id"]);
		if (PNApplication::$instance->selection->getOneConfigAttributeValue("set_correct_answer")) {
			$_answers = SQLQuery::create()->bypassSecurity()->select("ExamSubjectAnswer")->execute();
			foreach ($_answers as $a) {
				$q = $questions[$a["exam_subject_question"]];
				$subject_part = $subjects_parts[$q["exam_subject_part"]];
				$subjects[$subject_part["exam_subject"]]["parts"][$subject_part["id"]]["questions"][$q["id"]]["answers"][$a["exam_subject_version"]] = $a["answer"]; 
			}
		}
		$extracts = SQLQuery::create()->bypassSecurity()->select("ExamSubjectExtract")->execute();
		if (count($extracts) > 0) {
			$extracts_parts = SQLQuery::create()->bypassSecurity()->select("ExamSubjectExtractParts")->execute();
			for ($i = 0; $i < count($extracts); $i++) {
				$extracts[$i]["parts"] = array();
				foreach ($extracts_parts as $p) if ($p["extract"] == $extracts[$i]["id"]) array_push($extracts[$i]["parts"], $p["part"]);
			}
		}
		
		$rules = SQLQuery::create()->bypassSecurity()->select("ExamEligibilityRule")->execute();
		$rules_topics = SQLQuery::create()->bypassSecurity()->select("ExamEligibilityRuleTopic")->execute();
		for ($i = 0; $i < count($rules); $i++) {
			$rules[$i]["topics"] = array();
			foreach ($rules_topics as $t) if ($t["rule"] == $rules[$i]["id"]) array_push($rules[$i]["topics"], $t);
		}
		$root_rule = array("id"=>null,"next_rules"=>array());
		$this->buildRulesTree($rules, $root_rule);
		
		// get applicants assigned to this session/room
		$applicants_ids = SQLQuery::create()
			->select("Applicant")
			->whereValue("Applicant", "exam_center_room", $room_id)
			->whereValue("Applicant", "exam_session", $session_id)
			->field("Applicant", "people")
			->executeSingleField()
			;
		
		// get exam center id
		$center_id = SQLQuery::create()->select("ExamSession")->whereValue("ExamSession","event",$session_id)->field("exam_center")->executeSingleValue();
		// get interview center
		$interview_center_id = SQLQuery::create()->select("InterviewCenterExamCenter")->whereValue("InterviewCenterExamCenter","exam_center", $center_id)->field("interview_center")->executeSingleValue();
		if ($interview_center_id <> null)
			$interview_center_name = SQLQuery::create()->bypassSecurity()->select("InterviewCenter")->whereValue("InterviewCenter","id",$interview_center_id)->field("name")->executeSingleValue();
		else
			$interview_center_name = null;

		$insert_subject = array();
		$insert_subject_part = array();
		$insert_answer = array();
		$insert_extracts = array();
		$passers = array();
		$loosers = array();
		foreach ($results as $app) {
			if (!in_array($app["people_id"], $applicants_ids)) {
				PNApplication::error("Invalid request: you cannot save exam results for an applicant who is not assigned to the given session and room");
				return;
			}
			if ($app["exam_attendance"] <> "Yes") {
				SQLQuery::create()->updateByKey("Applicant", $app["people_id"], array(
					"exam_attendance"=>$app["exam_attendance"], 
					"excluded"=>true, 
					"automatic_exclusion_step"=>"Written Exam", 
					"automatic_exclusion_reason"=>"Attendance", 
					"exam_passer"=>null,
					"interview_center"=>null,
					"interview_session"=>null
				));
				array_push($loosers, $app["people_id"]);
			}
			$parts_score = array();
			$all_parts_score = SQLQuery::create()->bypassSecurity()->select("ApplicantExamSubjectPart")->whereValue("ApplicantExamSubjectPart","applicant",$app["people_id"])->execute();
			$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamSubject")->whereValue("ApplicantExamSubject","applicant",$app["people_id"])->execute();
			$subjects_scores = array();
			foreach ($rows as $row) $subjects_scores[$row["exam_subject"]] = floatval($row["score"]);
			foreach ($app["subjects"] as $subject) {
				if (!isset($subjects[$subject["id"]])) {
					PNApplication::error("Invalid subject id");
					return;
				}
				$version = @$subject["version"];
				if (count($subjects[$subject["id"]]["versions"]) == 0) {
					$version = null;
				} else if (count($subjects[$subject["id"]]["versions"]) == 1) {
					if ($version == null) $version = $subjects[$subject["id"]]["versions"][0];
					else if ($version <> $subjects[$subject["id"]]["versions"][0]) {
						PNApplication::error("Invalid subject version");
						return;
					}
				} else {
					if ($version == null) {
						PNApplication::error("Missing subject version");
						return;
					}
					if (!in_array($version, $subjects[$subject["id"]]["versions"])) {
						PNApplication::error("Invalid subject version");
						return;
					}
				}

				SQLQuery::create()->bypassSecurity()->removeKey("ApplicantExamSubject", array("applicant"=>$app["people_id"],"exam_subject"=>$subject["id"]));
				$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamSubjectPart")->whereValue("ApplicantExamSubjectPart","applicant",$app["people_id"])->whereIn("ApplicantExamSubjectPart","exam_subject_part",array_keys($subjects[$subject["id"]]["parts"]))->execute();
				if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantExamSubjectPart",$rows);
				$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamAnswer")->whereValue("ApplicantExamAnswer","applicant",$app["people_id"])->whereIn("ApplicantExamAnswer","exam_subject_question",$subjects[$subject["id"]]["questions_ids"])->execute();
				if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantExamAnswer",$rows);
				$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamExtract")->whereValue("ApplicantExamExtract","applicant",$app["people_id"])->execute();
				if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantExamExtract",$rows);
				
				$subject_score = 0;
				foreach ($subject["parts"] as $part) {
					if (!isset($subjects[$subject["id"]]["parts"][$part["id"]])) {
						PNApplication::error("Invalid subject part");
						return;
					}
					$p = $subjects[$subject["id"]]["parts"][$part["id"]];
					if (!isset($part["questions"])) {
						// only score
						$part_score = @$part["score"];
						if ($part_score == null) $part_score = 0; else $part_score = floatval($part_score);
						if ($part_score > floatval($p["max_score"])) {
							PNApplication::error("Invalid score for subject part: more than the maximum possible score");
							return;
						}
					} else {
						$part_score = 0;
						foreach ($part["questions"] as $qa) {
							if (!isset($p["questions"][$qa["id"]])) {
								PNApplication::error("Invalid question");
								return;
							}
							$q = $p["questions"][$qa["id"]];
							if (key_exists("answer", $qa)) {
								// answer given
								$given_answer = $qa["answer"];
								if ($given_answer === null || $given_answer === "")
									$q_score = 0;
								else {
									if (!isset($q["answers"][$version])) {
										PNApplication::error("Missing answer for subject ".$subjects[$subject["id"]]["name"].", part ".$p["index"].", question ".$q["index"]);
										return;
									}
									$good_answer = $q["answers"][$version];
									switch ($q["type"]) {
										case "mcq_single":
											if ($good_answer == $given_answer)
												$q_score = floatval($q["max_score"]);
											else {
												$nb_choices = intval($q["type_config"]);
												$q_score = -(floatval($q["max_score"])/($nb_choices-1));
											}
											break;
									}
								}
							} else {
								// only score
								$given_answer = null;
								$q_score = @$qa["score"];
								if ($q_score == null) $q_score = 0; else $q_score = floatval($q_score);
							}
							if ($q_score > floatval($q["max_score"])) {
								PNApplication::error("Invalid score for question: more than the maximum possible score");
								return;
							}
							array_push($insert_answer, array("applicant"=>$app["people_id"], "exam_subject_question"=>$qa["id"], "answer"=>$given_answer, "score"=>$q_score));
							$part_score += $q_score;
						}
					}
					array_push($insert_subject_part, array("applicant"=>$app["people_id"], "exam_subject_part"=>$p["id"], "score"=>$part_score));
					$subject_score += $part_score;
					$parts_score[$p["id"]] = $part_score;
				}
				array_push($insert_subject, array("applicant"=>$app["people_id"], "exam_subject"=>$subject["id"], "score"=>$subject_score, "exam_subject_version"=>$version));
				$subjects_scores[$subject["id"]] = $subject_score;
			}
			$extracts_scores = array();
			foreach ($extracts as $e) {
				$score = 0;
				foreach ($e["parts"] as $part_id) {
					if (isset($parts_score[$part_id]))
						$score += $parts_score[$part_id];
					else foreach ($all_parts_score as $ps)
						if ($ps["exam_subject_part"] == $part_id) { $score += floatval($ps["score"]); break; }
				}
				array_push($insert_extracts, array("applicant"=>$app["people_id"], "exam_extract"=>$e["id"], "score"=>$score));
				$extracts_scores[$e["id"]] = $score;
			}
			
			// apply eligibility rules
			if ($app["exam_attendance"] == "Yes") {
				$pass = $this->applyRules($root_rule, $subjects_scores, $extracts_scores);
				if (!$pass) {
					SQLQuery::create()->updateByKey("Applicant", $app["people_id"], array(
						"exam_attendance"=>"Yes", 
						"excluded"=>true, 
						"automatic_exclusion_step"=>"Written Exam", 
						"automatic_exclusion_reason"=>"Failed", 
						"exam_passer"=>false,
						"interview_center"=>null,
						"interview_session"=>null
					));
					array_push($loosers, $app["people_id"]);
				} else {
					// if applicant previously excluded because of attendance or results, put it back in the process!
					$row = SQLQuery::create()->bypassSecurity()->select("Applicant")->whereValue("Applicant","people",$app["people_id"])->executeSingleRow();
					$update = array("exam_attendance"=>"Yes", "exam_passer"=>true);
					if ($row["excluded"] && $row["automatic_exclusion_step"] == "Written Exam" && ($row["automatic_exclusion_reason"] == "Attendance" || $row["automatic_exclusion_reason"] == "Failed")) {
						$update["excluded"] = false;
						$update["automatic_exclusion_step"] = null;
						$update["automatic_exclusion_reason"] = null;
					}
					// assign to interview center if already linked
					if ($interview_center_id <> null && $row["interview_center"] == null) {
						$update["interview_center"] = $interview_center_id;
						$update["interview_session"] = null;
					}
					SQLQuery::create()->bypassSecurity()->updateByKey("Applicant", $app["people_id"], $update);
					array_push($passers, $app["people_id"]);
				}
			}
		}
		
		// do the inserts
		if (count($insert_subject) > 0) SQLQuery::create()->bypassSecurity()->insertMultiple("ApplicantExamSubject", $insert_subject);
		if (count($insert_subject_part) > 0) SQLQuery::create()->bypassSecurity()->insertMultiple("ApplicantExamSubjectPart", $insert_subject_part);
		if (count($insert_answer) > 0) SQLQuery::create()->bypassSecurity()->insertMultiple("ApplicantExamAnswer", $insert_answer);
		if (count($insert_extracts) > 0) SQLQuery::create()->bypassSecurity()->insertMultiple("ApplicantExamExtract", $insert_extracts);
		
		// signal passers and loosers if ever we need to do some actions
		PNApplication::$instance->selection->signalExamPassersAndLoosers($passers, $loosers);
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "{passers:".json_encode($passers).",interview_center_id:".json_encode($interview_center_id).",interview_center_name:".json_encode($interview_center_name)."}";
		} else
			SQLQuery::rollbackTransaction();
	}
	
	private function buildRulesTree(&$rules, &$rule) {
		for ($i = 0; $i < count($rules); $i++) {
			$r = $rules[$i];
			if ($r["parent"] <> $rule["id"]) continue;
			$r["next_rules"] = array();
			array_push($rule["next_rules"], $r);
			array_splice($rules, $i, 1);
			$i--;
		}
		for ($i = 0; $i < count($rule["next_rules"]); $i++)
			$this->buildRulesTree($rules, $rule["next_rules"][$i]);
	}
	
	private function applyRules($rule, $subjects_scores, $extracts_scores) {
		if (isset($rule["topics"])) {
			$total = 0;
			foreach ($rule["topics"] as $t) {
				if ($t["subject"] <> null)
					$score = @$subjects_scores[$t["subject"]];
				else 
					$score = @$extracts_scores[$t["extract"]];
				if ($score === null) $score = 0;
				$coef = @$t["coefficient"];
				if ($coef === null) $coef = 1; else $coef = floatval($coef);
				$total += $score * $coef;
			}
			$to_reach = floatval($rule["expected"]);
			if ($total < $to_reach) return false;
		}
		// we passed the rule, we need to pass one of the next ones
		if (count($rule["next_rules"]) == 0)
			return true; // we reach the end !
		foreach ($rule["next_rules"] as $r)
			if ($this->applyRules($r, $subjects_scores, $extracts_scores))
				return true;
		return false;
	}
	
}
?>