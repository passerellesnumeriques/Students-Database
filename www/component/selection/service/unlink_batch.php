<?php 
class service_unlink_batch extends Service {
	

	public function getRequiredRights() { return array("manage_selection_campaign"); }
	
	public function documentation() { echo "Unlink a batch of students from the selection process"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function mayUpdateSession() { return true; }
	
	public function execute(&$component, $input) {
		$campaign_id = $component->getCampaignId();
		if (isset($input["program"])) {
			foreach ($component->getPrograms() as $program)
				if ($program["id"] == $input["program"]) {
					$batch_id = $program["batch"];
					break;
				}
		} else {
			$batch_id = SQLQuery::create()->select("SelectionCampaign")->whereValue("SelectionCampaign","id",$campaign_id)->field("batch")->executeSingleValue();
		}
		
		SQLQuery::startTransaction();
		// get students to remove from the batch
		$q = PNApplication::$instance->students->getStudentsQueryForBatches(array($batch_id));
		$q->bypassSecurity(true);
#DEV
		$q->noWarning();
#END
		$q->join("Student", "smlink_Applicant_People", array("people"=>"root"));
		$q->whereValue("smlink_Applicant_People", "sm", $campaign_id);
		$q->field("Student","people","id");
		$students = $q->executeSingleField();
		if (count($students) > 0)
			PNApplication::$instance->students->removeStudents($students, true);
		if (isset($input["program"])) {
			SQLQuery::create()->updateByKey("SelectionProgram", $input["program"], array("batch"=>null));
			$component->setProgramBatch($input["program"], null);
		} else {
			SQLQuery::create()->updateByKey("SelectionCampaign", $campaign_id, array("batch"=>null));
		}
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>