<?php
/**
 * Component managing the list of staff
 */
class staff extends Component {
	
	public function getPluginImplementations() {
		require_once("StaffPeoplePlugin.inc");
		require_once("StaffAdministrationPlugin.inc");
		return array(
			new StaffPeoplePlugin(),
			new StaffAdministrationPlugin()
		);
	}
	
}
?>