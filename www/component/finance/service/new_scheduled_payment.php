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
			$descr = $payment["name"];
			if ($payment["times"] > 1) {
				$existing = SQLQuery::create()
					->select("ScheduledPaymentDate")
					->whereValue("ScheduledPaymentDate","regular_payment",$input["regular_payment"])
					->join("ScheduledPaymentDate","FinanceOperation",array("due_operation"=>"id"))
					->whereValue("FinanceOperation","people",$people_id)
					->whereValue("FinanceOperation","date",$date)
					->execute();
				for ($i = 0; $i < $payment["times"]; $i++) {
					$found = false;
					foreach ($existing as $e) if (substr($e["description"],0,strlen($payment["name"])+2+strlen("".($i+1))) == $payment["name"]." ".($i+1)."/") { $found = true; break; }
					if (!$found) break;
				}
				$descr .= " ".($i+1)."/".$payment["times"];
			}
			if ($payment["frequency"] == "Weekly") $descr .= " for week";
			$descr .= " of ";
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