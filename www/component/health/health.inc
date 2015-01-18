<?php 
class health extends Component {
	
	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "PeopleProfilePagePlugin":
				require_once("HealthPeopleProfilePagePlugin.inc");
				return array(
					new HealthPeopleProfilePagePlugin()
				);
		}
		return array();
	}
	
}
?>