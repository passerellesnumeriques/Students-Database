<?php 
require_once("component/news/NewsPlugin.inc");
/**
 * Implementation of NewsPlugin, to provide General section of updates.
 */
class GeneralNewsPlugin implements NewsPlugin {
	
	public function getSections() { return array(new ApplicationNewsSection()); }
	
}

/**
 * General section of updates
 */
class ApplicationNewsSection implements NewsSection {

	public function getName() { return "application"; }
	public function getDisplayName() { return "General"; }
	public function getIcon() { return "/static/application/logo.png"; }
	public function getAccessRight() {
		// TODO
		return 1;
	}
	public function getCategories() { return array(); }
	
}
?>