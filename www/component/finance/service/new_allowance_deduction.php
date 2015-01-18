<?php 
class service_new_allowance_deduction extends Service {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function documentation() { echo "Create a new deduction for an allowance"; }
	public function inputDocumentation() { echo "allowance,name,amount,batch"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		$students = PNApplication::$instance->students->getStudentsIdsForBatch($input["batch"]);
		$ids = SQLQuery::create()
			->select("StudentAllowance")
			->whereValue("StudentAllowance","allowance",$input["allowance"])
			->whereIn("StudentAllowance","student",$students)
			->whereNull("StudentAllowance","date")
			->field("id")
			->executeSingleField();
		$insert = array();
		foreach ($ids as $id)
			array_push($insert, array(
				"student_allowance"=>$id,
				"name"=>$input["name"],
				"amount"=>$input["amount"]
			));
		if (count($insert) > 0)
			SQLQuery::create()->insertMultiple("StudentAllowanceDeduction", $insert);
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>