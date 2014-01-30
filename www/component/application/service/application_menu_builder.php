<?php 
class service_application_menu_builder extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Provides JavaScript to build the application menu of the given component"; }
	public function input_documentation() { echo "<code>component</code>: component providing a menu"; }
	public function output_documentation() { echo "The JavaScript that builds the menu"; }
	
	public function get_output_format() { return "text/javascript"; }
	
	public function execute(&$component, $input) {
		include("component/".$input["component"]."/application_menu_provider.js");
	}
	
}
?>