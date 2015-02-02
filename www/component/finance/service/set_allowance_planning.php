<?php 
class service_set_allowance_planning extends Service {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function documentation() { echo "Set start and end dates for an allowance for the given batch"; }
	public function inputDocumentation() { echo "batch,allowance,start,end"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		require_once 'component/data_model/TableDefinition.inc';
		$batch_id = $input["batch"];
		$allowance_id = $input["allowance"];
		$start = \datamodel\ColumnDate::toTimestamp($input["start"]);
		$end = \datamodel\ColumnDate::toTimestamp($input["end"]);
		
		SQLQuery::startTransaction();
		$students_ids = PNApplication::$instance->students->getStudentsIdsForBatch($batch_id);
		if (count($students_ids) == 0) {
			// no student
			echo "true";
			return;
		}
		$allowance = SQLQuery::create()->select("Allowance")->whereValue("Allowance","id",$allowance_id)->executeSingleRow();
		// get base allowance and deductions
		$students_allowances_base = SQLQuery::create()
			->select("StudentAllowance")
			->whereValue("StudentAllowance", "allowance", $allowance_id)
			->whereIn("StudentAllowance", "student", $students_ids)
			->whereNull("StudentAllowance", "date")
			->execute();
		$bases = array();
		$students = array();
		foreach ($students_allowances_base as $base) {
			$base["deductions"] = array();
			$bases[$base["id"]] = $base;
			$students[$base["student"]] = $base["id"];
		}
		$students_deductions_base = SQLQuery::create()
			->select("StudentAllowanceDeduction")
			->whereIn("StudentAllowanceDeduction", "student_allowance", array_keys($bases))
			->execute();
		foreach ($students_deductions_base as $d) array_push($bases[$d["student_allowance"]]["deductions"], $d);
		// get previous dates
		$previous_start = null;
		$previous_end = null;
		$students_allowances_dates = SQLQuery::create()
			->select("StudentAllowance")
			->whereValue("StudentAllowance", "allowance", $allowance_id)
			->whereIn("StudentAllowance", "student", array_keys($students))
			->whereNotNull("StudentAllowance", "date")
			->orderBy("StudentAllowance", "date")
			->execute();
		$dates = array();
		foreach ($students_allowances_dates as $sa) {
			$date = \datamodel\ColumnDate::toTimestamp($sa["date"]);
			if ($previous_start == null || $date < $previous_start) $previous_start = $date;
			if ($previous_end == null || $date > $previous_end) $previous_end = $date;
			if (!isset($dates[$date])) $dates[$date] = array();
			array_push($dates[$date], $sa);
		}
		if ($previous_start <> null && $previous_start < $start) {
			// TODO remove previous, what about deductions ?
		}
		if ($previous_end <> null && $previous_end > $end) {
			// TODO remove previous, what about deductions ?
		}
		$date = $start;
		if ($allowance["frequency"] == "Weekly") {
			// go to next monday
			$d = getdate($date);
			while ($d["wday"] <> 1) {
				$date += 24*60*60;
				$d = getdate($date);
			}
		}
		$insert_allowance = array();
		$insert_deductions = array();
		$tz = date_default_timezone_get();
		date_default_timezone_set("GMT");
		do {
			if (!isset($dates[$date])) {
				// this is a new date
				$sql_date = date("Y-m-d",$date);
				for ($i = 0; $i < $allowance["times"]; $i++) {
					foreach ($students as $student_id=>$base_id) {
						$base = $bases[$base_id];
						$index = count($insert_allowance);
						array_push($insert_allowance, array(
							"student"=>$student_id,
							"allowance"=>$allowance_id,
							"date"=>$sql_date,
							"amount"=>$base["amount"],
							"paid"=>false
						));
						foreach ($base["deductions"] as $d) {
							array_push($insert_deductions, array(
								"student_allowance"=>$index,
								"amount"=>$d["amount"],
								"name"=>$d["name"]
							));
						}
					}
				}
			}
			switch ($allowance["frequency"]) {
				case "Daily": $date += 24*60*60; break;
				case "Weekly": $date += 7*24*60*60; break;
				case "Monthly":
					$d = getdate($date);
					$date = mktime(0,0,0,$d["mon"]+1,1,$d["year"]);
					break;
				case "Yearly":
					$d = getdate($date);
					$date = mktime(0,0,0,1,1,$d["year"]+1);
					break;
			}
		} while ($date <= $end);
		date_default_timezone_set($tz);
		// insert new items
		if (count($insert_allowance) > 0) {
			$ids = SQLQuery::create()->insertMultiple("StudentAllowance", $insert_allowance);
			if (count($insert_deductions) > 0) {
				for ($i = count($insert_deductions)-1; $i >= 0; --$i)
					$insert_deductions[$i]["student_allowance"] = $ids[$insert_deductions[$i]["student_allowance"]];
				SQLQuery::create()->insertMultiple("StudentAllowanceDeduction", $insert_deductions);
			}
		}
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>