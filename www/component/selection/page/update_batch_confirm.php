<?php 
require_once("SelectionPage.inc");
class page_update_batch_confirm extends SelectionPage {

	public function getRequiredRights() { return array("manage_selection_campaign"); }

	public function executeSelectionPage() {
		$batch_id = $_GET["batch"];
		$program_id = @$_GET["program"];
		// get applicants to be in the batch
		$q = SQLQuery::create()
			->select("Applicant")
			->whereNotValue("Applicant", "excluded", 1)
			->whereValue("Applicant", "applicant_decision", "Will come")
			->whereValue("Applicant", "final_decision", "Selected");
		if ($program_id <> null) $q->whereValue("Applicant","program",$program_id);
		PNApplication::$instance->people->joinPeople($q, "Applicant", "people", false);
		$applicants = $q->execute();
		$nb_selected = count($applicants);
		// get students in the batch
		$q = PNApplication::$instance->students->getStudentsQueryForBatches(array($batch_id));
		$q->bypassSecurity(true);
		PNApplication::$instance->people->joinPeople($q, "Student", "people");
#DEV
		$q->noWarning();
#END
		$q->join("People", "smlink_Applicant_People", array("id"=>"root"));
		$q->field("smlink_Applicant_People", "sm", "campaign_id");
		$students = $q->execute();
		// check what to do
		// 1- match applicants to be in the batch, with students already in the batch => list of applicants already there
		$applicants_already_there = array();
		for ($i = 0; $i < count($applicants); $i++) {
			for ($j = 0; $j < count($students); $j++) {
				if ($applicants[$i]["people_id"] == $students[$j]["people_id"]) {
					array_push($applicants_already_there, $applicants[$i]);
					array_splice($applicants, $i, 1);
					$i--;
					array_splice($students, $j ,1);
					break;
				}
			}
		}
		// 2- remaining applicants need to be included
		$applicants_to_include = $applicants;
		// 3- among the remaining students, those who come from the selection campaign will be removed, the others stay
		$students_staying = array();
		for ($i = 0; $i < count($students); $i++) {
			if ($students[$i]["campaign_id"] == PNApplication::$instance->selection->getCampaignId()) continue;
			array_push($students_staying, $students[$i]);
			array_splice($students, $i, 1);
			$i--;
		}
		$students_to_remove = $students;
		
		$nb_already = count($applicants_already_there);
		$nb_add = count($applicants_to_include);
		$nb_remove = count($students_to_remove);
		$nb_stay = count($students_staying);
		
		// print confirmation
		echo "<div style='background-color:white;padding:10px'>";
		echo $nb_selected." applicant".($nb_selected > 1 ? "s are" : " is")." selected from the process.<br/>";
		if ($nb_add > 0 || $nb_already > 0)
			echo "Currently, $nb_already ".($nb_already > 1 ? "are" : "is")." already in the batch, so $nb_add will be added.<br/>";
		if ($nb_stay > 0) {
			echo "Note that $nb_stay student".($nb_stay > 1 ? "s": "")." of this batch do".(count($nb_stay) < 2 ? "es" : "")." not come from this selection process,<br/>thus they will remain in the batch:<ul>";
			foreach ($students_staying as $p)
				echo "<li>".toHTML($p["first_name"]." ".$p["last_name"])."</li>";
			echo "</ul>";
		}
		if ($nb_remove > 0) {
			echo "The following $nb_remove applicant".($nb_remove > 1 ? "s are" : " is")." not anymore selected and will be removed from the batch:<ul>";
			foreach ($students_to_remove as $p)
				echo "<li>".toHTML($p["first_name"]." ".$p["last_name"])."</li>";
			echo "</ul>";
		}
		if ($nb_add > 0) {
			echo "The following $nb_add applicant".($nb_add > 1 ? "s were" : " was")." not in the batch, but ".($nb_add > 1 ? "are" : "is")." now selected and will be included into the batch:<ul>";
			foreach ($applicants_to_include as $p)
				echo "<li>".toHTML($p["first_name"]." ".$p["last_name"])."</li>";
			echo "</ul>";
		}
		if ($nb_already > 0) {
			echo "The following $nb_already applicant".($nb_already > 1 ? "s were" : " was")." already in the batch, and will remain as ".($nb_already > 1 ? "they are" : "it is")." still selected:<ul>";
			foreach ($applicants_to_include as $p)
				echo "<li>".toHTML($p["first_name"]." ".$p["last_name"])."</li>";
			echo "</ul>";
		}
		echo "<br/>Do you confirm ?";
		echo "</div>";
?>
<script type='text/javascript'>
var popup = window.parent.getPopupFromFrame(window);
popup.removeButtons();
popup.addYesNoButtons(function() {
	window.frameElement.confirmed();
	popup.close();
});
</script>
<?php 
	}
}
?>