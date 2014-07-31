<?php 
class internship extends Component {
	
	public function getPluginImplementations() {
		require_once("InternshipApplicationSection.inc");
		return array(
			new InternshipApplicationSection()
		);
	}
	
}
?>