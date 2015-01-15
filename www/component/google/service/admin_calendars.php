<?php 
class service_admin_calendars extends Service {
	
	public function getRequiredRights() { return array("admin_google"); }
	
	public function documentation() { echo "Calendars part of the admin page"; }
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function getOutputFormat($input) {
		if (isset($input["remove_calendar"]))
			return "application/json";
		return "text/html";
	}
	
	public function execute(&$component, $input) {
		require_once("component/google/lib_api/PNGoogleCalendar.inc");
		$gcal = new PNGoogleCalendar();
		if (isset($input["remove_calendar"])) {
			$gcal->removeCalendar($input["remove_calendar"]);
			return;
		}
		
$list = $gcal->getGoogleCalendars();
$synch = SQLQuery::create()->bypassSecurity()->select("GoogleCalendarSynchro")->execute();
?>
<table class='all_borders'>
	<tr><th>ID</th><th>Summary</th><th>Description</th><th>Location</th><th>Access</th><th>Last Synch</th><th></th></tr>
	<?php 
	foreach ($list as $cal) {
		echo "<tr>";
		echo "<td style='font-size:8pt;font-family:Courier New;'>".toHTML($cal->getId())."</td>";
		echo "<td>".toHTML($cal->getSummary())."</td>";
		echo "<td>".toHTML($cal->getDescription())."</td>";
		echo "<td>".toHTML($cal->getLocation())."</td>";
		echo "<td><ul>";
		try {
			set_time_limit(30);
			$acls = $gcal->getAcls($cal->getId());
			foreach ($acls as $acl) {
				echo "<li><b>".$acl->getRole()."</b>: ".$acl->getScope()->getType().": <i>".$acl->getScope()->getValue()."</i></li>";
			}
		} catch (Exception $e) {
			echo "<li><img src='".theme::$icons_16['error']."'/> Error: ".toHTML($e->getMessage())."</li>";
		}
		echo "</ul></td>";
		echo "<td>";
		$last = null;
		foreach ($synch as $s) if ($s["google_id"] == $cal->getId()) { $last = $s["timestamp"]; break; }
		if ($last == null)
			echo "<i>Never</i>";
		else 
			echo date("d M Y H:i", $last);
		echo "</td>";
		echo "<td>";
		echo "<button class='action red' onclick=\"var locker=lockScreen(null,'Removing calendar...');service.json('google','admin_calendars',{remove_calendar:'".$cal->getId()."'},function(res){setLockScreenContent(locker,'Loading calendars list...');loadCalendars(function(){unlockScreen(locker);});});\"><img src='".theme::$icons_16["remove_white"]."'/> Remove</button></form>";
		echo "</td>";
		echo "</tr>";
	}
	?>
</table>
<?php 
	}
	
}
?>