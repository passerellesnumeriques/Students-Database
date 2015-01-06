<?php
require_once("component/selection/page/SelectionPage.inc"); 
class page_applicant_exclude extends SelectionPage {
	
	public function getRequiredRights() { return array("edit_applicants"); }
	
	public function executeSelectionPage() {
		$input = json_decode($_POST["input"], true);
		$applicants_ids = $input["applicants"];
		
		$q = SQLQuery::create()->select("Applicant")->whereIn("Applicant","people",$applicants_ids);
		PNApplication::$instance->people->joinPeople($q, "Applicant", "people");
		$q->field("Applicant","applicant_id");
		$q->field("Applicant","excluded");
		$q->field("Applicant","automatic_exclusion_step");
		$q->field("Applicant","automatic_exclusion_reason");
		$q->field("Applicant","custom_exclusion");
		$q->field("Applicant","exam_session");
		$q->field("Applicant","exam_center");
		$q->field("Applicant","interview_session");
		$q->field("Applicant","interview_center");
		$applicants = $q->execute();
		
		// check if some of them are already excluded
		$already = array();
		$exam_sessions = array();
		$interview_sessions = array();
		$exam_center_without_session = array();
		$interview_center_without_session = array();
		foreach ($applicants as $a) {
			if ($a["excluded"] == 1) array_push($already, $a);
			if ($a["exam_session"] <> null && !in_array($a["exam_session"], $exam_sessions)) array_push($exam_sessions, $a["exam_session"]);
			if ($a["interview_session"] <> null && !in_array($a["interview_session"], $interview_sessions)) array_push($interview_sessions, $a["interview_session"]);
			if ($a["exam_session"] == null && $a["exam_center"] <> null) array_push($exam_center_without_session, $a);
			if ($a["interview_session"] == null && $a["interview_center"] <> null) array_push($interview_center_without_session, $a);
		}
		if (count($already) > 0) {
			echo "<div style='padding:5px'><div class='error_box'><table><tr><td valign=top><img src='".theme::$icons_16["error"]."'/></td><td>";
			if (count($applicants) == 1)
				echo "This applicant is already excluded !";
			else {
				echo "The following applicant".(count($already) > 1 ? "s are" : " is")." already excluded:<ul>";
				foreach ($already as $a) {
					echo "<li>".toHTML($a["first_name"]." ".$a["last_name"]).",  ID ".$a["applicant_id"].", Reason: ";
					if ($a["automatic_exclusion_step"] <> null)
						echo toHTML("Automatic from ".$a["automatic_exclusion_step"].": ".$a["automatic_exclusion_reason"]);
					else 
						echo toHTML("Manual: ".$a["custom_exclusion"]);
					echo "</li>";
				}
				echo "</ul>";
			}
			echo "</td></tr></table></div></div>";
			return;
		}
		if (count($exam_sessions) > 0) {
			// check if in the future
			require_once("component/calendar/CalendarJSON.inc");
			$events = CalendarJSON::getEventsFromDB($exam_sessions, PNApplication::$instance->selection->getCalendarId());
			$exam_sessions = array();
			foreach ($events as $ev) if ($ev["start"] > time()) array_push($exam_sessions, $ev["id"]);
			if (count($exam_sessions) > 0) {
				echo "<div style='padding:5px'><div class='warning_box'><table><tr><td valign=top><img src='".theme::$icons_16["warning"]."'/></td><td>";
				echo "The following applicants are assign to a future exam session, and you will probably need to remove them from the exam sessions if you exclude them:<ul>";
				foreach ($applicants as $a) {
					if (in_array($a["exam_session"], $exam_sessions))
						echo "<li>".toHTML($a["first_name"]." ".$a["last_name"]).",  ID ".$a["applicant_id"]."</li>";
				}
				echo "</ul>";
				echo "</td></tr></table></div></div>";
			}
		}
		if (count($interview_sessions) > 0) {
			// check if in the future
			require_once("component/calendar/CalendarJSON.inc");
			$events = CalendarJSON::getEventsFromDB($interview_sessions, PNApplication::$instance->selection->getCalendarId());
			$interview_sessions = array();
			foreach ($events as $ev) if ($ev["start"] > time()) array_push($interview_sessions, $ev["id"]);
			if (count($interview_sessions) > 0) {
				echo "<div style='padding:5px'><div class='warning_box'><table><tr><td valign=top><img src='".theme::$icons_16["warning"]."'/></td><td>";
				echo "The following applicants are assign to a future interview session, and you will probably need to remove them from the interview sessions if you exclude them:<ul>";
				foreach ($applicants as $a) {
					if (in_array($a["interview_session"], $interview_sessions))
						echo "<li>".toHTML($a["first_name"]." ".$a["last_name"]).",  ID ".$a["applicant_id"]."</li>";
				}
				echo "</ul>";
				echo "</td></tr></table></div></div>";
			}
		}
		if (count($exam_center_without_session) > 0) {
			echo "<div style='padding:5px'><div class='info_box'><table><tr><td valign=top><img src='".theme::$icons_16["info"]."'/></td><td>";
			echo "The following applicants are assign to an exam center, but not yet scheduled to an exam session. Please note they will be automatically unassigned from their exam center:<ul>";
			foreach ($exam_center_without_session as $a)
				echo "<li>".toHTML($a["first_name"]." ".$a["last_name"]).",  ID ".$a["applicant_id"]."</li>";
			echo "</ul>";
			echo "</td></tr></table></div></div>";
		}
		if (count($interview_center_without_session) > 0) {
			echo "<div style='padding:5px'><div class='info_box'><table><tr><td valign=top><img src='".theme::$icons_16["info"]."'/></td><td>";
			echo "The following applicants are assign to an interview center, but not yet scheduled to an interview session. Please note they will be automatically unassigned from their interview center:<ul>";
			foreach ($interview_center_without_session as $a)
				echo "<li>".toHTML($a["first_name"]." ".$a["last_name"]).",  ID ".$a["applicant_id"]."</li>";
			echo "</ul>";
			echo "</td></tr></table></div></div>";
		}
		
?>
<div style='background-color:white;padding:10px'>
	Please enter a reason: <input id='reason' type='text' maxlength=255/>
	<button class='action red' onclick='exclude();'>Exclude applicant<?php if (count($applicants) > 1) echo "s"?></button>
</div>
<script type='text/javascript'>
function exclude() {
	var reason = document.getElementById('reason').value;
	reason = reason.trim();
	if (reason.length == 0) { alert("Please enter a reason"); return; }
	var popup = window.parent.getPopupFromFrame(window);
	popup.freeze("Excluding applicant<?php if (count($applicants) > 1) echo "s"?>...");
	var ids = <?php echo json_encode($applicants_ids);?>;
	service.json("data_model","save_cells",{
			cells:[
				{
					table:'Applicant',
					sub_model:<?php echo $this->component->getCampaignId();?>,
					keys:ids,
					values:[{column:'excluded',value:1},{column:'custom_exclusion',value:reason}]
				}
				<?php 
				if (count($exam_center_without_session) > 0) {
					echo ",{";
					echo "table:'Applicant',";
					echo "sub_model:".$this->component->getCampaignId().",";
					echo "keys:[";
					$first = true;
					foreach ($exam_center_without_session as $a) {
						if ($first) $first = false; else echo ",";
						echo $a["people_id"];
					}
					echo "],";
					echo "values:[{column:'exam_center',value:null}]";
					echo "}";
				}
				if (count($interview_center_without_session) > 0) {
					echo ",{";
					echo "table:'Applicant',";
					echo "sub_model:".$this->component->getCampaignId().",";
					echo "keys:[";
					$first = true;
					foreach ($interview_center_without_session as $a) {
						if ($first) $first = false; else echo ",";
						echo $a["people_id"];
					}
					echo "],";
					echo "values:[{column:'interview_center',value:null}]";
					echo "}";
				}
				?>
			]
	},function(res) {
		<?php if (isset($_GET["ondone"])) echo "window.frameElement.".$_GET["ondone"]."();"?>
		popup.close();
	});
}
</script>
<?php 
	}
	
}
?>