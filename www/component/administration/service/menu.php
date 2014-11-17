<?php 
class service_menu extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Provides the administration menu"; }
	public function inputDocumentation() { echo "No"; }
	public function outputDocumentation() { echo "The HTML to put in the menu"; }
	public function getOutputFormat($input) { return "text/html"; }
		
	public function execute(&$component, $input) {
		require_once("component/administration/AdministrationPlugin.inc");
		$pages = array();
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi)
				if ($pi instanceof AdministrationPlugin)
					foreach ($pi->getAdministrationPages() as $page)
						if ($page->canAccess())
							array_push($pages, $page);
		usort($pages, function($p1,$p2) {
			$s1 = $p1->getTitle();
			if ($s1 == "Dashboard") return -1;
			$s2 = $p2->getTitle();
			if ($s2 == "Dashboard") return 1;
			return strcasecmp($s1, $s2);
		});
		
		foreach ($pages as $page) {
			echo "<a class='application_left_menu_item'";
			echo " href='".$page->getPage()."'";
			echo ">";
			echo "<img src='".$page->getIcon16()."'/> ";
			echo toHTML($page->getTitle());
		}
	}
	
}
?>