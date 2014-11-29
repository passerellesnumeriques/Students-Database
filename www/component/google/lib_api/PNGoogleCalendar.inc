<?php 
require_once("PNGoogle.inc");
class PNGoogleCalendar extends PNGoogle_ProjectSpecific {
	
	/** @var Google_Service_Calendar */
	protected $service;
	
	public function __construct() {
		parent::__construct(array('https://www.googleapis.com/auth/calendar'));
		// connect to Google Calendar service
		require_once("Google/Service/Calendar.php");
		$this->service = new Google_Service_Calendar($this->client);
	}
	
	/**
	 * @return Google_Service_Calendar_CalendarListEntry[]
	 */
	public function getGoogleCalendars() {
		$calendars = array();
		$list = $this->service->calendarList->listCalendarList(array("maxResults"=>250));
		while(true) {
			foreach ($list->getItems() as $cal) array_push($calendars, $cal);
			$pageToken = $list->getNextPageToken();
			if ($pageToken)
				$list = $this->service->calendarList->listCalendarList(array("maxResults"=>250,"pageToken"=>$pageToken));
			else
				break;
		}
		return $calendars;
	}
	
	public function removeCalendar($id) {
		$this->service->calendars->delete($id);
	}
	
	/**
	 * @return Google_Service_Calendar_CalendarListEntry
	 */
	public function addCalendar($summary, $description, $location) {
		$gcal = new Google_Service_Calendar_Calendar();
		$gcal->setSummary($summary);
		$gcal->setDescription($description);
		$gcal->setLocation($location);
		$gcal = $this->service->calendars->insert($gcal);
		return $gcal;
	}
	
	/**
	 * @return Google_Service_Calendar_AclRule[]
	 */
	public function getAcls($calendar_id, $remove_internals = true) {
		$acls = array();
		$list = $this->service->acl->listAcl($calendar_id, array("maxResults"=>250));
		$secrets = include("conf/google.inc");
		while(true) {
			foreach ($list->getItems() as $acl) {
				if ($remove_internals && (
					$acl->getScope()->getValue() == $secrets["service_account"] ||
					$acl->getScope()->getValue() == $calendar_id
				)) continue;
				array_push($acls, $acl);
			}
			$pageToken = $list->getNextPageToken();
			if ($pageToken)
				$list = $this->service->acl->listAcl($calendar_id, array("maxResults"=>250,"pageToken"=>$pageToken));
			else
				break;
		}
		return $acls;
	}
	
	public function addAcl($calendar_id, $role, $scope_type, $scope_value) {
		$rule = new Google_Service_Calendar_AclRule();
		$rule->setRole($role);
		$scope = new Google_Service_Calendar_AclRuleScope();
		$scope->setType($scope_type);
		$scope->setValue($scope_value);
		$rule->setScope($scope);
		$this->service->acl->insert($calendar_id, $rule);
	}
	
	public function removeAcl($calendar_id, $rule_id) {
		$this->service->acl->delete($calendar_id, $rule_id);
	}
	
	public function getAllEvents($calendar_id) {
		$events = array();
		$list = $this->service->events->listEvents($calendar_id, array("maxResults"=>2500,"alwaysIncludeEmail"=>true));
		while(true) {
			foreach ($list->getItems() as $event)
				array_push($events, $event);
			$pageToken = $list->getNextPageToken();
			if ($pageToken)
				$list = $this->service->events->listEvents($calendar_id, array("maxResults"=>2500,"alwaysIncludeEmail"=>true,"pageToken"=>$pageToken));
			else
				break;
		}
		return $events;
	}
	
	public function removeEvent($calendar_id, $event_id, $send_notifications = false) {
		$this->service->events->delete($calendar_id, $event_id, array("sendNotifications"=>$send_notifications));
	}
	
	public function addEvent($calendar_id, $event, $send_notifications = false) {
		$this->service->events->insert($calendar_id, $event, array("sendNotifications"=>$send_notifications));
	}
	
	public function updateEvent($calendar_id, $event_id, $event, $send_notifications = false) {
		$this->service->events->update($calendar_id, $event_id, $event, array("sendNotifications"=>$send_notifications));
	}
	
	public function synchCalendarsList() {
		$list = $this->getGoogleCalendars();
		$google = array();
		foreach ($list as $gcal) array_push($google, $gcal);
		// 1-create missing google calendars
		$pn = PNApplication::$instance->calendar->getAllCalendarsWithoutSecurity();
		$pn_google = SQLQuery::create()->bypassSecurity()->select("PNGoogleCalendar")->execute();
		// check our google ids, and remove those which are not anymore in Google, so we will create them again
		$not_anymore_in_google = array();
		for ($i = 0; $i < count($pn_google); $i++) {
			$cal = $pn_google[$i];
			$found = false;
			foreach ($google as $gcal)
				if ($gcal->getId() == $cal["google_id"]) { $found = true; break; }
			if (!$found) {
				array_push($not_anymore_in_google, $cal["google_id"]);
				array_splice($pn_google, $i, 1);
				$i--;
			}
		}
		if (count($not_anymore_in_google) > 0)
			SQLQuery::create()->bypassSecurity()->removeKeys("PNGoogleCalendar", $not_anymore_in_google);
		// create missings
		foreach ($pn as $cal) {
			$google_id = null;
			foreach ($pn_google as $c) if ($c["pn_id"] == $cal["id"]) { $google_id = $c["google_id"]; break; }
			if ($google_id == null) {
				$gcal = $this->addCalendar("[SMS] ".$cal["name"], "Students Management Software Calendar", PNApplication::$instance->local_domain."_".$cal["id"]);
				if ($gcal <> null && $gcal->getId() <> null) {
					$a = array("pn_id"=>$cal["id"],"google_id"=>$gcal->getId());
					array_push($pn_google, $a);
					SQLQuery::create()->bypassSecurity()->insert("PNGoogleCalendar", $a);
					array_push($google, $gcal);
				}
			}
		}
		// 2-remove calendars
		$to_remove = array();
		for ($i = 0; $i < count($pn_google); $i++) {
			$cal = $pn_google[$i];
			if ($cal["pn_id"] == null) {
				$this->removeCalendar($cal["google_id"]);
				array_push($to_remove, $cal["google_id"]);
				for ($j = 0; $j < count($google); $j++)
					if ($google[$j]->getId() == $cal["google_id"]) {
						array_splice($google, $j, 1);
						break;
					}
				array_splice($pn_google, $i, 1);
				$i--;
			}
		}
		SQLQuery::create()->bypassSecurity()->removeKeys("PNGoogleCalendar", $to_remove);
		// 3-share
		// 3.1 normal calendars
		foreach ($pn_google as $cal) {
			$users_ids = PNApplication::$instance->calendar->getUsersWhoCanReadCalendar($cal["pn_id"]);
			if ($users_ids == null) continue; // an error occured
			$acls = $this->getAcls($cal["google_id"], true);
			if (count($users_ids) == 0)
				$emails = array();
			else
				$emails = SQLQuery::create()->bypassSecurity()->select("GoogleUser")->whereIn("GoogleUser","user",$users_ids)->field("google_login")->distinct()->executeSingleField();
			// add missing users
			foreach ($emails as $email) {
				$found = false;
				foreach ($acls as $acl) if ($acl->getRole() == "reader" && $acl->getScope()->getType() == "user" && $acl->getScope()->getValue() == $email) { $found = true; break; }
				if (!$found)
					$this->addAcl($cal["google_id"], "reader", "user", $email);
			}
			// remove
			foreach ($acls as $acl) {
				if ($acl->getRole() <> "reader" || $acl->getScope()->getType() <> "user" || !in_array($acl->getScope()->getValue(), $emails)) {
					$this->removeCalendar($cal["google_id"], $acl->getId());
				}
			}
		}
		// 4-rename calendars if needed
		foreach ($pn_google as $cal) {
			$pn_cal = null;
			foreach ($pn as $c) if ($c["id"] == $cal["pn_id"]) { $pn_cal = $c; break; }
			if ($pn_cal == null) continue; // should never happen
			$gcal = null;
			foreach ($google as $g) if ($g->getId() == $cal["google_id"]) { $gcal = $g; break; }
			if ($gcal == null) continue; // should never happen
			if ($gcal->getSummary() <> "[SMS] ".$pn_cal["name"] || $gcal->getLocation() <> PNApplication::$instance->local_domain."_".$pn_cal["id"]) {
				// needs to be renamed
				$g = $this->service->calendars->get($gcal->getId());
				$g->setSummary("[SMS] ".$pn_cal["name"]);
				$g->setLocation(PNApplication::$instance->local_domain."_".$pn_cal["id"]);
				$this->service->calendars->update($gcal->getId(), $g);
			}
		}
	}
	
	public static function timestampToDate($timestamp) {
		$date = new Google_Service_Calendar_EventDateTime();
		$date->setDate(gmdate("Y-m-d",$timestamp));
		return $date;
	}
	public static function timestampToDateTime($timestamp) {
		$date = new Google_Service_Calendar_EventDateTime();
		$date->setDateTime(gmdate("Y-m-d",$timestamp)."T".gmdate("H:i:s",$timestamp)."Z");
		return $date;
	}
	
	public static function PNEventToGoogleEvent($pn) {
		$ev = new Google_Service_Calendar_Event();
		$ev->setICalUID($pn["uid"]);
		$ev->setSummary($pn["title"]);
		$ev->setDescription($pn["description"]);
		if ($pn["all_day"] == 1) {
			$ev->setStart(self::timestampToDate(intval($pn["start"])));
			$days = intval($pn["end"])-intval($pn["start"]);
			$days = floor($days/(24*60*60));
			$days++;
			$ev->setEnd(self::timestampToDate(intval($pn["start"])+$days*24*60*60));
		} else {
			$ev->setStart(self::timestampToDateTime(intval($pn["start"])));
			$ev->setEnd(self::timestampToDateTime(intval($pn["end"])));
		}
		// TODO recurrence
		$ev->setLocation($pn["location_freetext"]);
		// TODO location with geography
		$creator = new Google_Service_Calendar_EventCreator();
		$creator->setDisplayName("Students Management Software");
		$ev->setCreator($creator);
		if (@$pn["attendees"] <> null && count($pn["attendees"]) > 0) {
			$list = array();
			foreach ($pn["attendees"] as $a) {
				if ($a["organizer"] == 1 && $a["role"] == "NONE") {
					$o = new Google_Service_Calendar_EventOrganizer();
					$o->setDisplayName(@$a["name"]);
					$o->setEmail(@$a["email"]);
					$ev->setOrganizer($o);
				} else if ($a["creator"] == 1 && $a["role"] == "NONE") {
					$o = new Google_Service_Calendar_EventCreator();
					$o->setDisplayName(@$a["name"]);
					$o->setEmail(@$a["email"]);
					$ev->setCreator($o);
				} else {
					$at = new Google_Service_Calendar_EventAttendee();
					$at->setDisplayName(@$a["name"]);
					$at->setEmail(@$a["email"]);
					switch (@$a["participation"]) {
						case "YES": $at->setResponseStatus("accepted"); break;
						case "NO": $at->setResponseStatus("declined"); break;
						case "UNKNOWN": $at->setResponseStatus("needsAction"); break;
						case "TENTATIVE": $at->setResponseStatus("tentative"); break;
					}
					switch (@$a["role"]) {
						case "REQUESTED": $at->setOptional(false); break;
						case "OPTIONAL": $at->setOptional(true); break;
					}
					array_push($list, $at);
					if (@$a["organizer"] == 1) {
						$o = new Google_Service_Calendar_EventOrganizer();
						$o->setDisplayName(@$a["name"]);
						$o->setEmail(@$a["email"]);
						$ev->setOrganizer($o);
					}
					if (@$a["creator"] == 1) {
						$o = new Google_Service_Calendar_EventCreator();
						$o->setDisplayName(@$a["name"]);
						$o->setEmail(@$a["email"]);
						$ev->setCreator($o);
					}
				}
			}
			if (count($list) > 0) $ev->setAttendees($list);
		}
		return $ev;
	}
	
	public function synchCalendar($pn_id, $google_id, $last_update) {
		try {
			$gevents = $this->getAllEvents($google_id);
		} catch (Google_Service_Exception $e) {
			if ($e->getCode() == 404) return true; // not found = calendar removed, let's wait the calendars synch task
			PNApplication::error("Unable to retrieve Google calendar events", $e);
			return false;
		} catch (Exception $e) {
			PNApplication::error("Unable to retrieve Google calendar events", $e);
			return false;
		}
		$pnevents = PNApplication::$instance->calendar->getAllCalendarEventsWithoutSecurity($pn_id);
		$err = false;
		foreach ($gevents as $gev) {
			/* @var $gev Google_Service_Calendar_Event */
			$pn = null;
			foreach ($pnevents as $e)
				if ($e["uid"] == $gev->iCalUID) {
					$pn = $e;
					unset($pnevents[$e["id"]]);
					break;
				}
			if ($pn == null) {
				// event has been removed: remove it from Google
				try {
					$this->removeEvent($google_id, $gev->getId(), true);
				} catch (Exception $e) {
					PNApplication::error("Error removing Google Event ".$gev->getId(), $e);
					$err = true;
				}
				continue;
			}
			if (intval($pn["last_modified"]) < $last_update) continue; // not modified
			// event modified: update it in Google
			try {
				$this->updateEvent($google_id, $gev->getId(), self::PNEventToGoogleEvent($pn), true);
			} catch (Exception $e) {
				PNApplication::error("Error updating event ".$pn["id"], $e);
				$err = true;
			}
		}
		// add new events in Google
		foreach ($pnevents as $pn) {
			try {
				$this->addEvent($google_id, self::PNEventToGoogleEvent($pn), true);
			} catch (Exception $e) {
				PNApplication::error("Error creating event ".$pn["id"]."[".$pn["uid"]."] (".$pn["title"].") from calendar ".$pn_id, $e);
				$err = true;
			}
		}
		return $err == false;
	}
	
}
?>