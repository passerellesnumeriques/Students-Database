<?php 
class service_reassign_type extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	/**
	 * @param $component people
	 */
	public function execute(&$component, $input) {
		$people_id = $input["people"];
		$type = $input["type"];
		$pi = $component->getPeopleTypePlugin($type);
		$pi->reassign($people_id, $input["data"]);
	}
	
}
?>