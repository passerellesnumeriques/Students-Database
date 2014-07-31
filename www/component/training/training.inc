<?php 
class training extends Component {
	
	public function getPluginImplementations() {
		require_once("TrainingApplicationSection.inc");
		return array(
			new TrainingApplicationSection()
		);
	}
	
}
?>