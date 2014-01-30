<?php
/**
 * Component managing information about any kind of people (a student, a contact point in a company, a staff...)
 */
class people extends Component {

	public function getPluginImplementations() {
		require_once("PeoplePeoplePlugin.inc");
		return array(
			new PeoplePeoplePlugin()
		);
	}
		
}
?>