<?php 
class discipline extends Component {
	
	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "PeopleProfilePagePlugin":
				require_once("DisciplinePeopleProfilePagePlugin.inc");
				return array(
					new DisciplinePeopleProfilePagePlugin()
				);
		}
		return array();
	}
	
}
?>