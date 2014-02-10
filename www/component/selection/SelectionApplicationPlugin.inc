<?php 
require_once("component/application/ApplicationPlugin.inc"); 
class SelectionApplicationPlugin implements ApplicationPlugin {
	public function getPriority() { return 10; }
	
	public function getIcon16() { return "/static/selection/selection_16.png"; }
	public function getIcon32() { return "/static/selection/selection_32.png"; }
	public function getName() { return "Selection"; }
	public function getInfoText() { return "Access to the different steps of the selection process"; }
	
	public function getPageURL() { return "/dynamic/selection/page/selection_main_page"; }
	public function getMenuProviderService() { return "/dynamic/selection/service/application_menu_builder"; }
}
?>