<?php 
class service_applicant_assign_is extends Service {
	
	public function getRequiredRights() { return array("manage_applicant"); }
	
	public function documentation() { echo "Assign applicants to an Information Session.<br/><span style='color:red'>Note: this may require 2 steps if we need confirmation from the user</span>"; }
	public function inputDocumentation() {
		echo "<ul>";
			echo "<li><code>applicants</code>: list of Applicant ids to assign</li>";
			echo "<li><code>information_session</code>: InformationSession id</li>";
			echo "<li><code>confirm_data</code>: optional, indicates the user confirmed already, and contains information about what the user confirmed. If data changed between the 2 calls, the confirmation is asked again.</li>";
		echo "</ul>";
	}
	public function outputDocumentation() {
		echo "If we need confirmation from the user, the service returns a <code>confirm_message</code> and <code>confirm_data</code> to be resent in order to confirm.<br/>";
		echo "Else, it returns true on success, and false in case of failure.";
	}
	
	public function execute(&$component, $input) {
		$applicants_ids = $input["applicants"];
		$is_id = $input["information_session"];
		$confirm_data = @$input["confirm_data"];
		
		SQLQuery::startTransaction();
		
		// retrieve info about the applicants
		$q = SQLQuery::create()
			->select("Applicant")
			->whereIn("Applicant", "people", $applicants_ids)
			;
		$q->join("Applicant", "ExamSession", array("exam_session"=>"event"));
		PNApplication::$instance->calendar->joinEvent($q, "ExamSession", "event");
		$q->field("Applicant", "people", "people_id");
		$q->field("Applicant", "information_session", "is_id");
		$q->field("Applicant", "exam_center", "exam_center_id");
		$q->field("Applicant", "exam_session", "exam_session_id");
		$q->field("CalendarEvent", "start", "exam_session_start");
		PNApplication::$instance->people->joinPeople($q, "Applicant", "people", false);
		$applicants = $q->execute();
		
		// retrieve info about the information session
		$is = SQLQuery::create()
			->select("InformationSession")
			->whereValue("InformationSession", "id", $is_id)
			->join("InformationSession", "ExamCenterInformationSession", array("id"=>"information_session"))
			->field("InformationSession", "id", "is_id")
			->field("ExamCenterInformationSession", "exam_center", "exam_center_id")
			->join("ExamCenterInformationSession", "ExamCenter", array("exam_center"=>"id"))
			->field("ExamCenter", "name", "exam_center_name")
			->executeSingleRow()
			;
		
		if ($is["exam_center_id"] == null) {
			// the IS is not linked with any exam center => no implication, we can just do it
			SQLQuery::create()->updateByKeys("Applicant", array(array(
				$applicants_ids,
				array("information_session"=>$is_id)
			)));
			if (!PNApplication::hasErrors()) {
				SQLQuery::commitTransaction();
				echo "true";
			}
			return;
		}
		
		// the IS is linked, we may need to re-assign applicants to new exam center
		$now = time();
		$applicants_in_past_exam_session = array();
		$applicants_in_future_exam_session = array();
		$applicants_different_exam_center = array();
		$applicants_ok = array();
		foreach ($applicants as $a) {
			if ($a["exam_center_id"] <> null && $a["exam_center_id"] <> $is["exam_center_id"]) {
				if ($a["exam_session_id"] <> null) {
					if ($a["exam_session_start"] < $now) {
						array_push($applicants_in_past_exam_session, $a);
					} else {
						array_push($applicants_in_future_exam_session, $a);
					}
				} else {
					array_push($applicants_different_exam_center, $a);
				} 
			} else array_push($applicants_ok, $a);
		}
		
		if (count($applicants_ok) == count($applicants)) {
			// everything is OK, we can just perform the change
			SQLQuery::create()->updateByKeys("Applicant", array(array(
				$applicants_ids,
				array("information_session"=>$is_id)
			)));
			if (!PNApplication::hasErrors()) {
				SQLQuery::commitTransaction();
				echo "true";
			}
			return;
		}
		
		// here we need confirmation from the user
		$data = array("ok"=>array(),"past"=>array(),"future"=>array(),"different"=>array());
		foreach ($applicants_ok as $a) array_push($data["ok"], $a["people_id"]);
		$message = "This Information Session is linked to the exam center <i>".htmlentities($is["exam_center_name"])."</i>, meaning the applicants should be assigned to this exam center.<br/>";
		$message .= "Among the applicants you want to assign to the new Information Session:<ul>";
		if (count($applicants_in_past_exam_session) > 0) {
			$message .= "<li>";
			$message .= "The following applicants are already assigned to another exam center, and they already had their exam, so they will stay in their current exam center:<ul>";
			foreach ($applicants_in_past_exam_session as $a) {
				$message .= "<li>".htmlentities($a["first_name"]." ".$a["last_name"])."</li>";
				array_push($data["past"], $a["people_id"]);
			}
			$message .= "</ul>";
			$message .= "</li>";
		}
		if (count($applicants_in_future_exam_session) > 0) {
			$message .= "<li>";
			$message .= "The following applicants are already assigned to another exam center, and they are scheduled for an exam session. They will be removed from their exam session to be assigned to the new exam center:<ul>";
			foreach ($applicants_in_future_exam_session as $a) {
				$message .= "<li>".htmlentities($a["first_name"]." ".$a["last_name"])."</li>";
				array_push($data["future"], $a["people_id"]);
			}
			$message .= "</ul>";
			$message .= "</li>";
		}
		if (count($applicants_different_exam_center) > 0) {
			$message .= "<li>";
			$message .= "The following applicants are already assigned to another exam center, and will be assigned to the new exam center:<ul>";
			foreach ($applicants_different_exam_center as $a) {
				$message .= "<li>".htmlentities($a["first_name"]." ".$a["last_name"])."</li>";
				array_push($data["different"], $a["people_id"]);
			}
			$message .= "</ul>";
			$message .= "</li>";
		}
		$message .= "</ul>";
		
		// check if confirmation is still ok
		if ($confirm_data <> null) {
			$cmp = array_intersect_assoc($data, $conrifm_data);
			if (count($cmp) == count($data)) {
				// confirmation is ok, let's do it!
				SQLQuery::create()->updateByKeys("Applicant", array(array(
					$data["ok"],
					array("information_session"=>$is_id)
				)));
				SQLQuery::create()->updateByKeys("Applicant", array(array(
					$data["different"],
					array("information_session"=>$is_id, "exam_center"=>$is["exam_center_id"])
				)));
				SQLQuery::create()->updateByKeys("Applicant", array(array(
					$data["future"],
					array("information_session"=>$is_id, "exam_center"=>$is["exam_center_id"], "exam_session"=>null, "exam_center_room"=>null)
				)));
				SQLQuery::create()->updateByKeys("Applicant", array(array(
					$data["past"],
					array("information_session"=>$is_id)
				)));
				if (!PNApplication::hasErrors()) {
					SQLQuery::commitTransaction();
					echo "true";
				}
				return;
			}
		}
		echo "{confirm_message:".json_encode($message).",confirm_data:".json_encode($data)."}";
	}
	
}
?>