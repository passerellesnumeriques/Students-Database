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
	 * @param string $calendar_color default color of the calendar (in 6 hex digits), or null
	 * @param string $calendar_icon url of the icon for this calendar, or null
	 * @param array $readable_rights associative array of right name =&gt; right value, needed to access the calendar in read access
	 * @param array $writable_rights associative array of right name =&gt; right value, needed to access the calendar in write access
	 * @return boolean|number the calendar id, or false if it failed.
	 */
	public function createSharedCalendar($calendar_name, $calendar_color, $calendar_icon, $readable_rights, $writable_rights) {
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
		$calendar_id = SQLQuery::create()->bypassSecurity()->insert("Calendar", 
			array(
				"name"=>$calendar_name,
				"type"=>"internal",
				"color"=>$calendar_color,
				"icon"=>$calendar_icon
			)
		);
		foreach ($readable_rights as $name=>$value)
			SQLQuery::create()->bypassSecurity()->insert("CalendarRights", array("calendar"=>$calendar_id,"right_name"=>$name,"right_value"=>$value,"writable"=>false));
		foreach ($writable_rights as $name=>$value)
			SQLQuery::create()->bypassSecurity()->insert("CalendarRights", array("calendar"=>$calendar_id,"right_name"=>$name,"right_value"=>$value,"writable"=>true));
		return $calendar_id;
	}
	
	/**
	 * Create a calendar, owned by the given user
	 * @param number $user_id user id
	 * @param string $name name of the calendar
	 * @param string $color default color of the calendar or null
	 * @param string $icon icon's URL of the calendar or null
	 * @return number the calendar id
	 */
	public function createUserCalendar($user_id, $name, $color = null, $icon = null) {
		// check number of calendars for this user
		$nb = SQLQuery::create()->bypassSecurity()
			->select("UserCalendar")
			->whereValue("UserCalendar", "user", $user_id)
			->count()
			->executeSingleValue();
		if ($nb >= 50) {
			PNApplication::error("You reached the limit of 50 personal calendars.");
			return;
		}
		// ok, we can create
		SQLQuery::startTransaction();
		try {
			$calendar_id = SQLQuery::create()->bypassSecurity()->insert("Calendar", array(
				"name"=>$name,
				"color"=>$color,
				"icon"=>$icon,
				"type"=>"internal"
			));
			SQLQuery::create()->bypassSecurity()->insert("UserCalendar", array("user"=>$user_id,"calendar"=>$calendar_id));
			SQLQuery::commitTransaction();
			return $calendar_id;
		} catch(Exception $e) {
			SQLQuery::rollbackTransaction();
			throw $e;
		}
	}
	
	/**
	 * Remove the given shared calendar
	 * @param integer $id identifier of the calendar to remove
	 * @return boolean true on success
	 */
	public function removeSharedCalendar($id) {
		if (!$this->canWriteCalendar($id)) {
			PNApplication::error("You cannot remove this calendar");
			return false;
		}
		SQLQuery::create()->bypassSecurity()->removeKey("Calendar", $id);
		return true;
	}
	
	/**
	 * Called when an event is raised to signal that a new local user has just been created
	 * @param number $user_id user id
	 */
	public function localUserCreated($user_id) {
		$this->createUserCalendar($user_id, "Personal", "A0A0FF", null);
	}

	/**
	 * Check if the current user can read the calendar
	 * @param number $calendar_id calendar id
	 * @return boolean true if the current user can read it
	 */
	public function canReadCalendar($calendar_id) {
		$r = SQLQuery::create()->bypassSecurity()->select("UserCalendar")->where("calendar",$calendar_id)->where("user",PNApplication::$instance->user_management->user_id)->executeSingleRow();
		if ($r <> null) return true;
		$rights = SQLQuery::create()->bypassSecurity()->select("CalendarRights")->where("calendar", $calendar_id)->execute();
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
		$r = SQLQuery::create()->bypassSecurity()->select("UserCalendar")->where("calendar",$calendar_id)->where("user",PNApplication::$instance->user_management->user_id)->executeSingleRow();
		if ($r <> null) return true;
		$rights = SQLQuery::create()->bypassSecurity()->select("CalendarRights")->where("calendar", $calendar_id)->where("writable",true)->execute();
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
		$res = SQLQuery::create()->bypassSecurity()->select("UserCalendar")->where("user",PNApplication::$instance->user_management->user_id)->execute();
		foreach ($res as $r)
			array_push($ids, $r["calendar"]);
		$res = SQLQuery::create()->bypassSecurity()->select("CalendarRights")->execute();
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
		$res = SQLQuery::create()->bypassSecurity()->select("UserCalendar")->where("user",PNApplication::$instance->user_management->user_id)->execute();
		foreach ($res as $r)
			array_push($ids, $r["calendar"]);
		$res = SQLQuery::create()->bypassSecurity()->select("CalendarRights")->where("writable",true)->execute();
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
		$cid = SQLQuery::create()->bypassSecurity()->select("CalendarEvent")->whereValue("CalendarEvent", "id", $event_id)->field("CalendarEvent", "calendar")->executeSingleValue();
		if ($cid == null) return false;
		if ($cid <> $calendar_id) return false;
		//perform the remove
		SQLQuery::create()->bypassSecurity()->removeKey("CalendarEvent", $event_id);
		return true;
	}
	
	/**
	 * Save or create an event. If no id is specified, the id and uid attributes of the given event are updated.
	 * @param array $event JSON structure CalendarEvent 
	 * @return true on success, or false in case of failure
	 */
	public function saveEvent(&$event) {
		$calendar_id = $event["calendar_id"];
		if (!$this->canWriteCalendar($calendar_id)) {
			PNApplication::error("Access denied: you cannot modify this calendar.");
			return false;
		}
		if (isset($event["id"])) {
			// this is an update
			// check the event belongs to the calendar
			$res = SQLQuery::create()->bypassSecurity()->select("CalendarEvent")->where("id",$event["id"])->executeSingleRow();
			if ($res == null) {
				PNApplication::error("Invalid event id: does not exist");
				return false;
			}
			if ($res["calendar"] <> $calendar_id) {
				PNApplication::error("Invalid event id: does not belong to the given calendar");
				return false;
			}
			if (isset($event["uid"]) && $event["uid"] <> $res["uid"]) {
				PNApplication::error("Event id and uid do not match");
				return false;
			}
			$data = array();
			$data["start"] = intval($event["start"]);
			$data["end"] = intval($event["end"]);
			$data["all_day"] = $event["all_day"];
			$data["last_modified"] = time();
			$data["title"] = $event["title"];
			$data["description"] = $event["description"];
			$data["organizer"] = $event["organizer"];
			$data["participation"] = $event["participation"];
			$data["role"] = $event["role"];
			$data["app_link"] = @$event["app_link"];
			$data["app_link_name"] = @$event["app_link_name"];
			SQLQuery::create()->bypassSecurity()->updateByKey("CalendarEvent", $event["id"], $data);
			if (PNApplication::hasErrors())
				return false;
			$event["uid"] = $res["uid"];
		} else {
			// this is a new event
			$data["calendar"] = $calendar_id;
			$data["uid"] = $calendar_id."-".time()."-".rand(0, 100000)."@pn.".PNApplication::$instance->current_domain;
			$data["start"] = intval($event["start"]);
			$data["end"] = intval($event["end"]);
			$data["all_day"] = $event["all_day"];
			$data["last_modified"] = time();
			$data["title"] = $event["title"];
			$data["description"] = $event["description"];
			$data["organizer"] = $event["organizer"];
			$data["participation"] = $event["participation"];
			$data["role"] = $event["role"];
			$data["app_link"] = @$event["app_link"];
			$data["app_link_name"] = @$event["app_link_name"];
			$event["id"] = SQLQuery::create()->bypassSecurity()->insert("CalendarEvent", $data);
			$event["uid"] = $data["uid"];
			if (PNApplication::hasErrors()) return false;
		}
		if (isset($event["frequency"])) {
			// TODO
		}
		return true;
	}
	
	/**
	 * Get an event name
	 * @param number $calendar_id ID of the calendar to which the event belongs to
	 * @param number $event_id ID of the event seeked
	 * @return boolean|string false if not allowed, else the event name
	 */
	public function getEventName($calendar_id, $event_id){
		if(!$this->canReadCalendar($calendar_id)){
			PNApplication::error("Access denied to this calendar");
			return false;
		}
		$ev_name = SQLQuery::create()
			->bypassSecurity()
			->select("CalendarEvent")
			->field("CalendarEvent","title")
			->whereValue("CalendarEvent", "id", $event_id)
			->executeSingleValue();
		return $ev_name;
	}
	
	/** Join the CalendarEvent table.
	 * @param SQLQuery $q the query to join
	 * @param string $from_table origin table name
	 * @param string $from_key foreign key name in the origin table
	 */
	public function joinCalendarEvent(&$q, $from_table, $from_key) {
		$q->join($from_table, "CalendarEvent", array($from_key=>"id"));
	}
	/** Add a WHERE clause to select only past events
	 * @param SQLQuery $q the query to modify
	 * @param boolean $based_on_end if true, the event already finished (end in the past) will be returned, if false it will include events in the past and events already started but not yet finished (start in the past) 
	 */
	public function whereEventInThePast(&$q, $based_on_end = true) {
		$event_alias = $q->getTableAlias("CalendarEvent");
		$now = time();
		if ($based_on_end)
			$q->where("`$event_alias`.`end` < '$now'");
		else
			$q->where("`$event_alias`.`start` < '$now'");
	}
	/** Add a WHERE clause to select only past events
	 * @param SQLQuery $q the query to modify
	 * @param boolean $based_on_start if true, the event not yet started (start in the future) will be returned, if false it will include events in the future and events already started but not yet finished (end in the future) 
	 */
	public function whereEventInTheFuture(&$q, $based_on_start = true) {
		$event_alias = $q->getTableAlias("CalendarEvent");
		$now = time();
		if ($based_on_start)
			$q->where("`$event_alias`.`start` > '$now'");
		else
			$q->where("`$event_alias`.`end` > '$now'");
	}
}
?>