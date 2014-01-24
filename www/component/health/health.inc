<?php 
class health extends Component {
	
	public function getPluginImplementations() {
		require_once("HealthPeoplePlugin.inc");
		return array(
				new HealthPeoplePlugin()
		);
	}
	
}
?>