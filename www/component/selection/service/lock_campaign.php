<?php 
class service_lock_campaign extends Service {
	
	public function getRequiredRights() { return array("manage_selection_campaign"); }
	
	public function documentation() { echo "Lock the current selection campaign"; }
	public function inputDocumentation() { echo "reason"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function mayUpdateSession() { return true; } // to update the status in the selection component
	
	public function execute(&$component, $input) {
		if ($component->freezeCampaign($input["reason"]))
			echo "true";
	}
	
}
?>