<?php 
class service_remove_allowance_deduction extends Service {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function documentation() { echo "Remove a deduction for an allowance"; }
	public function inputDocumentation() { echo "allowance,batch,deduction_name"; }
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
			->executeSingleField();
		if (count($existing) > 0)
			SQLQuery::create()->removeKeys("StudentAllowanceDeduction", $existing);
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>