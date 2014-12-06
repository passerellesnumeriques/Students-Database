<?php 
class service_interview_reset_results extends Service {
	
	public function getRequiredRights() { return array("edit_interview_results"); }
	
	public function documentation() { echo "Save interview results"; }
	public function inputDocumentation() { echo "applicants: list of results to save by applicant"; }
	public function outputDocumentation() { echo "list of passers"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();

		
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>