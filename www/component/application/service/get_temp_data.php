<?php 
class service_get_temp_data extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Get the value of a temporary data"; }
	public function inputDocumentation() { echo "id"; }
	public function outputDocumentation() { echo "value"; }

	/**
	 * @param application $component
	 */
	public function execute(&$component, $input) {
		echo "{value:".json_encode($component->getTemporaryData($input["id"]))."}";
	}
	
}
?>