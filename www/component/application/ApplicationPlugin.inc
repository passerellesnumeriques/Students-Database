<?php
/**
 * Interface to implement to add a section to the application (main menu)
 */ 
interface ApplicationPlugin {
	
	/** Used to order the sections: lowest priority comes first
	 * @return integer priority
	 */
	public function getPriority();
	
	/** Icon (16x16 pixels) of the section
	 * @return string URL of the icon
	 */
	public function getIcon16();
	/** Icon (32x32 pixels) of the section
	 * @return string URL of the icon
	 */
	public function getIcon32();
	/** Name of the section
	 * @return string name
	 */
	public function getName();
	/** Description of the section
	 * @return string description
	 */
	public function getInfoText();
	
	/** Page to load to access to the section
	 * @return string URL of the page
	 */
	public function getPageURL();
	/** Service to call to populate the application menu
	 * @return string URL of the service
	 */
	public function getMenuProviderService();
	
}

/**
 * Implements the Dashboard section of the application
 */
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