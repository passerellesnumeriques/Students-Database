<?php 
class service_interview_save_center extends Service {
	
	public function getRequiredRights() { return array("manage_interview_center"); }
	
	public function documentation() { echo "Save all information related to an interview center"; }
	public function inputDocumentation() {
		echo "<ul>";
			echo "<li><code>center</code>: ExamCenter information:<ul>";
				echo "<li><code>id</code>: center id, or -1 for a new center</li>";
				echo "<li><code>name</code>: if set, the name will be updated</li>";
				echo "<li><code>geographic_area</code>: if set, this is a new geographic area ID</li>";
			echo "</ul></li>";
			echo "<li><code>partners</code>: if set, this is the new list of partner organizations:<ul>";
				echo "<li><code>organization</code>: ID of the organization</li>";
				echo "<li><code>host</code>: true if this is the host of the interviews sessions</li>";
				echo "<li><code>host_address_id</code>: ID of the PostalAddress of the partner</li>";
				echo "<li><code>selected_contact_points_id</code>: list of People ID which are contact points of the organization</li>";
			echo "</ul></li>";
			echo "<li><code>linked_exam_centers</code>: if set, list of Exam Center ID linked to this interview center";
			echo "<li><code>applicants</code>: if set, this is the new list of applicants, and their assignment:<ul>";
				echo "<li><code>people_id</code>: ID of the applicant</li>";
				echo "<li><code>interview_session_id</code>: session assigned, or null if not assigned to a session</li>";
			echo "</ul></li>";
		echo "</ul>";
	}
	public function outputDocumentation() {
		echo "<ul>";
			echo "<li><code>id</code>: the InterviewCenter ID</li>";
			echo "<li><code>sessions_ids</code>: list of {given_id,new_id} giving the ID of created sessions</li>";
		echo "</ul>";
	}
	
	public function execute(&$component, $input) {
		$center = $input["center"];
		$center_id = intval($center["id"]);
		if ($center_id == null || $center_id < 0) $center_id = -1;
		$is_new = $center_id == -1;
		
		SQLQuery::startTransaction();
		$output = array();
		
		// 1 - Save InterviewCenter
		if ($center_id == -1 && !isset($center["geographic_area"])) {
			// geographic area mandatory
			PNApplication::error("Missing geographic area");
			return;
		}
		if (!isset($center["name"]) && $center_id == -1) {
			// first save, but no name: use geographic location
			$center["name"] = PNApplication::$instance->geography->getGeographicAreaText($center["geographic_area"]);
			if ($center["name"] == null) return;
		}
		unset($center["id"]);
		if ($center_id == -1) {
			// insert the new center
			$center_id = SQLQuery::create()->insert("InterviewCenter", $center);
		} else if (isset($center["name"]) || isset($center["geographic_area"])) {
			SQLQuery::create()->updateByKey("InterviewCenter", $center_id, $center);
		}
		$output["id"] = $center_id;
		
		// 2 - Save partners
		if (isset($input["partners"])) {
			// remove any partner and their contact points previously saved
			if (!$is_new) {
				SQLQuery::create()->removeLinkedData("InterviewCenterPartner", "InterviewCenter", $center_id);
				SQLQuery::create()->removeLinkedData("InterviewCenterContactPoint", "InterviewCenter", $center_id);
			}
			if (count($input["partners"]) > 0) {
				// add partners
				$contacts = array();
				for ($i = 0; $i < count($input["partners"]); $i++) {
					if (isset($input["partners"][$i]["selected_contact_points_id"])) {
						if (count($input["partners"][$i]["selected_contact_points_id"]) > 0)
							$contacts[$input["partners"][$i]["organization"]] = $input["partners"][$i]["selected_contact_points_id"];
						unset($input["partners"][$i]["selected_contact_points_id"]);
					}
					// put the center id
					$input["partners"][$i]["interview_center"] = $center_id;
					// change name of columns
					$input["partners"][$i]["host_address"] = $input["partners"][$i]["host_address_id"];
					unset($input["partners"][$i]["host_address_id"]);
				}
				SQLQuery::create()->insertMultiple("InterviewCenterPartner", $input["partners"]);
				$list = array();
				foreach ($contacts as $org_id=>$contact_list)
					foreach ($contact_list as $contact_id)
						array_push($list, array(
							"interview_center"=>$center_id,
							"organization"=>$org_id,
							"people"=>$contact_id
						));
				if (count($list) > 0)
					SQLQuery::create()->insertMultiple("InterviewCenterContactPoint", $list);
			}
		}
		
		// 3 - Save linked Exam Centers
		if (isset($input["linked_exam_centers"])) {
			// remove current links
			if (!$is_new) {
				$to_remove = SQLQuery::create()->select("InterviewCenterExamCenter")->whereValue("InterviewCenterExamCenter","interview_center",$center_id)->execute();
				SQLQuery::create()->removeRows("InterviewCenterExamCenter",$to_remove);
			}
			// for linked is, make sure it is not actually linked to another center
			if (count($input["linked_exam_centers"]) > 0) {
				$already_linked = SQLQuery::create()->select("InterviewCenterExamCenter")->whereIn("InterviewCenterExamCenter","exam_center",$input["linked_exam_centers"])->execute();
				if (count($already_linked) > 0) {
					PNApplication::error("Exam center(s) already linked to another interview center");
					return;
				}
			}
			// add links
			$list = array();
			foreach ($input["linked_exam_centers"] as $ec_id)
				array_push($list, array("interview_center"=>$center_id, "exam_center"=>$ec_id));
			if (count($list) > 0)
				SQLQuery::create()->insertMultiple("InterviewCenterExamCenter", $list);
		}
		
		// Before modifying the sessions, we need to know which previous session was in the past (so applicants may already have results)
		$q = SQLQuery::create()->select("InterviewSession")->whereValue("InterviewSession","interview_center",$center_id);
		PNApplication::$instance->calendar->joinCalendarEvent($q, "InterviewSession", "event");
		PNApplication::$instance->calendar->whereEventInThePast($q, false);
		$q->field("InterviewSession","event");
		$past_sessions = $q->executeSingleField();
		if (count($past_sessions) > 0)
			$applicants_possible_results = SQLQuery::create()->select("Applicant")->whereIn("Applicant","interview_session",$past_sessions)->field("Applicant","people")->field("Applicant","interview_session")->execute();
		else
			$applicants_possible_results = array();
		
		$applicants_remove_results = array();
		
		// 5 - Save sessions
		if (isset($input["sessions"])) {
			// get the list of existing sessions
			if ($is_new)
				$existing_sessions = array();
			else
				$existing_sessions = SQLQuery::create()->select("InterviewSession")->whereValue("InterviewSession","interview_center",$center_id)->field("event")->executeSingleField();
			// save existing events which are still present, and new events
			$insert_sessions = array();
			$update_sessions = array();
			foreach ($input["sessions"] as $session) {
				$event = $session["event"];
				// set calendar id
				$event["calendar"] = $component->getCalendarId();
				// set the title
				if (!isset($center["name"])) $center["name"] = SQLQuery::create()->select("InterviewCenter")->whereValue("InterviewCenter","id",$center_id)->field("name")->executeSingleValue();
				$event["title"] = "Interviews @ ".$center["name"];
				// set information
				$attendees = @$event["attendees"];
				$event["attendees"] = array();
				array_push($event["attendees"], array(
					"name"=>"Selection",
					"role"=>"NONE",
					"organizer"=>true,
					"participation"=>"YES"
				));
				// TODO add creator ?
				if ($attendees <> null) {
					$people_ids = array();
					foreach ($attendees as $a) {
						if ($a["people"] > 0) array_push($people_ids, $a["people"]);
						$a["role"] = "REQUESTED";
					}
					if (count($people_ids) > 0) {
						$emails = PNApplication::$instance->contact->getPeoplesPreferredEMail($people_ids, true);
						foreach ($attendees as $a) if ($a["people"] > 0) $a["email"]=$emails[$a["people"]];
					}
					foreach ($attendees as $a) {
						if ($a["organizer"] || $a["name"] == "Selection") continue;
						unset($a["organizer"]);
						array_push($event["attendees"], $a);
					}
				}
				$event["app_link"] = "popup:/dynamic/selection/page/interview/center_profile?id=".$center_id;
				$event["app_link_name"] = "This event is an Interviews session: click to open the interview center";
				if ($event["id"] < 0) {
					// this is a new session
					$given_id = $event["id"];
					unset($event["id"]);
					unset($event["uid"]);
					PNApplication::$instance->calendar->saveEvent($event);
					if (!isset($output["sessions_ids"])) $output["sessions_ids"] = array();
					array_push($output["sessions_ids"], array("given_id"=>$given_id,"new_id"=>$event["id"]));
					array_push($insert_sessions, array("event"=>$event["id"],"interview_center"=>$center_id,"every_minutes"=>$session["every_minutes"],"parallel_interviews"=>$session["parallel_interviews"]));
				} else {
					// still present, remove it from the list of sessions to remove
					for ($i = 0; $i < count($existing_sessions); $i++)
						if ($existing_sessions[$i] == $event["id"]) {
							array_splice($existing_sessions, $i, 1);
							break;
						}
					// check if the session was in the past, and is now in the future
					if (in_array($event["id"], $past_sessions)) {
						//  was in the past
						$start = intval($event["start"]);
						if ($start > time()) {
							// now in the future => all applicants previously assigned to this session must have their results reset
							foreach ($applicants_possible_results as $app)
								if ($app["interview_session"] == $event["id"])
									array_push($applicants_remove_results, $app["people"]);
						}
					}
					// update the event
					PNApplication::$instance->calendar->saveEvent($event);
					// update the session
					array_push($update_sessions,array(array($event["id"]),array("every_minutes"=>$session["every_minutes"],"parallel_interviews"=>$session["parallel_interviews"])));
				}
			}
			// save links to new events
			if (count($insert_sessions) > 0)
				SQLQuery::create()->insertMultiple("InterviewSession", $insert_sessions);
			// update existing ones
			if (count($update_sessions) > 0)
				SQLQuery::create()->updateByKeys("InterviewSession",$update_sessions);
			// remove sessions not anymore present
			if (count($existing_sessions) > 0)
				SQLQuery::create()->removeKeys("InterviewSession", $existing_sessions);
		}
		
		// 6 - Save applicants
		if (isset($input["applicants"])) {
			// get the current list of applicants
			if ($is_new)
				$current_applicants = array();
			else
				$current_applicants = SQLQuery::create()->select("Applicant")->whereValue("Applicant","interview_center", $center_id)->field("people")->executeSingleField();
			// get the list of sessions in the past
			$q = SQLQuery::create()->select("InterviewSession")->whereValue("InterviewSession","interview_center",$center_id);
			PNApplication::$instance->calendar->joinCalendarEvent($q, "InterviewSession", "event");
			PNApplication::$instance->calendar->whereEventInThePast($q, false);
			$q->field("InterviewSession","event");
			$past_sessions = $q->executeSingleField();
				
			$applicants_updates = array();
			for ($i = 0; $i < count($input["applicants"]); $i++) {
				$a = $input["applicants"][$i];
				// get the id
				$people_id = $a["people_id"]; unset($a["people_id"]);
				// change the names
				$a["interview_session"] = $a["interview_session_id"]; unset($a["interview_session_id"]);
				// update the ids if needed
				if ($a["interview_session"] < 0)
					foreach ($output["sessions_ids"] as $sid)
						if ($sid["given_id"] == $a["interview_session"]) {
							$a["interview_session"] = $sid["new_id"];
							break;
						}
				// put the center id
				$a["interview_center"] = $center_id;
				// if the applicant was assigned to a past session, and it is now in a future session or without session, we must remove all results of the applicant
				if ($a["interview_session"] == null || !in_array($a["interview_session"], $past_sessions)) {
					// not anymore in a past session
					if (!in_array($people_id, $applicants_remove_results)) {
						// not yet in the list
						$found = false;
						foreach ($applicants_possible_results as $app) if ($app["people"] == $people_id) { $found = true; break; }
						if ($found) {
							// this applicant may have results
							array_push($applicants_remove_results, $people_id);
						}
					}
				}
				// update the applicant
				array_push($applicants_updates, array(array($people_id),$a));
				// if the applicant was already in this exam center, remove it from the list
				for ($j = 0; $j < count($current_applicants); ++$j)
					if ($current_applicants[$j] == $people_id) {
						array_splice($current_applicants, $j, 1);
						break;
					}
			}
			if (count($applicants_updates) > 0)
				SQLQuery::create()->updateByKeys("Applicant", $applicants_updates);
			// each applicant which is not anymore assigned to this exam center, may have results
			foreach ($applicants_possible_results as $app)
				if (in_array($app["people"], $current_applicants) && !in_array($app["people"], $applicants_remove_results))
					array_push($applicants_remove_results, $app["people"]);
			// remove applicants not anymore on this center
			if (count($current_applicants) > 0)
				SQLQuery::create()->updateByKeys("Applicant", array(array($current_applicants, array("interview_center"=>null,"interview_session"=>null))));
		}

		// TODO remove results
		
		if (PNApplication::hasErrors()) {
			SQLQuery::rollbackTransaction();
			echo "false";
			return;
		}
		
		SQLQuery::commitTransaction();
		echo json_encode($output);
	}
}
?>