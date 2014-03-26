<?php
/**
 * Component managing the list of staff
 */
class staff extends Component {
	
	public function getPluginImplementations() {
		require_once("StaffAdministrationPlugin.inc");
		require_once("StaffPeopleTypePlugin.inc");
		return array(
			new StaffAdministrationPlugin(),
			new StaffPeopleTypePlugin()
		);
	}
	
}
?>