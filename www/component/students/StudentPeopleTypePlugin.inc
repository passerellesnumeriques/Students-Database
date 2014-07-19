<?php 
require_once("component/people/PeopleTypePlugin.inc");
class StudentPeopleTypePlugin implements PeopleTypePlugin {
	
	public function getSecurityPriority() { return 50; }
	
	public function getId() { return "student"; }
	public function getName() { return "Student"; }
	public function isStandalone() { return true; }
	
	public function canRead() { return PNApplication::$instance->user_management->has_right("consult_students_list"); }
	public function canWrite() { return PNApplication::$instance->user_management->has_right("manage_batches"); }
	public function canRemove() { return PNApplication::$instance->user_management->has_right("manage_batches"); }
	
	public function getPeopleRowDescription($people) {
		$q = SQLQuery::create()->bypassSecurity()
			->select("Student")
			->whereValue("Student", "people", $people["id"])
			;
		PNApplication::$instance->curriculum->joinBatch($q, "Student", "batch");
		$batch = $q->field("StudentBatch","name")->executeSingleRow();
		if ($batch == null) return "Student";
		return "Student of batch ".$batch["name"];
	}
}
?>