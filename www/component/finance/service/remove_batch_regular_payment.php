<?php 
class service_remove_batch_regular_payment extends Service {
	
	public function getRequiredRights() { return array("manage_finance"); }
	
	public function documentation() { echo "Remove a regular payment for a batch"; }
	public function inputDocumentation() { echo "payment,batch"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		$students_ids = PNApplication::$instance->students->getStudentsIdsForBatch($input["batch"]);
		$ops_ids = SQLQuery::create()
			->select("FinanceOperation")
			->whereIn("FinanceOperation","people",$students_ids)
			->join("FinanceOperation","ScheduledPaymentDate",array("id"=>"due_operation"))
			->whereValue("ScheduledPaymentDate","regular_payment",$input["payment"])
			->field("FinanceOperation","id")
			->executeSingleField();
		SQLQuery::create()->removeKeys("FinanceOperation", $ops_ids);
		if (PNApplication::hasErrors()) return;
		SQLQuery::commitTransaction();
		echo "true";
	}
	
}
?>