<?php 
class service_menu extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Provides the administration menu"; }
	public function inputDocumentation() { echo "No"; }
	public function outputDocumentation() { echo "The HTML to put in the menu"; }
	public function getOutputFormat($input) { return "text/html"; }
		
	public function execute(&$component, $input) {
		require_once("component/administration/AdministrationPlugin.inc");
		foreach (PNApplication::$instance->components as $name=>$c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof AdministrationPlugin)) continue;
				foreach ($pi->getAdministrationPages() as $page) {
					if (!$page->canAccess()) continue;
					echo "<a class='application_left_menu_item'";
					echo " href='".$page->getPage()."'";
					echo ">";
					echo "<img src='".$page->getIcon16()."'/> ";
					echo htmlentities($page->getTitle());
				}
			}
		}
	}
	
}
?>