<?php 
class service_modify_allowance_deduction extends Service {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function documentation() { echo "Modify the amount of a deduction for an allowance"; }
	public function inputDocumentation() { echo "allowance,change,batch,deduction_name"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		require_once 'component/data_model/DataBaseLock.inc';
		$students = PNApplication::$instance->students->getStudentsIdsForBatch($input["batch"]);
		$existing = SQLQuery::create()
			->select("StudentAllowance")
			->whereValue("StudentAllowance","allowance",$input["allowance"])
			->whereIn("StudentAllowance","student",$students)
			->whereNull("StudentAllowance","date")
			->join("StudentAllowance","StudentAllowanceDeduction",array("id"=>"student_allowance"))
			->whereValue("StudentAllowanceDeduction","name",$input["deduction_name"])
			->field("StudentAllowanceDeduction","id")
			->field("StudentAllowanceDeduction","amount")
			->execute();
		if (count($existing) > 0) {
			$keys = array();
			foreach ($existing as $e) array_push($keys, $e["id"]);
			$locked_by = null;
			$locks = DataBaseLock::lockRows("StudentAllowanceDeduction", $keys, $locked_by);
			if ($locks == null) {
				PNApplication::error("Deductions are being edited by $locked_by and cannot be modified right now.");
				return;
			}
			for ($i = count($existing)-1; $i >= 0; $i--)
				SQLQuery::create()->updateByKey("StudentAllowanceDeduction", $existing[$i]["id"], array("amount"=>$existing[$i]["amount"]+$input["change"]), $locks[$i]);
			DataBaseLock::unlockMultiple($locks);
		}
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>