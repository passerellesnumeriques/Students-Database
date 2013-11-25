<?php 
class service_new_class extends Service {
	
	public function get_required_rights() { return array("manage_batches"); }
	
	public function documentation() { echo "Create a new class within the given academic periods"; }
	public function input_documentation() { echo "name, specialization, period"; }
	public function output_documentation() { echo "On success, returns the <code>id</code>: the id of the newly created class"; }
	
	public function execute(&$component, $input) {
		$id = SQLQuery::create()->insert("AcademicClass", array(
			"name"=>$input["name"],
			"specialization"=>$input["specialization"],
			"period"=>$input["period"]
		));
		echo "{id:".$id."}";
	}
	
}
?>