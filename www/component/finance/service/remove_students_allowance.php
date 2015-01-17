<?php 
class service_remove_students_allowance extends Service {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function documentation() { echo "Remove an allowance"; }
	public function inputDocumentation() { echo "allowance,(batch|student)"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		if (isset($input["batch"]))
			$students = PNApplication::$instance->students->getStudentsIdsForBatch($input["batch"]);
		else
			$students = array($input["student"]);
		$ids = SQLQuery::create()
			->select("StudentAllowance")
			->whereValue("StudentAllowance","allowance",$input["allowance"])
			->whereIn("StudentAllowance","student",$students)
			->field("id")
			->executeSingleField();
		if (count($ids) > 0)
			SQLQuery::create()->removeKeys("StudentAllowance", $ids);
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>