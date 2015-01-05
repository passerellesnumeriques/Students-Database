<?php 
class service_remove_operation extends Service {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function documentation() { echo "Remove an operation"; }
	public function inputDocumentation() { echo "id"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::create()->removeKey("FinanceOperation", $input["id"]);
		if (!PNApplication::hasErrors()) echo "true";
	}
	
}
?>