<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_exam_copy_subject extends SelectionPage {
	
	public function getRequiredRights() { return array("manage_exam_subject"); }
	
	public function executeSelectionPage() {
		$all_campaigns = PNApplication::$instance->selection->getCampaigns();
		$current_campaign = PNApplication::$instance->selection->getCampaignId();
		for ($i = 0; $i < count($all_campaigns); $i++)
			if ($all_campaigns[$i]["id"] == $current_campaign) {
				array_splice($all_campaigns, $i, 1);
				break;
			}
			
		if (count($all_campaigns) == 0) {
			echo "<div style='padding:10px;background-color:white'>No other selection campaign</div>";
			return;
		}

		echo "<div style='background-color:white;padding:10px'>";
		echo "Please select the subject to copy:";
		echo "<ul>";
		foreach($all_campaigns as $c) {
			echo "<li>".toHTML($c["name"]);
			echo "<ul>";
			$subjects = SQLQuery::create()
				->select("ExamSubject")
				->selectSubModel("SelectionCampaign", $c["id"])
				->execute();
			foreach ($subjects as $s) {
				echo "<li>";
				echo "<a href='#' onclick='copySubject(".$s["id"].",".$c["id"].");return false;'>";
				echo toHTML($s["name"]);
				echo "</a>";
				echo "</li>";
			}
			echo "</ul>";
			echo "</li>";
		}
		echo "</ul>";
		echo "</div>";
?>
<script type='text/javascript'>
function copySubject(subject_id, campaign_id) {
	var popup = window.parent.getPopupFromFrame(window);
	popup.freeze("Copying subject...");
	service.json("selection","exam/copy_subject",{subject:subject_id,campaign:campaign_id},function(res) {
		if (!res) popup.unfreeze();
		else popup.close();
	});
}
</script>
<?php 
	}
	
}
?>