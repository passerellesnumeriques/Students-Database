<?php 
class health extends Component {
	
	public function getPluginImplementations() {
		require_once("HealthPeopleProfilePagePlugin.inc");
		return array(
			new HealthPeopleProfilePagePlugin()
		);
	}
	
}
?>