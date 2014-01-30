<?php
/**
 * The administration component aggregates administration functionalities from other components, through the interface {@link AdministrationPlugin}
 */ 
class administration extends Component {

	public function getPluginImplementations() {
		require_once("AdministrationPlugin.inc");
		return array(
			new AdministrationDashboardPlugin()
		);
	}
	
}
?>