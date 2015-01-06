<?php 
require_once("component/application/ApplicationSectionPlugin.inc");
/**
 * Administration section
 */
class AdministrationApplicationSection implements ApplicationSectionPlugin {
	
	public function getId() { return "administration"; }
	public function getName() { return "Admin"; }
	public function getDescription() { return "Manage users and access rights, staffs, geographic data..."; }
	public function getPriority() { return 1000; }
	public function getIcon16White() { return "/static/administration/administration_white.png"; }
	public function getIcon32() { return "/static/administration/admin_32.png"; }
	public function getComponentsForURL() { return array("administration","geography","user_management"); }
	public function getMenuURL() { return "/dynamic/administration/service/menu"; }
	public function getDefaultPageURL() { return "/dynamic/administration/page/dashboard"; }
	
	public function canAccess() {
#SELECTION_TRAVEL
#return false;
#END
		require_once("component/administration/AdministrationPlugin.inc");
		foreach (PNApplication::$instance->components as $name=>$c)
			foreach ($c->getPluginImplementations() as $pi)
				if ($pi instanceof AdministrationPlugin)
					foreach ($pi->getAdministrationPages() as $page)
						if ($page->canAccess()) return true; // at least one page is accessible
		return false;
	}
	
}
?>