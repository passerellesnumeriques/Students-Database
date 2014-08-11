<?php 
class lab_component extends Component{
	
	public function getPluginImplementations() {
		require_once("LabComponentApplicationSection.inc");
		return array(
			new LabComponentApplicationSection()
		);
	}
	
}
?>