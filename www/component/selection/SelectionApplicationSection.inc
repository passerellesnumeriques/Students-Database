<?php 
require_once("component/application/ApplicationSectionPlugin.inc");
class SelectionApplicationSection implements ApplicationSectionPlugin {
	
	public function getId() { return "selection"; }
	public function getName() { return "Selection"; }
	public function getDescription() { return "Access to the different steps of the selection process"; }
	public function getPriority() { return 10; }
	public function getIcon16White() { return "/static/selection/selection_white.png"; }
	public function getIcon32() { return "/static/selection/selection_32.png"; }
	public function getComponentsForURL() { return array("selection"); }
	public function getMenuURL() { return "/dynamic/selection/service/menu"; }
	public function getDefaultPageURL() { return "/dynamic/selection/page/selection_main_page"; }
	public function canAccess() {
#SELECTION_TRAVEL
#return true;
#END
		return PNApplication::$instance->user_management->hasRight("can_access_selection_data");
	}
	
}
?>