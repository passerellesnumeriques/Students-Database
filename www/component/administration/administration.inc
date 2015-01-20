<?php
/**
 * The administration component aggregates administration functionalities from other components, through the interface {@link AdministrationPlugin}
 */ 
class administration extends Component {

	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "AdministrationPlugin":
				require_once("AdministrationPlugin.inc");
				return array(
					new AdministrationDashboardPlugin(),
					new ApplicationAdministrationPlugin(),
				);
			case "ApplicationSectionPlugin":
				require_once("AdministrationApplicationSection.inc");
				return array(
					new AdministrationApplicationSection()
				);
		}
		return array();
	}
	
	public function initRequest() {
		Component::allowServiceWithoutLogin("administration", "store_stats");
	}
	
}
?>