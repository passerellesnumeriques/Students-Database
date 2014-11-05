<?php 
class service_send_feedback extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Post a ticket"; }
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		
	}
	
}
?>