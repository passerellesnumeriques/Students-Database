<?php 
class service_save_event extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Save or create an event"; }
	public function input_documentation() { echo "<code>event</code>: Event object, with same format as service get"; }
	public function output_documentation() { echo "On success, returns the id and uid of the event"; }
	
	/**
	 * @param $component calendar 
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		$event = $input["event"];
		$result = $component->saveEvent($event);
		if(isset($result["id"]))
			echo "{id:".json_encode($result["id"]).", uid:".json_encode($resul["uid"])."}";
		else
			echo "false";
	}
	
}
?>