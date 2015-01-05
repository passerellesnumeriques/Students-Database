<?php 
class service_student_payment extends Service {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$people_id = $input["student"];
		$date = $input["date"];
		SQLQuery::startTransaction();
		foreach ($input["operations"] as $op) {
			$amount = $op["amount"];
			$description = $op["description"];
			$op_id = SQLQuery::create()->insert("FinanceOperation", array(
				"people"=>$people_id,
				"date"=>$date,
				"amount"=>$amount,
				"description"=>$description
			));
			if ($op_id == null) return;
			if (isset($op["schedule"])) {
				SQLQuery::create()->insert("PaymentOperation", array(
					"due_operation"=>$op["schedule"],
					"payment_operation"=>$op_id
				));
			}
		}
		if (PNApplication::hasErrors()) return;
		SQLQuery::commitTransaction();
		echo "true";
	}
}
?>