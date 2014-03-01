<?php 
class service_get_batch extends Service {
	
	public function get_required_rights() { return array("consult_curriculum"); }
	
	public function documentation() { echo "Retrieve a Batch JSON structure"; }
	public function input_documentation() { echo "<code>id</code>: batch id"; }
	public function output_documentation() { echo "Batch JSON structure"; }
	
	public function execute(&$component, $input) {
		require_once("component/curriculum/CurriculumJSON.inc");
		echo CurriculumJSON::BatchJSON($input["id"]);
	}
	
}
?>