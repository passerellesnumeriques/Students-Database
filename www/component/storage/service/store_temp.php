<?php 
class service_store_temp extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Receive a file and store it temporarly"; }
	public function inputDocumentation() { echo "A file"; }
	public function outputDocumentation() { echo "the id of the temporarly stored file"; }
	
	public function execute(&$component, $input) {
		$ids = array();
		$names = array();
		$types = array();
		$sizes = array();
		$component->receive_upload($ids, $names, $types, $sizes, 10*60);
		if (count($ids) > 0)
			echo "{id:".$ids[0]."}";
	}
	
}
?>