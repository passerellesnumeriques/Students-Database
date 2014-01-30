<?php 
class selection_Tests extends ComponentTests {
	
	public function getFunctionalitiesTests() {
		return array(
			"CreateSelectRenameRemoveCampaign",
			"GetSaveConfigGetCampaigns"
		);
	}
	public function getServicesTests() {
		return array(
			"CreateRenameRemoveCampaign",
			"SetCampaign",
		);
	}
	public function getUITests() {
		return array(
		);
	}
	
}
?>