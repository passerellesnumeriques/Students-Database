<?php 
class service_assign_group extends Service {
	
	public function getRequiredRights() { return array("manage_batches"); }
	
	public function documentation() { echo "Assign/Unassign a student to a group"; }
	public function inputDocumentation() { echo "<code>student</code>:student's people id, <code>group_type</code>: group type id, <code>group</code>: group id or <code>null</code>, <code>period</code>: period"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$component->assignStudentToGroup($input["student"], $input["group_type"], $input["group"], $input["period"]);
		if (!PNApplication::hasErrors())
			echo "true";
	}
	
}
?>