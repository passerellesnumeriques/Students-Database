<?php 
class service_set_student_allowance_deduction extends Service {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function documentation() { echo "Set/Remove/Create deduction for an allowance for a student"; }
	public function inputDocumentation() { echo "student,amount,allowance[,deduction][,deduction_name]"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$student_id = $input["student"];
		$allowance_id = $input["allowance"];
		$amount = floatval($input["amount"]);
		$deduction_id = @$input["deduction"];
		$deduction_name = @$input["deduction_name"];
		
		SQLQuery::startTransaction();
		if ($amount == 0) {
			// remove
			SQLQuery::create()->removeKey("StudentAllowanceDeduction", $deduction_id);
		} else if ($deduction_id > 0) {
			// update
			SQLQuery::create()->updateByKey("StudentAllowanceDeduction", $deduction_id, array("amount"=>$amount));
		} else {
			// create
			$student_allowance_id = SQLQuery::create()->select("StudentAllowance")->whereValue("StudentAllowance","student",$student_id)->whereValue("StudentAllowance","allowance",$allowance_id)->whereNull("StudentAllowance","date")->field("id")->executeSingleValue();
			SQLQuery::create()->insert("StudentAllowanceDeduction", array(
				"student_allowance"=>$student_allowance_id,
				"amount"=>$amount,
				"name"=>$deduction_name
			));
		}
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>