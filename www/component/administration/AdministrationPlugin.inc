<?php
/**
 * Provides pages in the Administration section of the application
 */ 
interface AdministrationPlugin {
	
	/**
	 * Provides the list of pages to display in the Administration section of the application
	 * @return AdministrationPage[] list of pages
	 */
	public function getAdministrationPages();
	
}

/**
 * Describes a page to be displayed in the Administration section of the application
 */
class AdministrationPage {
	
	/** @var string $title Title of the page (displayed in the menu) */
	private $title;
	/** @var string $page URL of the page */
	private $page;
	/** @var string $icon16 Icon to be displayed next to the title */
	private $icon16;
	/** @var string $icon32 Icon to be displayed next to the title */
	private $icon32;
	/** @var string $info_text The tooltip to explain the page */
	private $info_text;
	private $can_access_function;
	
	/**
	 * @param string $title Title of the page
	 * @param string $page URL of the page
	 * @param string $icon16 Icon (16x16 pixels) of the page
	 * @param string $icon32 Icon (32x32 pixels) of the page
	 * @param string $info_text text describing the page that will be displayed in a tooltip
	 */
	public function __construct($title, $page, $icon16, $icon32, $info_text, $can_access_function) {
		$this->title = $title;
		$this->page = $page;
		$this->icon16 = $icon16;
		$this->icon32 = $icon32;
		$this->info_text = $info_text;
		$this->can_access_function = $can_access_function;
	}
	
	/** @return string URL of the 16x16 pixels icon */
	public function getIcon16() { return $this->icon16; }
	/** @return string URL of the 32x32 pixels icon */
	public function getIcon32() { return $this->icon32; }
	/** @return string The title of the page */
	public function getTitle() { return $this->title; }
	/** @return string The URL of the page */
	public function getPage() { return $this->page; }
	/** @return string The tooltip to explain the page */
	public function getInfoText() { return $this->info_text; }
	/** return true if the current user can access to this page */
	public function canAccess() { return call_user_func($this->can_access_function); }
	
}

/**
 * Implementation of AdministrationPlugin to provide the Dashboard page
 */
class AdministrationDashboardPlugin implements AdministrationPlugin {
	
	public function getAdministrationPages() {
		return array(
			new AdministrationPage("Dashboard", "/dynamic/administration/page/dashboard", theme::$icons_16["dashboard"], theme::$icons_32["dashboard"], "Go back to the administration dashboard", 
				function() {
					foreach (PNApplication::$instance->components as $name=>$c)
						foreach ($c->getPluginImplementations() as $pi)
							if ($pi instanceof AdministrationPlugin && !($pi instanceof AdministrationDashboardPlugin))
								foreach ($pi->getAdministrationPages() as $page)
									if ($page->canAccess()) return true; // at least one page is accessible
				}
			)
		);
	}
	
}

class ApplicationAdministrationPlugin implements AdministrationPlugin {
	public function getAdministrationPages() {
		return array(
				new AdministrationPage("Application", "/dynamic/administration/page/app_admin", "/static/application/logo_16.png", "/static/application/logo_32.png", "Manage the application, deploy new version...", function() { return PNApplication::$instance->user_management->has_right("manage_application"); })
		);
	}
}
?>