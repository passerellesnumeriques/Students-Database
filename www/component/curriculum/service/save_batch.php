<?php 
class service_save_batch extends Service {
	
	public function get_required_rights() { return array("edit_curriculum"); }
	
	public function documentation() { echo "Save or create a batch, with periods and specializations"; }
	public function input_documentation() {
		// TODO
	}
	public function output_documentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		// TODO
	}
	
}
?>