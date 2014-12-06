<?php 
class service_exam_save_center extends Service {
	
	public function getRequiredRights() { return array("manage_exam_center"); }
	
	public function documentation() { echo "Save all information related to an exam center"; }
	public function inputDocumentation() {
		echo "<ul>";
			echo "<li><code>center</code>: ExamCenter information:<ul>";
				echo "<li><code>id</code>: center id, or -1 for a new center</li>";
				echo "<li><code>name</code>: if set, the name will be updated</li>";
				echo "<li><code>geographic_area</code>: if set, this is a new geographic area ID</li>";
			echo "</ul></li>";
			echo "<li><code>partners</code>: if set, this is the new list of partner organizations:<ul>";
				echo "<li><code>organization</code>: ID of the organization</li>";
				echo "<li><code>host</code>: true if this is the host of the exam</li>";
				echo "<li><code>host_address_id</code>: ID of the PostalAddress of the partner</li>";
				echo "<li><code>selected_contact_points_id</code>: list of People ID which are contact points of the organization</li>";
			echo "</ul></li>";
			echo "<li><code>linked_is</code>: if set, list of InformationSession ID linked to this exam center";
			echo "<li><code>rooms</code>: if set, this is the new list of rooms:<ul>";
				echo "<li><code>id</code>: ID of the existing room, or a negative value for a new room (the created ID will be returned in output)</li>";
				echo "<li><code>name</code>: if set, the new name of the room</li>";
				echo "<li><code>capacity</code>: if set, the new capacity of the room</li>";
			echo "</ul></li>";
			echo "<li><code>applicants</code>: if set, this is the new list of applicants, and their assignment:<ul>";
				echo "<li><code>people_id</code>: ID of the applicant</li>";
				echo "<li><code>exam_session_id</code>: session assigned, or null if not assigned to a session/room</li>";
				echo "<li><code>exam_center_room_id</code>: room assigned, or null if not assigned to a sessions/room</li>";
			echo "</ul></li>";
		echo "</ul>";
	}
	public function outputDocumentation() {
		echo "<ul>";
			echo "<li><code>id</code>: the ExamCenter ID</li>";
			echo "<li><code>rooms_ids</code>: list of {given_id,new_id} giving the ID of created rooms</li>";
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
		
		// 1 - Save ExamCenter
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
			$center_id = SQLQuery::create()->insert("ExamCenter", $center);
		} else if (isset($center["name"]) || isset($center["geographic_area"])) {
			SQLQuery::create()->updateByKey("ExamCenter", $center_id, $center);
		}
		$output["id"] = $center_id;
		
		// 2 - Save partners
		if (isset($input["partners"])) {
			// remove any partner and their contact points previously saved
			if (!$is_new) {
				SQLQuery::create()->removeLinkedData("ExamCenterPartner", "ExamCenter", $center_id);
				SQLQuery::create()->removeLinkedData("ExamCenterContactPoint", "ExamCenter", $center_id);
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
					$input["partners"][$i]["exam_center"] = $center_id;
					// change name of columns
					$input["partners"][$i]["host_address"] = $input["partners"][$i]["host_address_id"];
					unset($input["partners"][$i]["host_address_id"]);
				}
				SQLQuery::create()->insertMultiple("ExamCenterPartner", $input["partners"]);
				$list = array();
				foreach ($contacts as $org_id=>$contact_list)
					foreach ($contact_list as $contact_id)
						array_push($list, array(
							"exam_center"=>$center_id,
							"organization"=>$org_id,
							"people"=>$contact_id
						));
				if (count($list) > 0)
					SQLQuery::create()->insertMultiple("ExamCenterContactPoint", $list);
			}
		}
		
		// 3 - Save linked IS
		if (isset($input["linked_is"])) {
			// remove current links
			if (!$is_new) {
				$to_remove = SQLQuery::create()->select("ExamCenterInformationSession")->whereValue("ExamCenterInformationSession","exam_center",$center_id)->execute();
				SQLQuery::create()->removeRows("ExamCenterInformationSession",$to_remove);
			}
			// for linked is, make sure it is not actually linked to another center
			if (count($input["linked_is"]) > 0) {
				$already_linked = SQLQuery::create()->select("ExamCenterInformationSession")->whereIn("ExamCenterInformationSession","information_session",$input["linked_is"])->execute();
				if (count($already_linked) > 0) {
					PNApplication::error("Information session(s) already linked to another exam center");
					return;
				}
			}
			// add links
			$list = array();
			foreach ($input["linked_is"] as $is_id)
				array_push($list, array("exam_center"=>$center_id, "information_session"=>$is_id));
			if (count($list) > 0)
				SQLQuery::create()->insertMultiple("ExamCenterInformationSession", $list);
		}
		
		// 4 - Save rooms
		if (isset($input["rooms"])) {
			// get the list of existing rooms
			if ($is_new)
				$existing_rooms = array();
			else
				$existing_rooms = SQLQuery::create()->select("ExamCenterRoom")->whereValue("ExamCenterRoom","exam_center",$center_id)->field("id")->executeSingleField();
			// update existing rooms which are still present
			$update_rooms = array();
			foreach ($input["rooms"] as $room) {
				if ($room["id"] < 0) {
					// this is a new room
					if (!isset($output["rooms_ids"])) $output["rooms_ids"] = array();
					$new_id = SQLQuery::create()->insert("ExamCenterRoom", array("exam_center"=>$center_id, "name"=>$room["name"], "capacity"=>$room["capacity"]));
					array_push($output["rooms_ids"], array("given_id"=>$room["id"],"new_id"=>$new_id)); 
				} else {
					// still present, remove it from the list of rooms to remove
					for ($i = 0; $i < count($existing_rooms); $i++)
						if ($existing_rooms[$i] == $room["id"]) {
							array_splice($existing_rooms, $i, 1);
							break;
						}
					// update the room
					array_push($update_rooms, array(array($room["id"]), array("name"=>$room["name"],"capacity"=>$room["capacity"])));
				}
			}
			if (count($update_rooms) > 0)
				SQLQuery::create()->updateByKeys("ExamCenterRoom", $update_rooms);
			// remove remaining rooms which are not present anymore
			if (count($existing_rooms) > 0)
				SQLQuery::create()->removeByKeys("ExamCenterRoom", $existing_rooms);
		}
		
		// Before modifying the sessions, we need to know which previous session was in the past (so applicants may already have results)
		$q = SQLQuery::create()->select("ExamSession")->whereValue("ExamSession","exam_center",$center_id);
		PNApplication::$instance->calendar->joinCalendarEvent($q, "ExamSession", "event");
		PNApplication::$instance->calendar->whereEventInThePast($q, false);
		$q->field("ExamSession","event");
		$past_sessions = $q->executeSingleField();
		if (count($past_sessions) > 0)
			$applicants_possible_results = SQLQuery::create()->select("Applicant")->whereIn("Applicant","exam_session",$past_sessions)->field("Applicant","people")->field("Applicant","exam_session")->execute();
		else
			$applicants_possible_results = array();
		
		$applicants_remove_results = array();
		
		// 5 - Save sessions
		if (isset($input["sessions"])) {
			// get the list of existing sessions
			if ($is_new)
				$existing_sessions = array();
			else
				$existing_sessions = SQLQuery::create()->select("ExamSession")->whereValue("ExamSession","exam_center",$center_id)->field("event")->executeSingleField();
			// save existing events which are still present, and new events
			$insert_sessions = array();
			foreach ($input["sessions"] as $event) {
				// set calendar id
				$event["calendar"] = $component->getCalendarId();
				// set the title
				if (!isset($center["name"])) $center["name"] = SQLQuery::create()->select("ExamCenter")->whereValue("ExamCenter","id",$center_id)->field("name")->executeSingleValue();
				$event["title"] = "Written Exam @ ".$center["name"];
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
				$event["app_link"] = "popup:/dynamic/selection/page/exam/center_profile?id=".$center_id;
				$event["app_link_name"] = "This event is a Written Exam session: click to open the exam center";
				if ($event["id"] < 0) {
					// this is a new session
					$given_id = $event["id"];
					unset($event["id"]);
					unset($event["uid"]);
					PNApplication::$instance->calendar->saveEvent($event);
					if (!isset($output["sessions_ids"])) $output["sessions_ids"] = array();
					array_push($output["sessions_ids"], array("given_id"=>$given_id,"new_id"=>$event["id"]));
					array_push($insert_sessions, array("event"=>$event["id"],"exam_center"=>$center_id));
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
								if ($app["exam_session"] == $event["id"])
									array_push($applicants_remove_results, $app["people"]);
						}
					}
					// update the event
					PNApplication::$instance->calendar->saveEvent($event);
				}
			}
			// save links to new events
			if (count($insert_sessions) > 0)
				SQLQuery::create()->insertMultiple("ExamSession", $insert_sessions);
			// remove sessions not anymore present
			if (count($existing_sessions) > 0)
				SQLQuery::create()->removeKeys("ExamSession", $existing_sessions);
		}
		
		// 6 - Save applicants
		if (isset($input["applicants"])) {
			// get the current list of applicants
			if ($is_new)
				$current_applicants = array();
			else
				$current_applicants = SQLQuery::create()->select("Applicant")->whereValue("Applicant","exam_center", $center_id)->field("people")->executeSingleField();
			// get the list of sessions in the past
			$q = SQLQuery::create()->select("ExamSession")->whereValue("ExamSession","exam_center",$center_id);
			PNApplication::$instance->calendar->joinCalendarEvent($q, "ExamSession", "event");
			PNApplication::$instance->calendar->whereEventInThePast($q, false);
			$q->field("ExamSession","event");
			$past_sessions = $q->executeSingleField();
				
			$applicants_updates = array();
			for ($i = 0; $i < count($input["applicants"]); $i++) {
				$a = $input["applicants"][$i];
				// make sure we cannot assign to a session without a room, or to a room without a session
				if ($a["exam_session_id"] == null || $a["exam_center_room_id"] == null)
					$a["exam_session_id"] = $a["exam_center_room_id"] = null;
				// get the id
				$people_id = $a["people_id"]; unset($a["people_id"]);
				// change the names
				$a["exam_session"] = $a["exam_session_id"]; unset($a["exam_session_id"]);
				$a["exam_center_room"] = $a["exam_center_room_id"]; unset($a["exam_center_room_id"]);
				// update the ids if needed
				if ($a["exam_session"] < 0)
					foreach ($output["sessions_ids"] as $sid)
						if ($sid["given_id"] == $a["exam_session"]) {
							$a["exam_session"] = $sid["new_id"];
							break;
						}
				if ($a["exam_center_room"] < 0)
					foreach ($output["rooms_ids"] as $sid)
						if ($sid["given_id"] == $a["exam_center_room"]) {
							$a["exam_center_room"] = $sid["new_id"];
							break;
						}
				// put the center id
				$a["exam_center"] = $center_id;
				// if the applicant was assigned to a past session, and it is now in a future session or without session, we must remove all results of the applicant
				if ($a["exam_session"] == null || !in_array($a["exam_session"], $past_sessions)) {
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
				SQLQuery::create()->updateByKeys("Applicant", array(array($current_applicants, array("exam_center"=>null,"exam_session"=>null,"exam_center_room"=>null))));
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