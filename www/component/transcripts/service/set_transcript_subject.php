<?php 
class service_set_transcript_subject extends Service {
	
	public function getRequiredRights() { return array("edit_transcripts_design"); }

	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$period_id = $input["period"];
		$spe_id = $input["specialization"];
		$subject_id = $input["subject"];
		$period_id = $input["period"];
		$selected = $input["selected"];
		if (!$selected)
			SQLQuery::create()->removeKey("TranscriptSubjects", array("period"=>$period_id,"specialization"=>$spe_id,"subject"=>$subject_id));
		else
			SQLQuery::create()->insert("TranscriptSubjects", array("period"=>$period_id,"specialization"=>$spe_id,"subject"=>$subject_id));
		echo "true";
	}
	
}
?>