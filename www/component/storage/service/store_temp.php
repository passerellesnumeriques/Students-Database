<?php 
class service_store_temp extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Receive a file and store it temporarly"; }
	public function input_documentation() { echo "A file"; }
	public function output_documentation() { echo "the id of the temporarly stored file"; }
	public function get_output_format($input) { return "text/plain"; }
	
	public function execute(&$component, $input) {
		$ids = array();
		$names = array();
		$types = array();
		$sizes = array();
		$component->receive_upload($ids, $names, $types, $sizes, 10*60);
		if (count($ids) > 0)
			echo $ids[0];
	}
	
}
?>