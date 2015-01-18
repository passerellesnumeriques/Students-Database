<?php
/**
 * Alumni component
 */ 
class alumni extends Component {
	
	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "ApplicationSectionPlugin":
				require_once("AlumniApplicationSection.inc");
				return array(
					new AlumniApplicationSection()
				);
		}
		return array();
	}
	
}
?>