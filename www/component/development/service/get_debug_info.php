<?php 
class service_get_debug_info extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function execute(&$component, $input) {
		echo "{";
		echo "requests:".json_encode($component->requests);
		echo "}";
	}
	
}
?>