<?php 
interface AdministrationPlugin {
	
	/**
	 * @return AdministrationPage[]
	 */
	public function getAdministrationPages();
	
}

class AdministrationPage {
	
	private $title;
	private $page;
	private $icon16;
	
	public function __construct($title, $page, $icon16) {
		$this->title = $title;
		$this->page = $page;
		$this->icon16 = $icon16;
	}
	
	public function getIcon16() { return $this->icon16; }
	public function getTitle() { return $this->title; }
	public function getPage() { return $this->page; }
	
}
?>