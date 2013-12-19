<?php 
class calendar extends Component {
	
	public function init() {
		PNApplication::$instance->user_management->local_user_created->listen($this, "local_user_created");
	}
	
	public function create_shared_calendar($calendar_name, $readable_rights, $writable_rights) {
		foreach ($readable_rights as $name=>$value)
			if (!PNApplication::$instance->user_management->has_right($name, $value)) {
				PNApplication::error("You cannot create a calendar with right ".$name." because you don't have this right");
				return false;
			}
		foreach ($writable_rights as $name=>$value)
			if (!PNApplication::$instance->user_management->has_right($name, $value)) {
				PNApplication::error("You cannot create a calendar with right ".$name." because you don't have this right");
				return false;
			}
		if (count($writable_rights) == 0) {
			PNApplication::error("You must specify a write access for the calendar");
			return false;
		}
		$calendar_id = SQLQuery::create()->bypass_security()->insert("Calendar", array("name"=>$calendar_name,"type"=>"internal"));
		foreach ($readable_rights as $name=>$value)
			SQLQuery::create()->bypass_security()->insert("CalendarRights", array("calendar"=>$calendar_id,"right_name"=>$name,"right_value"=>$value,"writable"=>false));
		foreach ($writable_rights as $name=>$value)
			SQLQuery::create()->bypass_security()->insert("CalendarRights", array("calendar"=>$calendar_id,"right_name"=>$name,"right_value"=>$value,"writable"=>true));
		return $calendar_id;
	}
	
	public function create_user_calendar($user_id) {
		$calendar_id = SQLQuery::create()->bypass_security()->insert("Calendar", array("name"=>"Personal","type"=>"internal"));
		SQLQuery::create()->bypass_security()->insert("UserCalendar", array("user"=>$user_id,"calendar"=>$calendar_id));
		return $calendar_id;
	}
	
	public function local_user_created($user_id) {
		$this->create_user_calendar($user_id);
	}

	public function canReadCalendar($calendar_id) {
		$r = SQLQuery::create()->bypass_security()->select("UserCalendar")->where("calendar",$calendar_id)->where("user",PNApplication::$instance->user_management->user_id)->execute_single_row();
		if ($r <> null) return true;
		$rights = SQLQuery::create()->bypass_security()->select("CalendarRights")->where("calendar", $calendar_id)->execute();
		foreach ($rights as $r)
			if (PNApplication::$instance->user_management->has_right($r["right_name"], $r["right_value"]))
				return true;
		return false;
	}
	
	public function canWriteCalendar($calendar_id) {
		$r = SQLQuery::create()->bypass_security()->select("UserCalendar")->where("calendar",$calendar_id)->where("user",PNApplication::$instance->user_management->user_id)->execute_single_row();
		if ($r <> null) return true;
		$rights = SQLQuery::create()->bypass_security()->select("CalendarRights")->where("calendar", $calendar_id)->where("writable",true)->execute();
		foreach ($rights as $r)
			if (PNApplication::$instance->user_management->has_right($r["right_name"], $r["right_value"]))
				return true;
		return false;
	}
	
	public function getAccessibleCalendars() {
		$ids = array();
		$res = SQLQuery::create()->bypass_security()->select("UserCalendar")->where("user",PNApplication::$instance->user_management->user_id)->execute();
		foreach ($res as $r)
			array_push($ids, $r["calendar"]);
		$res = SQLQuery::create()->bypass_security()->select("CalendarRights")->execute();
		foreach ($res as $r) {
			if (in_array($r["calendar"], $ids)) continue;
			if (PNApplication::$instance->user_management->has_right($r["right_name"], $r["right_value"]))
				array_push($ids, $r["calendar"]);
		}
		return $ids;
	}

	public function getWritableCalendars() {
		$ids = array();
		$res = SQLQuery::create()->bypass_security()->select("UserCalendar")->where("user",PNApplication::$instance->user_management->user_id)->execute();
		foreach ($res as $r)
			array_push($ids, $r["calendar"]);
		$res = SQLQuery::create()->bypass_security()->select("CalendarRights")->where("writable",true)->execute();
		foreach ($res as $r) {
			if (in_array($r["calendar"], $ids)) continue;
			if (PNApplication::$instance->user_management->has_right($r["right_name"], $r["right_value"]))
				array_push($ids, $r["calendar"]);
		}
		return $ids;
	}
	
	public function getEvent($id){
		$event = SQLQuery::create()
					->bypass_security()
					->select("CalendarEvent")
					->where("id = '".$id."'")
					->execute_single_row();
		return $event;
	}
	
	public function saveEvent($event){
		$calendar_id = $event["calendar"];
		if (!$this->canWriteCalendar($calendar_id)) {
			PNApplication::error("Access denied: you cannot modify this calendar.");
			return;
		}
		if (isset($event["id"])) {
			// this is an update
			// check the event belongs to the calendar
			$res = SQLQuery::create()->bypass_security()->select("CalendarEvent")->where("id",$event["id"])->execute_single_row();
			if ($res == null) {
				PNApplication::error("Invalid event id: does not exist");
				return;
			}
			if ($res["calendar"] <> $calendar_id) {
				PNApplication::error("Invalid event id: does not belong to the given calendar");
				return;
			}
			if (isset($event["uid"]) && $event["uid"] <> $res["uid"]) {
				PNApplication::error("Event id and uid do not match");
				return;
			}
			$data = array();
			$data["start"] = $event["start"];
			$data["end"] = $event["end"];
			$data["all_day"] = $event["all_day"];
			$data["last_modified"] = time();
			$data["title"] = $event["title"];
			$data["description"] = $event["description"];
			SQLQuery::create()->bypass_security()->update_by_key("CalendarEvent", $event["id"], $data);
			if (PNApplication::has_errors())
				return;
			$event_id = $event["id"];
			$event_uid = $res["uid"];
		} else {
			// this is a new event
			$data["calendar"] = $calendar_id;
			$data["uid"] = $calendar_id."-".time()."-".rand(0, 100000)."@pn.".PNApplication::$instance->current_domain;
			$data["start"] = $event["start"];
			$data["end"] = $event["end"];
			$data["all_day"] = $event["all_day"];
			$data["last_modified"] = time();
			$data["title"] = $event["title"];
			$data["description"] = $event["description"];
			$event_id = SQLQuery::create()->bypass_security()->insert("CalendarEvent", $data);
			$event_uid = $data["uid"];
			if (PNApplication::has_errors()) return;
		}
		if (isset($event["frequency"])) {
			// TODO
		}
		return array("id"=>$event_id,"uid"=>$event_uid);
	}
	
	public function removeEvent($event_id, $calendar_id){
		if (!$this->canWriteCalendar($calendar_id)) {
			PNApplication::error("Access denied: you cannot modify this calendar.");
			return "false";
		}
		//perform the remove
		SQLQuery::create()->bypass_security()->remove_key("CalendarEvent", $event_id);
		if(PNApplication::has_errors())
			return "false";
		return "true";
	}
	
}
?>