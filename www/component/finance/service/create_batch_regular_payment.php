<?php 
class service_create_batch_regular_payment extends Service {
	
	public function getRequiredRights() { return array("manage_finance"); }
	
	public function documentation() { echo "Configure regular payment for a batch"; }
	public function inputDocumentation() { echo "batch, payment, start, end, amount"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		require_once 'component/data_model/Model.inc';
		
		$payment_id = $input["payment"];
		$batch_id = $input["batch"];
		$start = datamodel\ColumnDate::splitDate($input["start"]);
		$end = datamodel\ColumnDate::splitDate($input["end"]);
		$amount = floatval($input["amount"]);
		
		SQLQuery::startTransaction();
		
		$payment = SQLQuery::create()->select("FinanceRegularPayment")->whereValue("FinanceRegularPayment","id",$payment_id)->executeSingleRow();
		$batch = PNApplication::$instance->curriculum->getBatch($batch_id);
		$batch_start = datamodel\ColumnDate::splitDate($batch["start_date"]);
		//$batch_end = datamodel\ColumnDate::splitDate($batch["end_date"]);
		
		$students_ids = PNApplication::$instance->students->getStudentsIdsForBatch($batch_id);
		if (count($students_ids) == 0) {
			PNApplication::error("There is no student in this batch!");
			return;
		}
		
		$year = $start["year"];
		$month = $start["mon"];
		$day = 1;
		if ($year == $batch_start["year"] && $month == $batch_start["mon"]) $day = $batch_start["mday"];
		if ($payment["frequency"] == "Weekly") {
			// go to next monday
			do {
				$ts = mktime(0,0,0,$month,$day,$year);
				$d = getdate($ts);
				if ($d["wday"] == 1) break;
				$day++;
			} while (true);
			$day = $d["mday"];
			$month = $d["mon"];
			$year = $d["year"];
		}
		do {
			$payments = array();
			foreach ($students_ids as $people_id)
				array_push($payments, array(
					"people"=>$people_id,
					"amount"=>-$amount,
					"date"=>$year."-".$month."-".$day
				));
			$payments_ids = SQLQuery::create()->insertMultiple("FinanceOperation", $payments);
			$schedules = array();
			for ($i = 0; $i < count($students_ids); $i++)
				array_push($schedules, array(
					"due_operation"=>$payments_ids[$i],
					"regular_payment"=>$payment_id
				));
			SQLQuery::create()->insertMultiple("ScheduledPaymentDate", $schedules);
			switch ($payment["frequency"]) {
				case "Daily":
					$d = getdate(mktime(0,0,0,$month,$day+1,$year));
					$day = $d["mday"];
					$month = $d["mon"];
					$year = $d["year"];
					break;
				case "Weekly":
					$d = getdate(mktime(0,0,0,$month,$day+7,$year));
					$day = $d["mday"];
					$month = $d["mon"];
					$year = $d["year"];
					break;
				case "Monthly":
					$day = 1;
					if (++$month == 13) { $month = 1; $year++; }
					break;
				case "Yearly":
					$year++;
					break;
				default:
					PNApplication::error("Unknown frequency: ".$payment["frequency"]);
					return;
			}
		} while ($year < $end["year"] || $month < $end["mon"] || $day < $end["mday"]);
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>