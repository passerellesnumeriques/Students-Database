<?php 
class calendar extends Component {
	
	public function init() {
		PNApplication::$instance->user_management->local_user_created->listen($this, "local_user_created");
	}
	
	public function local_user_created($user_id) {
		$calendar_id = SQLQuery::create()->bypass_security()->insert("Calendar", array("name"=>"Personal","type"=>"internal"));
		SQLQuery::create()->bypass_security()->insert("UserCalendar", array("user"=>$user_id,"calendar"=>$calendar_id));
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
	
}
?>