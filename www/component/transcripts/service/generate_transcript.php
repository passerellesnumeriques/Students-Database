<?php 
class service_generate_transcript extends Service {
	
	public function getRequiredRights() { return array(); } // TODO
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		require_once("component/transcripts/page/design.inc");
		if (!isset($input["id"]))
			generateTranscript($input["period"], @$input["specialization"]);
		else
			generatePublishedTranscript($input["id"], $input["student"]);
	}
		
}
?>