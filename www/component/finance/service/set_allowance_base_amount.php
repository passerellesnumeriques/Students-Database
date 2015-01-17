<?php 
class service_set_allowance_base_amount extends Service {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function documentation() { echo "Set the base amount for an allowance"; }
	public function inputDocumentation() { echo "allowance,amount,(batch|student)"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		if (isset($input["batch"]))
			$students = PNApplication::$instance->students->getStudentsIdsForBatch($input["batch"]);
		else
			$students = array($input["student"]);
		$existing = SQLQuery::create()
			->select("StudentAllowance")
			->whereValue("StudentAllowance","allowance",$input["allowance"])
			->whereIn("StudentAllowance","student",$students)
			->whereNull("StudentAllowance","date")
			->execute();
		$update = array();
		foreach ($existing as $e) {
			array_push($update, $e["id"]);
			array_splice($students, array_search($e["student"], $students), 1);
		}
		if (count($update) > 0)
			SQLQuery::create()->updateAllKeys("StudentAllowance", $update, array("amount"=>$input["amount"]));
		if (count($students) > 0 && (!isset($input["skip_no_allowance"]) || !$input["skip_no_allowance"])) {
			$insert = array();
			foreach ($students as $student)
				array_push($insert, array(
					"student"=>$student,
					"allowance"=>$input["allowance"],
					"date"=>null,
					"amount"=>$input["amount"]
				));
			SQLQuery::create()->insertMultiple("StudentAllowance", $insert);
		}
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>