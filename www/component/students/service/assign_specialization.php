<?php 
class service_assign_specialization extends Service {
	
	public function getRequiredRights() { return array("manage_batches"); }
	
	public function documentation() { echo "Assign/Unassign specializations to a student"; }
	public function inputDocumentation() { echo "<code>student</code>:student's people id, <code>specialization</code>: specialization id or <code>null</code>"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$component->assignStudentToSpecialization($input["student"], $input["specialization"]);
		if (!PNApplication::hasErrors())
			echo "true";
	}
	
}
?>