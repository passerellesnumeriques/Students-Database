<?php 
class service_interview_status extends Service {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		echo "TODO";
	}

}
?>