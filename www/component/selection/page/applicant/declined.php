<?php
require_once("component/selection/page/SelectionPage.inc"); 
class page_applicant_declined extends SelectionPage {
	
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
		$applicants = $q->execute();

		echo "<div style='background-color:white;padding:10px'>";
		echo "The following ".count($applicants)." applicant".(count($applicants) > 1 ? "s" : "")." declined:<ul>";
		foreach ($applicants as $a) {
			echo "<li>";
			echo toHTML($a["first_name"]." ".$a["last_name"]." (ID ".$a["applicant_id"].")");
			if ($a["excluded"] == 0)
				echo ", Reason given: <input type='text' maxlength=150 size=30 id='reason_".$a["people_id"]."'/>";
			else {
				echo " <img src='".theme::$icons_16["warning"]."'/> This applicant is already excluded: ";
				if ($a["automatic_exclusion_step"] <> null)
					echo toHTML("Automatic from ".$a["automatic_exclusion_step"].": ".$a["automatic_exclusion_reason"]);
				else 
					echo toHTML("Manual: ".$a["custom_exclusion"]);
			}
			echo "</li>";
		}
		echo "</ul>";
		echo "<button onclick='save()' class='action'>Save</button>";
		echo "</div>";
?>
<script type='text/javascript'>
var ids = [<?php 
$first = true;
foreach ($applicants as $a) {
	if ($a["excluded"] == 1) continue;
	if ($first) $first = false; else echo ",";
	echo $a["people_id"];
}
?>];
function save() {
	var cells = [];
	for (var i = 0; i < ids.length; ++i) {
		var reason = document.getElementById('reason_'+ids[i]).value;
		reason = reason.trim();
		if (reason.length == 0) { alert("Please enter a reason for every applicant who declined"); return; }
		cells.push({
			table:'Applicant',
			sub_model:<?php echo $this->component->getCampaignId();?>,
			keys:[ids[i]],
			values:[{column:'applicant_decision',value:'Declined'},{column:'applicant_not_coming_reason',value:reason}]
		});
	}
	var popup = window.parent.get_popup_window_from_frame(window);
	popup.freeze("Saving...");
	service.json("data_model","save_cells",{cells:cells},function(res) {
		<?php if (isset($_GET["ondone"])) echo "window.frameElement.".$_GET["ondone"]."();"?>
		popup.close();
	});
}
</script>
<?php 
	}
	
}
?>