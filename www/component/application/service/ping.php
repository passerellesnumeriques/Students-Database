<?php
class service_ping extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() { echo "Extend the session, and get recent events"; }
	public function inputDocumentation() { echo "nothing"; }
	public function outputDocumentation() { echo "nothing"; }
	public function execute(&$component, $input) {
		echo "true";
	}
	
} 
?>