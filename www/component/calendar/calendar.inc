<?php
/**
 * Component managing calendars
 */ 
class calendar extends Component {
	
	public function initRequest() {
		Component::allowServiceWithoutLogin("calendar", "get_my_calendars");
	}
	
	/**
	 * Create a new shared (not presonal) calendar. 
	 * @param string $calendar_name name of the calendar
	 * @param string $calendar_color default color of the calendar (in 6 hex digits), or null
	 * @param string $calendar_icon url of the icon for this calendar, or null
	 * @param array $readable_rights associative array of right name =&gt; right value, needed to access the calendar in read access
	 * @param array $writable_rights associative array of right name =&gt; right value, needed to access the calendar in write access
	 * @param string|null $internal_data data to store with the calendar
	 * @return boolean|number the calendar id, or false if it failed.
	 */
	public function createSharedCalendar($calendar_name, $calendar_color, $calendar_icon, $readable_rights, $writable_rights, $internal_data = null) {
		foreach ($readable_rights as $name=>$value)
			if (!PNApplication::$instance->user_management->hasRight($name, $value)) {
				PNApplication::error("You cannot create a calendar with right ".$name." because you don't have this right");
				return false;
			}
		foreach ($writable_rights as $name=>$value)
			if (!PNApplication::$instance->user_management->hasRight($name, $value)) {
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
				"data"=>$internal_data,
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
	 * Remove a writable right on a shared calendar
	 * @param integer $calendar_id the calendar
	 * @param string $right_name which right
	 * @param string $right_value which value for the right
	 */
	public function removeSharedCalendarWritableRight($calendar_id, $right_name, $right_value) {
		SQLQuery::create()->bypassSecurity()->removeKey("CalendarRights", array("calendar"=>$calendar_id,"right_name"=>$right_name));
	}
	/**
	 * Add a writable right on a shared calendar
	 * @param integer $calendar_id the calendar
	 * @param string $right_name which right
	 * @param string $right_value which value for the right
	 */
	public function addSharedCalendarWritableRight($calendar_id, $right_name, $right_value) {
		SQLQuery::create()->bypassSecurity()->insert("CalendarRights", array("calendar"=>$calendar_id,"right_name"=>$right_name,"right_value"=>$right_value,"writable"=>true));
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
	
	public function renameCalendar($id, $name) {
		SQLQuery::create()->bypassSecurity()->updateByKey("Calendar", $id, array("name"=>$name));
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
			if (PNApplication::$instance->user_management->hasRight($r["right_name"], $r["right_value"]))
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
			if (PNApplication::$instance->user_management->hasRight($r["right_name"], $r["right_value"]))
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
			if (PNApplication::$instance->user_management->hasRight($r["right_name"], $r["right_value"]))
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
			if (PNApplication::$instance->user_management->hasRight($r["right_name"], $r["right_value"]))
				array_push($ids, $r["calendar"]);
		}
		return $ids;
	}
	
	/**
	 * Return a list of calendars the current user own, and can modify and remove
	 * @return multitype:number ids of the calendars the current user own
	 */
	public function getOwnedCalendars() {
		$ids = array();
		$res = SQLQuery::create()->bypassSecurity()->select("UserCalendar")->where("user",PNApplication::$instance->user_management->user_id)->execute();
		foreach ($res as $r)
			array_push($ids, $r["calendar"]);
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
			$data["app_link"] = @$event["app_link"];
			$data["app_link_name"] = @$event["app_link_name"];
			SQLQuery::create()->bypassSecurity()->updateByKey("CalendarEvent", $event["id"], $data);
			$this->saveEventFrequency($event["id"], @$event["frequency"], false);
			$this->saveEventAttendees($event["id"], @$event["attendees"], false);
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
			$data["app_link"] = @$event["app_link"];
			$data["app_link_name"] = @$event["app_link_name"];
			$event["id"] = SQLQuery::create()->bypassSecurity()->insert("CalendarEvent", $data);
			$event["uid"] = $data["uid"];
			$this->saveEventFrequency($event["id"], @$event["frequency"], true);
			$this->saveEventAttendees($event["id"], @$event["attendees"], true);
			if (PNApplication::hasErrors()) return false;
		}
		return true;
	}
	
	/**
	 * Save the frequency part of an event
	 * @param number $event_id the event ID
	 * @param array $frequency frequency data
	 * @param boolean $is_new indicates if this is a new event
	 */
	private function saveEventFrequency($event_id, $frequency, $is_new) {
		if (!$is_new)
			$exists = SQLQuery::create()->bypassSecurity()->select("CalendarEventFrequency")->whereValue("CalendarEventFrequency","event",$event_id)->field("event")->executeSingleValue();
		else
			$exists = null;
		if ($frequency == null) {
			if ($exists <> null)
				SQLQuery::create()->bypassSecurity()->removeKey("CalendarEventFrequency", $event_id);
			return;
		}
		$data = array(
			"frequency"=>$frequency["frequency"],
			"until"=>$frequency["until"] <> null ? intval($frequency["until"]) : null,
			"count"=>$frequency["count"] <> null ? intval($frequency["count"]) : null,
			"interval"=>$frequency["interval"] <> null ? intval($frequency["interval"]) : null,
			"by_month"=>$frequency["by_month"],
			"by_week_no"=>$frequency["by_week_no"],
			"by_year_day"=>$frequency["by_year_day"],
			"by_month_day"=>$frequency["by_month_day"],
			"by_week_day"=>$frequency["by_week_day"],
			"by_hour"=>$frequency["by_hour"],
			"by_setpos"=>$frequency["by_setpos"],
			"week_start"=>$frequency["week_start"],
		);
		if ($exists)
			SQLQuery::create()->bypassSecurity()->updateByKey("CalendarEventFrequency", $event_id, $data);
		else {
			$data["event"] = $event_id;
			SQLQuery::create()->bypassSecurity()->insert("CalendarEventFrequency", $data);
		}
	}
	
	/**
	 * Save attendees of an event
	 * @param number $event_id the event ID
	 * @param array $attendees the attendees
	 * @param boolean $is_new indicates if this is a new event
	 */
	private function saveEventAttendees($event_id, $attendees, $is_new) {
		if ($attendees == null) $attendees = array();
		if (!$is_new)
			$existings = SQLQuery::create()->bypassSecurity()->select("CalendarEventAttendee")->whereValue("CalendarEventAttendee","event",$event_id)->execute();
		else 
			$existings = array();
		if (count($existings) > 0)
			SQLQuery::create()->bypassSecurity()->removeRows("CalendarEventAttendee", $existings);
		if (count($attendees) > 0) {
			$peoples_ids = array();
			$contacts_ids = array();
			for ($i = count($attendees)-1; $i >= 0; $i--) {
				if (@$attendees[$i]["participation"] == null) $attendees[$i]["participation"] = "UNKNOWN";
				if (!isset($attendees[$i]["organizer"]))
					$attendees[$i]["organizer"] = false;
				if (!isset($attendees[$i]["creator"]))
					$attendees[$i]["creator"] = false;
				if (!isset($attendees[$i]["role"]))
					$attendees[$i]["role"] = "NONE";
				$attendees[$i]["event"] = $event_id;
				if (@$attendees[$i]["people"] > 0 && !in_array($attendees[$i]["people"], $peoples_ids)) array_push($peoples_ids, $attendees[$i]["people"]);
				if (@$attendees[$i]["people_email"] > 0 && !in_array($attendees[$i]["people_email"], $contacts_ids)) array_push($contacts_ids, $attendees[$i]["people_email"]);
			}
			if (count($peoples_ids) > 0)
				$peoples = PNApplication::$instance->people->getPeoples($peoples_ids,false,false,true);
			else
				$peoples = array();
			if (count($contacts_ids) > 0)
				$emails = PNApplication::$instance->contact->getEMails($contacts_ids,true);
			else
				$emails = array();
			if (count($peoples) > 0 || count($emails) > 0)
				for ($i = count($attendees)-1; $i >= 0; $i--) {
					if (@$attendees[$i]["people"] > 0) foreach ($peoples as $p) if ($p["id"] == $attendees[$i]["people"]) { $attendees[$i]["name"] = $p["first_name"]." ".$p["last_name"]; break; }
					if (@$attendees[$i]["people_email"] > 0) foreach ($emails as $e) if ($e["id"] == $attendees[$i]["people_email"]) { $attendees[$i]["email"] = $e["contact"]; break; }
				}
			SQLQuery::create()->bypassSecurity()->insertMultiple("CalendarEventAttendee", $attendees);
		}
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
	
	/**
	 * Retrieve an event from database
	 * @param number $calendar_id ID of the calendar the event belongs to
	 * @param number $event_id the event ID
	 * @param boolean $include_frequency if true, frequency information will be included
	 * @return array|boolean the event, or false if an error occured
	 */
	public function getEvent($calendar_id, $event_id, $include_frequency = false) {
		if(!$this->canReadCalendar($calendar_id)){
			PNApplication::error("Access denied to this calendar");
			return false;
		}
		$q = SQLQuery::create()->bypassSecurity()
			->select("CalendarEvent")
			->whereValue("CalendarEvent","id",$event_id)
			;
		if ($include_frequency)
			$q->join("CalendarEvent", "CalendarEventFrequency", array("id"=>"event"));
		$ev = $q->executeSingleRow();
		if ($ev["calendar"] <> $calendar_id) {
			PNApplication::error("Invalid event ID");
			return false;
		}
		return $ev;
	}
	
	/** Join the CalendarEvent table.
	 * @param SQLQuery $q the query to join
	 * @param string $from_table origin table name
	 * @param string $from_key foreign key name in the origin table
	 * @param boolean $with_frequency if true, will join also with table CalendarEventFrequency
	 */
	public function joinCalendarEvent(&$q, $from_table, $from_key, $with_frequency = false) {
		$q->join($from_table, "CalendarEvent", array($from_key=>"id"));
		if ($with_frequency) $q->join("CalendarEvent", "CalendarEventFrequency", array("id"=>"event"));
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
	
	/**
	 * Retrieve all calendars from database, without any security check (used for synchronization with Google in cron task)
	 * @return array the rows from the Calendar table
	 */
	public function getAllCalendarsWithoutSecurity() {
		return SQLQuery::create()->bypassSecurity()->select("Calendar")->execute();
	}
	
	/**
	 * Get all events from a calendar, without any security check (used for synchronization with Google in cron task)
	 * @param number $calendar_id calendar ID
	 * @return array the events, including frequency and attendees information
	 */
	public function getAllCalendarEventsWithoutSecurity($calendar_id) {
		$events = SQLQuery::create()->bypassSecurity()->select("CalendarEvent")->whereValue("CalendarEvent","calendar",$calendar_id)->execute();
		if (count($events) == 0) return array();
		$ids = array();
		$list = array();
		foreach ($events as $e) {
			$e["attendees"] = array();
			$e["frequency"] = null;
			$list[$e["id"]] = $e;
			array_push($ids, $e["id"]);
		}
		$attendees = SQLQuery::create()->bypassSecurity()->select("CalendarEventAttendee")->whereIn("CalendarEventAttendee","event",$ids)->execute();
		$frequencies = SQLQuery::create()->bypassSecurity()->select("CalendarEventFrequency")->whereIn("CalendarEventFrequency","event",$ids)->execute();
		foreach ($attendees as $a) array_push($list[$a["event"]]["attendees"], $a);
		foreach ($frequencies as $f) $list[$f["event"]]["frequency"] = $f;
		return $list;
	}
	/** Get all custom calendars plugins declared by components
	 * @return CustomCalendarPlugin[] the plugins
	 */
	public function getAllCustomCalendars() {
		require_once("component/calendar/CustomCalendarPlugin.inc");
		$list = array();
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi)
				if ($pi instanceof CustomCalendarPlugin)
					array_push($list, $pi);
		return $list;
	}
	
	/**
	 * Retrieve all the users who can access the given calendar (used to by shared on Google)
	 * @param number $calendar_id calendar ID
	 * @return number[] list of users id
	 */
	public function getUsersWhoCanReadCalendar($calendar_id) {
		// get user calendars
		$list = SQLQuery::create()->bypassSecurity()->select("UserCalendar")->whereValue("UserCalendar","calendar",$calendar_id)->field("user")->distinct()->executeSingleField();
		if (count($list) == 0) {
			// it should be a shared calendar
			$rights = SQLQuery::create()->bypassSecurity()->select("CalendarRights")->whereValue("CalendarRights","calendar",$calendar_id)->field("CalendarRights","right_name","name")->field("CalendarRights","right_value","value")->execute();
			if (count($rights) > 0)
				$list = PNApplication::$instance->user_management->getUsersHavingRights($rights, false);
		}
		return $list;
	}
	
	/**
	 * Retrieve the attendees from events
	 * @param number[] $ids events ids
	 * @param boolean $include_people if true, people information will be included for attendees attached to a people
	 * @param boolean $bypass_security if true, no security check will be performed while accessing the database
	 * @return array associative array with the event ID as key, an the list of attendees as value
	 */
	public function getEventsAttendees($ids, $include_people = false, $bypass_security = false) {
		$q = SQLQuery::create()->bypassSecurity($bypass_security)
			->select("CalendarEventAttendee")
			->whereIn("CalendarEventAttendee","event", $ids);
		$q->field("CalendarEventAttendee", "event", "event_id");
		$q->field("CalendarEventAttendee", "name", "attendee_name");
		$q->field("CalendarEventAttendee", "email", "attendee_email");
		if ($include_people)
			PNApplication::$instance->people->joinPeople($q, "CalendarEventAttendee", "people", true);
		$list = $q->execute();
		$result = array();
		foreach ($ids as $id) $result[$id] = array();
		foreach ($list as $a) array_push($result[$a["event_id"]], $a);
		return $result;
	}
}
?>