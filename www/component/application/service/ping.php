<?php
class service_ping extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Extend the session, and get recent events"; }
	public function input_documentation() { echo "nothing"; }
	public function output_documentation() { echo "nothing"; }
	public function execute(&$component, $input) {
		echo "true";
	}
	
} 
?>