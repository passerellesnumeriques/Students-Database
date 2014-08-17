<?php 
require_once("component/news/NewsPlugin.inc");
class SelectionNewsPlugin implements NewsPlugin {

	public function getSections() {
		return array(
			new SelectionSection()
		);
	}
	
}

class SelectionSection implements NewsSection {
	public function getName() { return "selection"; }
	public function getDisplayName() { return "Selection"; }
	public function getIcon() { return "/static/selection/selection_32.png"; }
	public function getAccessRight() {
		if (PNApplication::$instance->components["user_management"]->has_right("manage_selection_campaign",true))
			return 2;
		if (PNApplication::$instance->components["user_management"]->has_right("can_access_selection_data",true))
			return 1;
		return 0;
	}
	public function getCategories() {
		return array(); // TODO
	}
	public function getTagsFilters() { return array(); }
}

?>