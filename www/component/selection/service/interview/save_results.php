<?php 
class service_interview_save_results extends Service {
	
	public function getRequiredRights() { return array("edit_interview_results"); }
	
	public function documentation() { echo "Save interview results"; }
	public function inputDocumentation() { echo "session: id of the interview session, applicants: list of results to save by applicant"; }
	public function outputDocumentation() { echo "list of passers"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();

		// get the existing criteria
		$criteria = SQLQuery::create()->bypassSecurity()->select("InterviewCriterion")->field("id")->executeSingleField();
		// get the existing interviewers
		require_once("component/calendar/CalendarJSON.inc");
		$event = CalendarJSON::getEventFromDB($input["session"], PNApplication::$instance->selection->getCalendarId());
		$interviewers = array();
		foreach ($event["attendees"] as $a) array_push($interviewers, $a["id"]);
		// get the existing applicants
		$assigned_applicants = SQLQuery::create()->bypassSecurity()->select("Applicant")->whereValue("Applicant","interview_session",$input["session"])->field("people")->executeSingleField();
		// get the eligibility rules
		$rules = SQLQuery::create()->bypassSecurity()->select("InterviewEligibilityRule")->execute();
		$rules_criteria = SQLQuery::create()->bypassSecurity()->select("InterviewEligibilityRuleCriterion")->execute();
		for ($i = 0; $i < count($rules); $i++) {
			$rules[$i]["criteria"] = array();
			foreach ($rules_criteria as $t) if ($t["rule"] == $rules[$i]["id"]) array_push($rules[$i]["criteria"], $t);
		}
		$root_rule = array("id"=>null,"next_rules"=>array());
		$this->buildRulesTree($rules, $root_rule);
		
		$app_ids = array();
		$app_updates = array();
		$insert_grades = array();
		$insert_interviewers = array();
		$passers = array();
		$loosers = array();
		foreach ($input["applicants"] as $app) {
			$app_id = $app["applicant"];
			if (!in_array($app_id, $assigned_applicants)) {
				PNApplication::error("Applicant not assigned to this interview session!");
				return;
			}
			array_push($app_ids, $app_id);
			$app_updates[$app_id] = array("interview_comment"=>$app["comment"]);
			if (!$app["attendance"]) {
				// absent
				$app_updates[$app_id]["interview_attendance"] = 0;
				$app_updates[$app_id]["automatic_exclusion_step"] = "Interview";
				$app_updates[$app_id]["automatic_exclusion_reason"] = "Absent";
				$app_updates[$app_id]["excluded"] = 1;
				$app_updates[$app_id]["interview_passer"] = 0;
				array_push($loosers, $app_id);
				continue;
			}
			$app_updates[$app_id]["interview_attendance"] = 1;
			foreach ($app["interviewers"] as $i) {
				if (!in_array($i, $interviewers)) {
					PNApplication::error("Interviewer not in this session !");
					return;
				}
				array_push($insert_interviewers, array("applicant"=>$app_id,"interviewer"=>$i));
			}
			foreach ($app["grades"] as $g) {
				if (!in_array($g["criterion"], $criteria)) {
					PNApplication::error("Criterion does not exist !");
					return;
				}
				array_push($insert_grades, array("people"=>$app_id,"criterion"=>$g["criterion"],"grade"=>$g["grade"]));
			}
			if ($this->applyRules($root_rule, $app["grades"])) {
				// passed !
				array_push($passers, $app_id);
			} else {
				// failed !
				$app_updates[$app_id]["automatic_exclusion_step"] = "Interview";
				$app_updates[$app_id]["automatic_exclusion_reason"] = "Failed";
				$app_updates[$app_id]["excluded"] = 1;
				$app_updates[$app_id]["interview_passer"] = 0;
				array_push($loosers, $app_id);
			}
		}
		// if some passers where excluded previously because of failure, we need to unexclude them
		if (count($passers) > 0) {
			$passers_info = SQLQuery::create()->bypassSecurity()->select("Applicant")->whereIn("Applicant","people",$passers)->execute();
			foreach ($passers as $app_id) {
				$info = null;
				foreach ($passers_info as $p) if ($p["people"] == $app_id) { $info = $p; break; }
				if ($info["automatic_exclusion_step"] == "Interview" && $info["automatic_exclusion_reason"] == "Failed") {
					$app_updates[$app_id]["automatic_exclusion_step"] = null;
					$app_updates[$app_id]["automatic_exclusion_reason"] = null;
					$app_updates[$app_id]["excluded"] = 0;
				}
				$app_updates[$app_id]["interview_passer"] = 1;
			}
		}		
		// remove any previous grade
		$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantInterviewCriterionGrade")->whereIn("ApplicantInterviewCriterionGrade","people",$app_ids)->execute();
		if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantInterviewCriterionGrade", $rows);
		// remove any previous interviewers
		$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantInterviewer")->whereIn("ApplicantInterviewer","applicant",$app_ids)->execute();
		if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("ApplicantInterviewer", $rows);
		// insert grades
		if (count($insert_grades) > 0)
			SQLQuery::create()->bypassSecurity()->insertMultiple("ApplicantInterviewCriterionGrade", $insert_grades);
		// insert interviewers
		if (count($insert_interviewers) > 0)
			SQLQuery::create()->bypassSecurity()->insertMultiple("ApplicantInterviewer", $insert_interviewers);
		// update applicants
		foreach ($app_updates as $app_id=>$to_update)
			SQLQuery::create()->bypassSecurity()->updateByKey("Applicant", $app_id, $to_update);
		
		PNApplication::$instance->selection->signalInterviewPassersAndLoosers($passers, $loosers);
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo json_encode($passers);
		}
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
	
	private function applyRules($rule, $grades) {
		if (isset($rule["criteria"])) {
			$total = 0;
			foreach ($rule["criteria"] as $criterion) {
				$score = 0;
				foreach ($grades as $g) if ($g["criterion"] == $criterion["criterion"]) { $score = $g["grade"]; }
				if ($score === null) $score = 0;
				$score = floatval($score);
				$coef = @$criterion["coefficient"];
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
			if ($this->applyRules($r, $grades))
				return true;
		return false;
	}
	
}
?>