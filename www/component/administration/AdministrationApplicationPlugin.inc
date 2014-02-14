<?php
require_once("component/application/ApplicationPlugin.inc"); 
class AdministrationApplicationPlugin implements ApplicationPlugin {
	public function getPriority() { return 10000; }
	
	public function getIcon16() { return "/static/administration/admin_16.png"; }
	public function getIcon32() { return "/static/administration/admin_32.png"; }
	public function getName() { return "Administration"; }
	public function getInfoText() { return "Manage and configure application: users, staff, database..."; }
	
	public function getPageURL() { return "/dynamic/administration/page/dashboard"; }
	public function getMenuProviderService() { return "/dynamic/administration/service/application_menu_builder"; }
}
?>