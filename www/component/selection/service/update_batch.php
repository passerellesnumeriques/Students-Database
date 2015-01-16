<?php 
class service_update_batch extends Service {
	
	public function getRequiredRights() { return array("manage_selection_campaign"); }
	
	public function documentation() { echo "Update a batch of students from the one selected during the selection process"; }
	public function inputDocumentation() { echo "batch"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function mayUpdateSession() { return true; } // change batch for program
	
	public function execute(&$component, $input) {
		$batch_id = $input["batch"];
		$program_id = @$input["program"];
		if ($program_id <> null) $component->setProgramBatch($program_id, $batch_id);
		SQLQuery::startTransaction();
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
		
		// ---- Apply changes ----
		// 1-remove students not anymore selected
		if (count($students_to_remove) > 0) {
			$peoples_ids = array();
			foreach ($students_to_remove as $p) array_push($peoples_ids, $p["people_id"]);
			PNApplication::$instance->students->removeStudents($peoples_ids, true);
		}
		// 2-add new applicants
		if (count($applicants_to_include) > 0) {
			foreach ($applicants_to_include as $p)
				PNApplication::$instance->students->makeAsStudent($p["people_id"], $batch_id, true);
		}
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>