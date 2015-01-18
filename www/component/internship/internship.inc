<?php 
class internship extends Component {
	
	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "ApplicationSectionPlugin":
				require_once("InternshipApplicationSection.inc");
				return array(
					new InternshipApplicationSection()
				);
		}
		return array();
	}
	
}
?>