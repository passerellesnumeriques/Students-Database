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
			->select("PaymentOperation")
			->whereValue("PaymentOperation","payment_operation",$op_id)
			->join("PaymentOperation","FinanceOperation",array("due_operation"=>"id"))
			->executeSingleRow();
		if ($payment_of <> null) {
			$min = 0;
			$other_payments = SQLQuery::create()
				->select("PaymentOperation")
				->whereValue("PaymentOperation","due_operation",$payment_of["due_operation"])
				->whereNotValue("PaymentOperation","payment_operation",$op_id)
				->join("PaymentOperation","FinanceOperation",array("payment_operation"=>"id"))
				->execute();
			$other_amount = 0;
			foreach ($other_payments as $p) $other_amount += floatval($p["amount"]);
			$max = -floatval($payment_of["amount"])-$other_amount;
		} else {
			$payments = SQLQuery::create()
				->select("PaymentOperation")
				->whereValue("PaymentOperation","due_operation",$op_id)
				->join("PaymentOperation","FinanceOperation",array("payment_operation"=>"id"))
				->execute();
			$paid = 0;
			foreach ($payments as $p) $paid += floatval($p["amount"]);
			$max = -$paid;
		}
		$update = array();
		if (isset($input["amount"])) {
			$amount = floatval($input["amount"]);
			if ($min !== null && $amount < $min) {
				PNApplication::error("Invalid amount: this operation cannot be less than $min");
				return;
			}
			if ($max !== null && $amount > $max) {
				PNApplication::error("Invalid amount: this operation cannot be more than $max");
				return;
			}
			$update["amount"] = $amount;
		}
		if (isset($input["date"])) $update["date"] = $input["date"];
		if (isset($input["description"])) $update["description"] = $input["description"];
		SQLQuery::create()->updateByKey("FinanceOperation", $op_id, $update);
		if (!PNApplication::hasErrors()) echo "true";
	}
	
}
?>