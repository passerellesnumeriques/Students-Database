<?php 
class alumni extends Component {
	
	public function getPluginImplementations() {
		require_once("AlumniApplicationSection.inc");
		return array(
			new AlumniApplicationSection()
		);
	}
	
}
?>