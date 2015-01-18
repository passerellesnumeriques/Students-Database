<?php 
class education extends Component {
	
	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "ApplicationSectionPlugin":
				require_once("EducationApplicationSection.inc");
				return array(
					new EducationApplicationSection()
				);
		}
		return array();
	}
	
}
?>