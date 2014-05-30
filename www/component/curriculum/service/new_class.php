<?php 
class service_new_class extends Service {
	
	public function getRequiredRights() { return array("manage_batches"); }
	
	public function documentation() { echo "Create a new class within the given academic periods"; }
	public function inputDocumentation() { echo "name, specialization, period"; }
	public function outputDocumentation() { echo "On success, returns the <code>id</code>: the id of the newly created class"; }
	
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