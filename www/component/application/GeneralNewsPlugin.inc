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
		if (PNApplication::$instance->user_management->has_right("post_general_updates"))
			return 2;
		return 1; // everybody can see
	}
	public function getCategories() { return array(); }
	
}
?>