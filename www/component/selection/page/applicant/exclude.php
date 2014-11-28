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
		$applicants = $q->execute();
		
		// check if some of them are already excluded
		$already = array();
		foreach ($applicants as $a) if ($a["excluded"] == 1) array_push($already, $a);
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
	var popup = window.parent.get_popup_window_from_frame(window);
	popup.freeze("Excluding applicant<?php if (count($applicants) > 1) echo "s"?>...");
	var ids = <?php echo json_encode($applicants_ids);?>;
	service.json("data_model","save_cells",{cells:[{table:'Applicant',sub_model:<?php echo $this->component->getCampaignId();?>,keys:ids,values:[{column:'excluded',value:1},{column:'custom_exclusion',value:reason}]}]},function(res) {
		<?php if (isset($_GET["ondone"])) echo "window.frameElement.".$_GET["ondone"]."();"?>
		popup.close();
	});
}
</script>
<?php 
	}
	
}
?>