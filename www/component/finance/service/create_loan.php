<?php 
class service_create_loan extends Service {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function documentation() { echo "Create a new loan for a student"; }
	public function inputDocumentation() { echo "people,date,reason,operations"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		
		$loan_id = SQLQuery::create()->insert("Loan",array("date"=>$input["date"],"reason"=>$input["reason"]));
		if ($loan_id == null) return;
		
		foreach ($input["operations"] as $op) {
			$op_id = SQLQuery::create()->insert("FinanceOperation",array("people"=>$input["people"],"amount"=>$op["amount"],"date"=>$op["date"],"description"=>$op["description"]));
			if ($op_id == null) return;
			SQLQuery::create()->insert("ScheduledPaymentDate",array("due_operation"=>$op_id,"loan"=>$loan_id));
		}
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>