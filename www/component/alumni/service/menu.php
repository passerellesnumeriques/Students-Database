<?php 
class service_menu extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Provide the left side menu for the alumni section"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "the HTML"; }
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		echo "Alumni not yet started";
	}
	
}
?>