<?php 
class education extends Component {
	
	public function getPluginImplementations() {
		require_once("EducationApplicationSection.inc");
		return array(
			new EducationApplicationSection()
		);
	}
	
}
?>