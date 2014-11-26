<?php 
class service_remove_event extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Remove an event"; }
	public function inputDocumentation() { echo "calendar, event"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		if ($component->removeEvent($input["event"], $input["calendar"]) && !PNApplication::hasErrors())
			echo "true";
	}
	
}
?>