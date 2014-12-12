<?php 
class service_si_save_visits extends Service {
	
	public function getRequiredRights() { return array("edit_social_investigation"); }
	
	public function documentation() { echo "Save list of events corresponding to the family visits"; }
	public function inputDocumentation() { echo "applicant, visits"; }
	public function outputDocumentation() { echo "list of {given_id,new_id} for the new events"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		$applicant_id = $input["applicant"];
		$events = $input["visits"];
		$calendar_id = $component->getCalendarId();
		$applicant = PNApplication::$instance->people->getPeople($applicant_id, true);
		$applicant_sel_id = SQLQuery::create()->select("Applicant")->whereValue("Applicant","people",$applicant_id)->field("applicant_id")->executeSingleValue();
		$existing = SQLQuery::create()->select("SocialInvestigation")->whereValue("SocialInvestigation","applicant",$applicant_id)->field("event")->executeSingleField();
		$new_ids = array();
		foreach ($events as $event) {
			if ($event["start"] == null) continue;
			
			$ev = array(
				"id"=>$event["id"],
				"calendar_id"=>$calendar_id,
				"start"=>$event["start"],
				"end"=>$event["start"]+24*60*60-1,
				"all_day"=>1,
				"title"=>"SI: Visit of ".$applicant["first_name"]." ".$applicant["last_name"]." (ID $applicant_sel_id)",
				"description"=>"Visit of the family of ".$applicant["first_name"]." ".$applicant["last_name"]." (ID $applicant_sel_id) for Social Investigations",
				"app_link"=>"popup:/dynamic/people/page/profile?people=".$applicant_id."&page=".urlencode("Social Investigation"),
				"app_link_name"=>"Click to see social investigations data for this applicant",
				"attendees"=>array(array(
					"name"=>"Selection",
					"organizer"=>1,
					"role"=>"NONE",
					"participation"=>"YES"
				))
			);
			foreach ($event["attendees"] as $a) {
				if ($a["people"] <> null)
					array_push($ev["attendees"], array("people"=>$a["people"],"role"=>"REQUESTED","participation"=>"YES"));
				else
					array_push($ev["attendees"], array("name"=>$a["name"],"role"=>"REQUESTED","participation"=>"YES"));
			}
				
			if ($event["id"] > 0) {
				$exist = null;
				for ($i = 0; $i < count($existing); $i++)
					if ($existing[$i] == $event["id"]) {
						$exist = $existing[$i];
						array_splice($existing, $i, 1);
						break;
					}
				if ($exist == null) {
					PNApplication::error("Invalid event id");
					return;
				}
				$ev["uid"] = $exist["uid"];
				PNApplication::$instance->calendar->saveEvent($ev);
			} else {
				$given_id = $event["id"];
				unset($ev["id"]);
				PNApplication::$instance->calendar->saveEvent($ev);
				$new_id = $ev["id"];
				array_push($new_ids, array("given_id"=>$given_id,"new_id"=>$new_id));
				SQLQuery::create()->insert("SocialInvestigation",array("applicant"=>$applicant_id,"event"=>$new_id));
			}
		}
		foreach ($existing as $removed)
			PNApplication::$instance->calendar->removeEvent($removed["id"], $calendar_id);
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo json_encode($new_ids);
		}
	}
	
}
?>