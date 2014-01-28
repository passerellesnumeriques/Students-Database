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
	
	/**
	 * @param string $title Title of the page
	 * @param string $page URL of the page
	 * @param string $icon16 Icon (16x16 pixels) of the page
	 */
	public function __construct($title, $page, $icon16) {
		$this->title = $title;
		$this->page = $page;
		$this->icon16 = $icon16;
	}
	
	/** @return string URL of the 16x16 pixels icon */
	public function getIcon16() { return $this->icon16; }
	/** @return string The title of the page */
	public function getTitle() { return $this->title; }
	/** @return string The URL of the page */
	public function getPage() { return $this->page; }
	
}

class AdministrationDashboardPlugin implements AdministrationPlugin {
	
	public function getAdministrationPages() {
		return array(
			new AdministrationPage("Dashboard", "dynamic/administration/page/dashboard", theme::$icons_16["dashboard"])
		);
	}
	
}
?>