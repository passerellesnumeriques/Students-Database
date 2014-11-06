<?php 
class teaching extends Component {

	public function getPluginImplementations() {
		require_once("TeacherPeopleTypePlugin.inc");
		require_once("TeacherAssignmentsPeopleProfilePagePlugin.inc");
		return array(
				new TeacherPeopleTypePlugin(),
				new TeacherAssignmentsPeopleProfilePagePlugin(),
		);
	}
	
}
?>