<?php 
class page_admin extends Page {
	
	public function getRequiredRights() { return array("admin_google"); }
	
	public function execute() {
require_once("component/google/lib_api/PNGoogleCalendar.inc");
$gcal = new PNGoogleCalendar();

if (isset($_POST["action"])) {
	switch ($_POST["action"]) {
		case "remove_calendar":
			$gcal->removeCalendar($_POST["calendar_id"]);
			echo "<script type='text/javascript'>location.reload();</script>";
			break;
	}
}
		
$this->requireJavascript("section.js");
theme::css($this, "section.css");
?>
<div style='padding:10px'>
	<div id='section_calendars' title='Calendars'>
		<div style='padding:5px;'>
			<table class='all_borders'>
				<tr><th>ID</th><th>Summary</th><th>Description</th><th>Location</th><th>Access</th><th></th></tr>
				<?php 
				$list = $gcal->getGoogleCalendars();
				foreach ($list as $cal) {
					echo "<tr>";
					echo "<td>".toHTML($cal->getId())."</td>";
					echo "<td>".toHTML($cal->getSummary())."</td>";
					echo "<td>".toHTML($cal->getDescription())."</td>";
					echo "<td>".toHTML($cal->getLocation())."</td>";
					echo "<td><ul>";
					$acls = $gcal->getAcls($cal->getId());
					foreach ($acls as $acl) {
						echo "<li><b>".$acl->getRole()."</b>: ".$acl->getScope()->getType().": <i>".$acl->getScope()->getValue()."</i></li>";
					}
					echo "</ul></td>";
					echo "<td>";
					$id = $this->generateID();
					echo "<form method='POST' id='$id'><input type='hidden' name='action' value='remove_calendar'/><input type='hidden' name='calendar_id' value='".$cal->getId()."'/><button class='action red' onclick=\"document.forms['$id'].submit();\"><img src='".theme::$icons_16["remove_white"]."'/> Remove</button></form>";
					echo "</td>";
					echo "</tr>";
				}
				?>
			</table>
		</div>
	</div>
</div>
<script type='text/javascript'>
sectionFromHTML('section_calendars');
</script>
<?php 				
	}
	
}
?>