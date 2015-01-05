<?php 
class service_new_scheduled_payment extends Service {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function documentation() { echo "Create a new scheduled payment"; }
	public function inputDocumentation() { echo "student,date,amount[,regular_payment]"; }
	public function outputDocumentation() { echo "id: id of new operation"; }
	
	public function execute(&$component, $input) {
		require_once 'component/data_model/TableDefinition.inc';
		$people_id = $input["student"];
		$date = $input["date"];
		$date_ts = datamodel\ColumnDate::toTimestamp($date);
		if (isset($input["regular_payment"])) {
			$payment = SQLQuery::create()->select("FinanceRegularPayment")->whereValue("FinanceRegularPayment", "id", $input["regular_payment"])->executeSingleRow();
			$descr = $payment["name"]." of ";
			switch ($payment["frequency"]) {
				case "Daily":
				case "Weekly":
					$descr .= date("d M Y", $date_ts);
					break;
				case "Monthly":
					$descr .= date("F Y", $date_ts);
					break;
				case "Yearly":
					$descr .= date("Y", $date_ts);
					break;
			}
		}
		SQLQuery::startTransaction();
		$due_id = SQLQuery::create()->insert("FinanceOperation",array(
			"people"=>$people_id,
			"date"=>$date,
			"amount"=>-floatval($input["amount"]),
			"description"=>$descr
		));
		if ($due_id == null) return;
		if (isset($input["regular_payment"])) {
			SQLQuery::create()->insert("ScheduledPaymentDate", array(
				"due_operation"=>$due_id,
				"regular_payment"=>$input["regular_payment"]
			));
		}
		if (PNApplication::hasErrors()) return;
		SQLQuery::commitTransaction();
		echo "{id:$due_id}";
	}
	
}
?>