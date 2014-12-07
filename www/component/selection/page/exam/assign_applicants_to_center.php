<?php
require_once("component/selection/page/SelectionPage.inc"); 
class page_exam_assign_applicants_to_center extends SelectionPage {
	
	public function getRequiredRights() { return array("edit_applicants"); }
	
	public function executeSelectionPage() {
		$input = json_decode($_POST["input"], true);
		$applicants_ids = $input["applicants"];
		
		$q = SQLQuery::create()->select("Applicant")->whereIn("Applicant","people",$applicants_ids);
		PNApplication::$instance->people->joinPeople($q, "Applicant", "people");
		$q->field("Applicant","exam_session");
		$q->field("Applicant","applicant_id");
		$q->field("Applicant","excluded");
		$applicants = $q->execute();
		
		// check if some of them are already assigned to an exam session, or excluded
		$already = array();
		$excluded = array();
		foreach ($applicants as $a) {
			if ($a["exam_session"] <> null) array_push($already, $a);
			if ($a["excluded"] == 1) array_push($excluded, $a);
		}
		if (count($already) > 0) {
			if (count($already) == 1)
				echo "<div style='padding:5px'><div class='error_box'><table><tr><td valign=top><img src='".theme::$icons_16["error"]."'/></td><td>You cannot assign to an exam center, because ";
			if (count($applicants) == 1)
				echo "this applicant is already assigned to an exam session.<br/>";
			else {
				echo "the following applicant".(count($already) > 1 ? "s are" : " is")." already assigned to an exam session:<ul>";
				foreach ($already as $a)
					echo "<li>".toHTML($a["first_name"]." ".$a["last_name"])." (ID ".$a["applicant_id"].")</li>";
				echo "</ul>";
			}
			if (count($already) == 1)
				echo "If you want to change this applicant to a different exam center, you need first to unassign from the exam session, by going to the page of the exam center the applicant is currently assigned to.";
			else
				echo "If you want to change them to a different exam center, you need first to unassign them from the exam sessions, by going to the page of the exam center they are currently assigned to.";
			echo "</td></tr></table></div></div>";
			return;
		}
		if (count($excluded) > 0) {
			if (count($excluded) == 1)
				echo "<div style='padding:5px'><div class='error_box'><table><tr><td valign=top><img src='".theme::$icons_16["error"]."'/></td><td>You cannot assign to an exam center, because ";
			if (count($applicants) == 1)
				echo "this applicant is already excluded from the process.<br/>";
			else {
				echo "the following applicant".(count($excluded) > 1 ? "s are" : " is")." already excluded from the process:<ul>";
				foreach ($excluded as $a)
					echo "<li>".toHTML($a["first_name"]." ".$a["last_name"])." (ID ".$a["applicant_id"].")</li>";
				echo "</ul>";
			}
			if (count($excluded) == 1)
				echo "If you want to assign this applicant, you need first to unexclude him/her.";
			else
				echo "If you want to assign them, you need first to unexclude them.";
			echo "</td></tr></table></div></div>";
			return;
		}
		
		// get list of exam centers
		$centers = SQLQuery::create()->select("ExamCenter")->execute();
?>
<div style='background-color:white;padding:10px'>
	Select an exam center: <select id='choice'><option value='0'></option>
		<?php
		foreach ($centers as $c) echo "<option value='".$c["id"]."'>".toHTML($c["name"])."</option>"; 
		?>
	</select><br/>
	<button class='action' onclick='assignApplicants();'>Assign applicant<?php if (count($applicants) > 1) echo "s"?></button>
</div>
<script type='text/javascript'>
function assignApplicants() {
	var center_id = document.getElementById('choice').value;
	if (center_id == 0) { alert("Please select an exam center"); return; }
	var popup = window.parent.get_popup_window_from_frame(window);
	popup.freeze("Assigning applicant<?php if (count($applicants) > 1) echo "s"?>...");
	var ids = <?php echo json_encode($applicants_ids);?>;
	service.json("data_model","save_cells",{cells:[{table:'Applicant',sub_model:<?php echo $this->component->getCampaignId();?>,keys:ids,values:[{column:'exam_center',value:center_id}]}]},function(res) {
		<?php if (isset($_GET["ondone"])) echo "window.frameElement.".$_GET["ondone"]."();"?>
		popup.close();
	});
}
</script>
<?php 
	}
	
}
?>