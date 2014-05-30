<?php 
class service_save_event extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() { echo "Save or create an event"; }
	public function inputDocumentation() { echo "<code>event</code>: Event object, with same format as service get"; }
	public function outputDocumentation() { echo "On success, returns the id and uid of the event"; }
	
	/**
	 * @param $component calendar 
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		$event = $input["event"];

		if ($event["id"] <= 0) unset($event["id"]);
		if (!$component->saveEvent($event))
			echo "false";
		else
			echo "{id:".json_encode($event["id"]).", uid:".json_encode($event["uid"])."}";
	}
	
}
?>