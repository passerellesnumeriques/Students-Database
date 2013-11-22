<?php 
class service_assign_specializations extends Service {
	
	public function get_required_rights() { return array("manage_batches"); }
	
	public function documentation() { echo "Assign/Unassign specializations to students"; }
	public function input_documentation() { echo "List of assignment: [{<code>student</code>:student's people id, <code>specialization</code>: specialization id or <code>null</code>}]"; }
	public function output_documentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		foreach ($input as $assignment)
			$component->assign_student_to_specialization($assignment["student"], $assignment["specialization"]);
		if (!PNApplication::has_errors())
			echo "true";
	}
	
}
?>