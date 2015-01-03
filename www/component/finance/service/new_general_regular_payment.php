<?php 
class service_new_general_regular_payment extends Service {
	
	public function getRequiredRights() { return array("manage_finance"); }
	
	public function documentation() { echo "Create a new general regular payment of students"; }
	public function inputDocumentation() { echo "name, frequency"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$id = SQLQuery::create()->insert("FinanceRegularPayment", array("name"=>$input["name"],"frequency"=>$input["frequency"]));
		if ($id <> null) echo "true";
	}
	
}
?>