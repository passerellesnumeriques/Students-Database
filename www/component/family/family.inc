<?php 
class family extends Component {
	
	public function getPluginImplementations() {
		require_once("FamilyBackgroundProfilePage.inc");
		return array(
			new FamilyBackgroundProfilePage()
		);
	}
	
}
?>