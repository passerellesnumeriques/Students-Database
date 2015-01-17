<?php 
class service_new_allowance extends Service {
	
	public function getRequiredRights() { return array("manage_finance"); }
	
	public function documentation() { echo "Create a new allowance for students"; }
	public function inputDocumentation() { echo "name, frequency, times"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$id = SQLQuery::create()->insert("Allowance", array(
			"name"=>$input["name"],
			"frequency"=>$input["frequency"],
			"times"=>$input["times"]
		));
		if ($id <> null) echo "true";
	}
	
}
?>