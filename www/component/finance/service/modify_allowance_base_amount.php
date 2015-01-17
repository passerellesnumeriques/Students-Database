<?php 
class service_modify_allowance_base_amount extends Service {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function documentation() { echo "Modify the base amount for an allowance"; }
	public function inputDocumentation() { echo "allowance,change,batch"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		$students = PNApplication::$instance->students->getStudentsIdsForBatch($input["batch"]);
		$existing = SQLQuery::create()
			->select("StudentAllowance")
			->whereValue("StudentAllowance","allowance",$input["allowance"])
			->whereIn("StudentAllowance","student",$students)
			->whereNull("StudentAllowance","date")
			->execute();
		foreach ($existing as $e)
			SQLQuery::create()->updateByKey("StudentAllowance", $e["id"], array("amount"=>$e["amount"]+$input["change"]));
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>