<?php 
class service_unlock_campaign extends Service {
	
	public function getRequiredRights() { return array("manage_selection_campaign"); }
	
	public function documentation() { echo "Unlock the current selection campaign"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function mayUpdateSession() { return true; } // to update the status in the selection component
	
	public function execute(&$component, $input) {
		if ($component->unfreezeCampaign())
			echo "true";
	}
	
}
?>