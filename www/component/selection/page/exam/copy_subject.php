<?php 
class page_exam_copy_subject extends Page {
	
	public function getRequiredRights() { return array("manage_exam_subject"); }
	
	public function execute() {
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
			echo "<li>".htmlentities($c["name"]);
			echo "<ul>";
			$subjects = SQLQuery::create()
				->select("ExamSubject")
				->selectSubModel("SelectionCampaign", $c["id"])
				->execute();
			foreach ($subjects as $s) {
				echo "<li>";
				echo "<a href='/dynamic/selection/page/exam/subject?id=".$s["id"]."&campaign_id=".$c["id"]."'>";
				echo htmlentities($s["name"]);
				echo "</a>";
				echo "</li>";
			}
			echo "</ul>";
			echo "</li>";
		}
		echo "</ul>";
		echo "</div>";
	}
	
}
?>