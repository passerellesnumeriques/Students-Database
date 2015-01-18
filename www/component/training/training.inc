<?php 
class training extends Component {
	
	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "ApplicationSectionPlugin":
				require_once("TrainingApplicationSection.inc");
				return array(
					new TrainingApplicationSection()
				);
		}
		return array();
	}
	
}
?>