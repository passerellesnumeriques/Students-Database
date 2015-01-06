<?php 
class service_generate_transcript extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		if (!PNApplication::$instance->user_management->hasRight("consult_students_grades")) {
			// it can be the student itself
			$ok = false;
			if (in_array("student",PNApplication::$instance->user_management->people_types)) {
				if (PNApplication::$instance->user_management->people_id == $input["student"])
					$ok = true;
			}
			if (!$ok) {
				PNApplication::error("Access denied");
				return;
			}
		}
		require_once("component/transcripts/page/design.inc");
		if (!isset($input["id"]))
			generateTranscript("_".time(), $input["period"], @$input["specialization"]);
		else
			generatePublishedTranscript($input["id"], $input["student"], @$input["id_suffix"]);
	}
		
}
?>