<?php 
class service_create_temp_data extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Create a new temporary data in database"; }
	public function inputDocumentation() { echo "value"; }
	public function outputDocumentation() { echo "id"; }

	/**
	 * @param application $component
	 */
	public function execute(&$component, $input) {
		echo "{id:".$component->createTemporaryData($input["value"])."}";
	}
	
}
?>