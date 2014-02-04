<?php 
class service_application_menu_builder extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Provides JavaScript to build the application menu"; }
	public function input_documentation() { echo "No"; }
	public function output_documentation() { echo "The JavaScript that builds the menu"; }
	
	public function get_output_format() { return "text/javascript"; }
	
	public function execute(&$component, $input) {
		require_once("component/administration/AdministrationPlugin.inc");
		foreach (PNApplication::$instance->components as $name=>$c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof AdministrationPlugin)) continue;
				foreach ($pi->getAdministrationPages() as $page) {
					echo "addMenuItem(".json_encode($page->getIcon16()).", ".json_encode($page->getTitle()).", ".json_encode($page->getPage()).");";
				}
			}
		}
	}
	
}
?>