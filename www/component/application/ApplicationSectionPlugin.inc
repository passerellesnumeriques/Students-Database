<?php 
interface ApplicationSectionPlugin {
	
	/** The Identifier of the section */
	public function getId();
	/** The name displayed */
	public function getName();
	/** Short text describing the section, that will be display as a tooltip or together with the name of the section */
	public function getDescription();
	/** Priority used to order the sections when displaying them: lower is the value, higher is the priority */
	public function getPriority();
	/** URL of the white icon 16x16 pixels */
	public function getIcon16White();
	/** URL of the icon 32x32 pixels */
	public function getIcon32();
	/** List of components' name that will be used to detect on which section we are when loading a new page */
	public function getComponentsForURL();
	/** URL to load the menu on the left side */
	public function getMenuURL();
	/** URL of the default page which will be displayed when the user enter the section */
	public function getDefaultPageURL();
	
}

class HomeApplicationSection implements ApplicationSectionPlugin {
	
	public function getId() { return "home"; }
	public function getName() { return "Home"; }
	public function getDescription() { return ""; }
	public function getPriority() { return 0; }
	public function getIcon16White() { return "/static/application/home_white.png"; }
	public function getIcon32() { return ""; }
	public function getComponentsForURL() { 
		return array(
			"application", // For home page
			"calendar", // For Your Calendars
			"people" // For Your profile
		);
	}
	public function getMenuURL() { return "/dynamic/application/service/home_menu"; }
	public function getDefaultPageURL() { return "/dynamic/application/page/overview"; }
	
}
?>