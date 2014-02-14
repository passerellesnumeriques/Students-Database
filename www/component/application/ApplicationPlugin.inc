<?php 
interface ApplicationPlugin {
	
	public function getPriority();
	
	public function getIcon16();
	public function getIcon32();
	public function getName();
	public function getInfoText();
	
	public function getPageURL();
	public function getMenuProviderService();
	
}

class ApplicationDashboard implements ApplicationPlugin {
	public function getPriority() { return 0; }
	
	public function getIcon16() { return theme::$icons_16["dashboard"]; }
	public function getIcon32() { return theme::$icons_32["dashboard"]; }
	public function getName() { return "Dashboard"; }
	public function getInfoText() { return "General information"; }
	
	public function getPageURL() { return "/dynamic/application/page/dashboard"; }
	public function getMenuProviderService() { return "/static/application/application_menu_provider.js"; }
}
?>