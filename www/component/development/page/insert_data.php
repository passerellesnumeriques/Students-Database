<?php 
class page_insert_data extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
?>
<script type='text/javascript'>
window.frameElement._no_loading = true;
</script>
<?php
		if (isset($_GET["action"])) {
			switch ($_GET["action"]) {
				case "insert_applicants": $this->insertApplicants($_GET["campaign"]); return;
				case "insert_10000_applicants": $this->insert10000Applicants($_GET["campaign"]); return;
				case "big_campaign": $this->createBigCampaign(); return;
			}
		}
?>
Insert 1 000 000 applicants for Selection Campaign:<ul>
<?php
$campaigns = SQLQuery::create()->bypassSecurity()->noWarning()->select("SelectionCampaign")->execute();
foreach ($campaigns as $c)
	echo "<li><a href='?action=insert_applicants&campaign=".$c["id"]."'>".toHTML($c["name"])."</a></li>";
?>
</ul>
Insert 10 000 applicants for Selection Campaign:<ul>
<?php
$campaigns = SQLQuery::create()->bypassSecurity()->noWarning()->select("SelectionCampaign")->execute();
foreach ($campaigns as $c)
	echo "<li><a href='?action=insert_10000_applicants&campaign=".$c["id"]."'>".toHTML($c["name"])."</a></li>";
?>
</ul>
Create a selection campaign, with:<ul>
	<li>100 Information Sessions</li>
	<li>1000 applicants for each IS (100 000 applicants in total)</li>
	<li>50 Exam Centers, linked to 2 Information Sessions each (2000 applicants for each center)</li>
	<li>Plan 10 Exam sessions, with 200 applicants each</li>
	<li>Enter exam results for each session, with 100 passers, 80 failed, 20 absent (total = 100*10*50 = 50 000 passers)</li>
	<li>25 Interview Centers, linked to 2 Exam Centers each (2000 applicants for each center)</li>
	<li>Plan 10 Interview sessions, with 200 applicants each</li>
	<li>Enter interview results for each session, with 100 passers, 80 failed, 20 absent (total = 100*10*25 = 25 000 passers)</li>
</ul>
<a href='?action=big_campaign'>Do it!</a>
<?php 
	}
	
	private function insertPeoples($nb, $type) {
		$sql = "INSERT INTO `People` (`first_name`,`last_name`,`sex`,`types`) VALUES ";
		for ($i = 0; $i < $nb; $i++) {
			if ($i > 0) $sql .= ",";
			$sql .= "('FN".$i."_".rand(0,10000)."','LN".$i."_".rand(0,10000)."','".(rand(0,1) == 0 ? "M" : "F")."','/$type/')";
		}
		SQLQuery::getDataBaseAccessWithoutSecurity()->execute($sql);
		$id = SQLQuery::getDataBaseAccessWithoutSecurity()->getInsertID();
		$ids = array();
		for ($i = 0; $i < $nb; $i++) array_push($ids, $id+$i);
		return $ids;
	}
	
	private function insertApplicants($campaign_id) {
		$step = @$_GET["step"];
		if ($step == null) $step = 0;
		if ($step == 0)	$start = time(); else $start = intval($_GET["start"]);
		set_time_limit(300);
		$ids = $this->insertPeoples(5000, "applicant");
		$sql = "INSERT INTO `Applicant_$campaign_id` (`people`,`applicant_id`,`excluded`) VALUES ";
		for ($i = 0; $i < 5000; $i++) {
			if ($i > 0) $sql .= ",";
			$sql .= "(".$ids[$i].",'".$ids[$i]."',0)";
		}
		SQLQuery::getDataBaseAccessWithoutSecurity()->execute($sql);
		if ($step < 199) {
			$end = time();
			$nb_done = (($step+1)*5000);
			echo "$nb_done applicants inserted so far in ".($end-$start)." seconds (remaining ".(1000000*($end-$start)/$nb_done)." seconds";
			echo "<script type='text/javascript'>location.href = '?action=insert_applicants&campaign=".$campaign_id."&step=".($step+1)."&start=".$start."';</script>";
			return;
		}
		$end = time();
		echo "1 000 000 applicants inserted in ".($end-$start)." seconds.<br/>";
		echo "<a href='?'>Back</a>";
	}
	
	private function insert10000Applicants($campaign_id) {
		$start = time();
		set_time_limit(300);
		$ids = $this->insertPeoples(10000, "applicant");
		$sql = "INSERT INTO `Applicant_$campaign_id` (`people`,`applicant_id`,`excluded`) VALUES ";
		for ($i = 0; $i < 10000; $i++) {
			if ($i > 0) $sql .= ",";
			$sql .= "(".$ids[$i].",'".$ids[$i]."',0)";
		}
		SQLQuery::getDataBaseAccessWithoutSecurity()->execute($sql);
		$end = time();
		echo "10 000 applicants inserted in ".($end-$start)." seconds.<br/>";
		echo "<a href='?'>Back</a>";
	}
	
	private function createBigCampaign() {
		echo "TODO";
	}
}
?>