<?php 
class page_test extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		set_include_path(get_include_path() . PATH_SEPARATOR . realpath("component/google/lib_api"));
		// initialize Google client
		require_once("Google/Client.php");
		$client = new Google_Client();
		require_once("Google/IO/Curl.php");
		$io = new Google_IO_Curl($client);
		// TODO proxy
		$client->setIo($io);

		// authentication
		require_once("Google/Auth/AssertionCredentials.php");
		$client->setApplicationName("Students Management Software");
		$service_account_name = "668569616064-8cn91oiqe6qtbe4sde84u9ugoiaf0ptv@developer.gserviceaccount.com";
		$key = file_get_contents("component/google/lib_api/key.p12");
		$cred = new Google_Auth_AssertionCredentials(
			$service_account_name,
			array('https://www.googleapis.com/auth/calendar'),
			$key
		);
		$client->setAssertionCredentials($cred);
		if($client->getAuth()->isAccessTokenExpired()) {
			$client->getAuth()->refreshTokenWithAssertion($cred);
		}
		
		// connect to Google Calendar service
		require_once("Google/Service/Calendar.php");
		$service = new Google_Service_Calendar($client);
		
		switch (@$_POST["fct"]) {
			case "create_calendar": $this->createCalendar($service); break;
			case "open_calendar": $this->openCalendar($service); break;
			case "share_calendar": $this->shareCalendar($service); break;
			default: $this->listCalendars($service); break;
		}
	}
	
	/**
	 * @param Google_Service_Calendar $service
	 */
	private function listCalendars($service) {
		echo "<h1>Calendars</h1>";
		echo "<table>";
		echo "<tr><th>ID</th><th>Summary</th><th>Description</th><th>Location</th><th>Timezone</th><th></th></tr>";
		
		$calendarList = $service->calendarList->listCalendarList();
		while(true) {
			foreach ($calendarList->getItems() as $cal) {
				/* @var $cal Google_Service_Calendar_CalendarListEntry */
				echo "<tr>";
				echo "<td>".$cal->getId()."</td>";
				echo "<td>".$cal->getSummary()."</td>";
				echo "<td>".$cal->getDescription()."</td>";
				echo "<td>".$cal->getLocation()."</td>";
				echo "<td>".$cal->getTimeZone()."</td>";
				echo "<td>";
				echo "<form method='POST'><input type='hidden' name='fct' value='open_calendar'/><input type='hidden' name='calendar_id' value='".$cal->getId()."'/><input type='submit' value='Open'/></form>";
				echo "</td>";
				echo "</tr>";
			}
			$pageToken = $calendarList->getNextPageToken();
			if ($pageToken) {
				$optParams = array('pageToken' => $pageToken);
				$calendarList = $service->calendarList->listCalendarList($optParams);
			} else {
				break;
			}
		}
		echo "</table>";
		echo "<form method='POST'>";
		echo "<input type='hidden' name='fct' value='create_calendar'/>";
		echo "Create a new calendar:<br/>";
		echo "Summary <input type='text' name='summary'/><br/>";
		echo "Description <input type='text' name='description'/><br/>";
		echo "<input type='submit' value='Create'/>";
		echo "</form>";
	}
	
	/**
	 * @param Google_Service_Calendar $service
	 */
	private function createCalendar($service) {
		$cal = new Google_Service_Calendar_Calendar();
		$cal->setSummary($_POST["summary"]);
		$cal->setDescription($_POST["description"]);
		$service->calendars->insert($cal);
		echo "Calendar created.<br/>";
		echo "<form method='POST'><input type='submit' value='Back to calendars list'/></form>";
	}
	
	/**
	 * @param Google_Service_Calendar $service
	 */
	private function openCalendar($service) {
		echo "<h1>Calendar</h1>";
		$cal = $service->calendars->get($_POST["calendar_id"]);
		echo "ID: ".$cal->getId()."<br/>";
		echo "Summary: ".$cal->getSummary()."<br/>";
		echo "Description: ".$cal->getDescription()."<br/>";
		
		echo "<h2>Rules</h2>";
		echo "<table>";
		echo "<tr><th>ID</th><th>Role</th><th>Scope</th><th></th></tr>";
		$list = $service->acl->listAcl($_POST["calendar_id"]);
		while(true) {
			foreach ($list->getItems() as $rule) {
				/* @var $rule Google_Service_Calendar_AclRule */
				echo "<tr>";
				echo "<td>".$rule->getId()."</td>";
				echo "<td>".$rule->getRole()."</td>";
				echo "<td>";
				$scope = $rule->getScope();
				echo $scope->getType()." = ".$scope->getValue();
				echo "</td>";
				echo "<td>";
				echo "</td>";
				echo "</tr>";
			}
			$pageToken = $list->getNextPageToken();
			if ($pageToken) {
				$optParams = array('pageToken' => $pageToken);
				$list = $service->acl->listAcl($_POST["calendar_id"]);
			} else {
				break;
			}
		}
		echo "</table>";
		echo "<form method='POST'>";
		echo "<input type='hidden' name='fct' value='share_calendar'/>";
		echo "<input type='hidden' name='calendar_id' value='".$_POST["calendar_id"]."'/>";
		echo "Share calendar with ";
		echo "<input type='text' name='email'/>";
		echo "<input type='submit' value='Share'/>";
		echo "</form>";
	}
	
	/**
	 * @param Google_Service_Calendar $service
	 */
	private function shareCalendar($service) {
		$calendar_id = $_POST["calendar_id"];
		$email = $_POST["email"];
		$rule = new Google_Service_Calendar_AclRule();
		$rule->setRole("reader");
		$scope = new Google_Service_Calendar_AclRuleScope();
		$scope->setType("user");
		$scope->setValue($email);
		$rule->setScope($scope);
		$service->acl->insert($calendar_id, $rule);
		echo "Calendar shared.<br/>";
		echo "<form method='POST'><input type='hidden' name='fct' value='open_calendar'/><input type='hidden' name='calendar_id' value='".$calendar_id."'/><input type='submit' value='Back to calendar'/></form>";
	}
}
?>