<?php
/**
 * The administration component aggregates administration functionalities from other components, through the interface {@link AdministrationPlugin}
 */ 
class administration extends Component {

	public function getPluginImplementations() {
		require_once("AdministrationPlugin.inc");
		require_once("AdministrationApplicationSection.inc");
		return array(
			new AdministrationDashboardPlugin(),
			new ApplicationAdministrationPlugin(),
			new AdministrationApplicationSection()
		);
	}
	
	public function initRequest() {
		Component::allowServiceWithoutLogin("administration", "store_stats");
	}
	
}
?>