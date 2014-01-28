<?php 
class service_application_menu_builder extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function get_output_format() { return "text/javascript"; }
	
	public function execute(&$component, $input) {
		include("component/".$input["component"]."/application_menu_provider.js");
	}
	
}
?>