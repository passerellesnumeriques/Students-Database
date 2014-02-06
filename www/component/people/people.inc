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
		
	public function joinPeople(&$q, $from_table, $from_key) {
		require_once("PeopleJSON.inc");
		$q->join($from_table, "People", array($from_key=>"id"));
		PeopleJSON::PeopleSQL($q);
	}
}
?>