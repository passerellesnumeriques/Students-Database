<?php 
require_once("PNGoogle.inc");
class PNGoogleCalendar extends PNGoogle {
	
	/** @var Google_Service_Calendar */
	protected $service;
	
	public function __construct() {
		parent::__construct();
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
		while(true) {
			foreach ($list->getItems() as $acl) {
				if ($remove_internals && (
					$acl->getScope()->getValue() == PNGoogle::$service_account_name ||
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
	
	public function synchCalendarsList() {
		$list = $this->getGoogleCalendars();
		$google = array();
		foreach ($list as $gcal) array_push($google, $gcal);
		// 1-create missing google calendars
		// 1.1 normal calendars
		$pn = PNApplication::$instance->calendar->getAllCalendarsWithoutSecurity();
		$pn_google = SQLQuery::create()->bypassSecurity()->select("PNGoogleCalendar")->execute();
		// check our google ids, and remove those which are not anymore in Google, so we will create them again
		$not_anymore_in_google = array();
		foreach ($pn_google as $cal) {
			$found = false;
			foreach ($google as $gcal)
				if ($gcal->getId() == $cal["google_id"]) { $found = true; break; }
			if (!$found) array_push($not_anymore_in_google, $cal["pn_id"]);
		}
		if (count($not_anymore_in_google) > 0)
			SQLQuery::create()->bypassSecurity()->removeKeys("PNGoogleCalendar", $not_anymore_in_google);
		// create missings
		foreach ($pn as $cal) {
			$google_id = null;
			if (!in_array($cal["id"], $not_anymore_in_google))
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
		// 1.2 custom calendars
		$pn_custom = PNApplication::$instance->calendar->getAllCustomCalendars();
		$pn_custom_google = SQLQuery::create()->bypassSecurity()->select("PNCustomGoogleCalendar")->execute();
		foreach ($pn_custom as $cal) {
			$google_id = null;
			foreach ($pn_custom_google as $g) if ($g["pn_id"] == $cal->getId()) { $google_id = $g["google_id"]; break; }
			if ($google_id == null) {
				// does not exist yet in Google
				// TODO
			} else {
				$found = false;
				foreach ($google as $gcal)
					if ($gcal->getId() == $google_id) { $found = true; break; }
				if (!$found) {
					// it is not anymore in Google, we need to create it again, and update the google_id in the database
					// TODO
				}
			}
		}
		// TODO
		// 2-remove calendars
		// 2.1 normal calendars
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
		// 2.2 custom calendars
		// TODO
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
		// 3.2 custom calendars
		// TODO
		// 4-rename calendars if needed
		// 4.1 normal calendars
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
		// 4.2 custom calendars
		// TODO
	}
	
}
?>