<?php 
class service_save_operation extends Service {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$op_id = $input["id"];
		$min = null;
		$max = null;
		$payment_of = SQLQuery::create()
			->select("ScheduledPaymentDateOperation")
			->whereValue("ScheduledPaymentDateOperation","operation",$op_id)
			->join("ScheduledPaymentDateOperation","FinanceOperation",array("schedule"=>"id"))
			->executeSingleRow();
		if ($payment_of <> null) {
			$min = 0;
			$other_payments = SQLQuery::create()
				->select("ScheduledPaymentDateOperation")
				->whereValue("ScheduledPaymentDateOperation","schedule",$payment_of["schedule"])
				->whereNotValue("ScheduledPaymentDateOperation","operation",$op_id)
				->join("ScheduledPaymentDateOperation","FinanceOperation",array("operation"=>"id"))
				->execute();
			$other_amount = 0;
			foreach ($other_payments as $p) $other_amount += floatval($p["amount"]);
			$max = -floatval($payment_of["amount"])-$other_amount;
		} else {
			$payments = SQLQuery::create()
				->select("ScheduledPaymentDateOperation")
				->whereValue("ScheduledPaymentDateOperation","schedule",$op_id)
				->join("ScheduledPaymentDateOperation","FinanceOperation",array("operation"=>"id"))
				->execute();
			$paid = 0;
			foreach ($payments as $p) $paid += floatval($p["amount"]);
			$max = -$paid;
		}
		$amount = floatval($input["amount"]);
		if ($min !== null && $amount < $min) {
			PNApplication::error("Invalid amount: this operation cannot be less than $min");
			return;
		}
		if ($max !== null && $amount > $max) {
			PNApplication::error("Invalid amount: this operation cannot be more than $max");
			return;
		}
		SQLQuery::create()->updateByKey("FinanceOperation", $op_id, array(
			"amount"=>$amount,
			"date"=>$input["date"],
			"description"=>$input["description"]
		));
		if (!PNApplication::hasErrors()) echo "true";
	}
	
}
?>