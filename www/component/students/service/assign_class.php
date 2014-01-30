<?php 
class service_assign_class extends Service {
	
	public function get_required_rights() { return array("manage_batches"); }
	
	public function documentation() { echo "Assign/Unassign class to a student"; }
	public function input_documentation() { echo "<code>student</code>:student's people id, <code>clas</code>: class id or <code>null</code>, <code>period</code>: period"; }
	public function output_documentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$component->assign_student_to_class($input["student"], $input["clas"], $input["period"]);
		if (!PNApplication::has_errors())
			echo "true";
	}
	
}
?>