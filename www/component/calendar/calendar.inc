<?php
/**
 * Component managing calendars
 */ 
class calendar extends Component {
	
	public function init() {
		PNApplication::$instance->user_management->local_user_created->listen($this, "localUserCreated");
	}
	
	/**
	 * Create a new shared (not presonal) calendar. 
	 * @param string $calendar_name name of the calendar
	 * @param array $readable_rights associative array of right name =&gt; right value, needed to access the calendar in read access
	 * @param array $writable_rights associative array of right name =&gt; right value, needed to access the calendar in write access
	 * @return boolean|number the calendar id, or false if it failed.
	 */
	public function createSharedCalendar($calendar_name, $readable_rights, $writable_rights) {
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
	
	/**
	 * Create a calendar, owned by the given user
	 * @param number $user_id user id
	 * @return number the calendar id
	 */
	public function createUserCalendar($user_id) {
		$calendar_id = SQLQuery::create()->bypass_security()->insert("Calendar", array("name"=>"Personal","type"=>"internal"));
		SQLQuery::create()->bypass_security()->insert("UserCalendar", array("user"=>$user_id,"calendar"=>$calendar_id));
		return $calendar_id;
	}
	
	/**
	 * Called when an event is raised to signal that a new local user has just been created
	 * @param number $user_id user id
	 */
	public function localUserCreated($user_id) {
		$this->createUserCalendar($user_id);
	}

	/**
	 * Check if the current user can read the calendar
	 * @param number $calendar_id calendar id
	 * @return boolean true if the current user can read it
	 */
	public function canReadCalendar($calendar_id) {
		$r = SQLQuery::create()->bypass_security()->select("UserCalendar")->where("calendar",$calendar_id)->where("user",PNApplication::$instance->user_management->user_id)->execute_single_row();
		if ($r <> null) return true;
		$rights = SQLQuery::create()->bypass_security()->select("CalendarRights")->where("calendar", $calendar_id)->execute();
		foreach ($rights as $r)
			if (PNApplication::$instance->user_management->has_right($r["right_name"], $r["right_value"]))
				return true;
		return false;
	}
	
	/**
	 * Check if the current user can modify the calendar
	 * @param number $calendar_id calendar id
	 * @return boolean true if the current user can modify it
	 */
	public function canWriteCalendar($calendar_id) {
		$r = SQLQuery::create()->bypass_security()->select("UserCalendar")->where("calendar",$calendar_id)->where("user",PNApplication::$instance->user_management->user_id)->execute_single_row();
		if ($r <> null) return true;
		$rights = SQLQuery::create()->bypass_security()->select("CalendarRights")->where("calendar", $calendar_id)->where("writable",true)->execute();
		foreach ($rights as $r)
			if (PNApplication::$instance->user_management->has_right($r["right_name"], $r["right_value"]))
				return true;
		return false;
	}
	
	/**
	 * Return a list of calendars the current user can access to
	 * @return multitype:number ids of the calendars the current user can access to
	 */
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

	/**
	 * Return a list of calendars the current user can modify
	 * @return multitype:number ids of the calendars the current user can modify
	 */
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
	
	/** Remove an event
	 * @param number $event_id id of the event to remove
	 * @param number $calendar_id calendar id from which the event is
	 * @return boolean true if it succeed
	 */
	public function removeEvent($event_id, $calendar_id){
		if (!$this->canWriteCalendar($calendar_id)) {
			PNApplication::error("Access denied: you cannot modify this calendar.");
			return false;
		}
		// check the event belongs to the calendar
		$cid = SQLQuery::create()->bypass_security()->select("CalendarEvent")->where_value("CalendarEvent", "id", $event_id)->field("CalendarEvent", "calendar")->execute_single_value();
		if ($cid == null) return false;
		if ($cid <> $calendar_id) return false;
		//perform the remove
		SQLQuery::create()->bypass_security()->remove_key("CalendarEvent", $event_id);
		return true;
	}
	
}
?>