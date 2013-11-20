<?php 
class calendar extends Component {
	
	public function init() {
		PNApplication::$instance->user_management->local_user_created->listen($this, "local_user_created");
	}
	
	public function create_shared_calendar($name, $readable_rights, $writable_rights) {
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
		$calendar_id = SQLQuery::create()->bypass_security()->insert("Calendar", array("name"=>$name,"type"=>"internal"));
		foreach ($readable_rights as $name=>$value)
			SQLQuery::create()->bypass_security()->insert("CalendarRights", array("calendar"=>$calendar_id,"right_name"=>$name,$right_value=>$value,"writable"=>false));
		foreach ($writable_rights as $name=>$value)
			SQLQuery::create()->bypass_security()->insert("CalendarRights", array("calendar"=>$calendar_id,"right_name"=>$name,$right_value=>$value,"writable"=>true));
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
	
}
?>