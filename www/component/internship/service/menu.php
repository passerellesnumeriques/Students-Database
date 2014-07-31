<?php 
class service_menu extends Service {
	
	public function getRequiredRights() { return array(); } // TODO
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		echo "Internship not yet started";
	}
	
}
?>