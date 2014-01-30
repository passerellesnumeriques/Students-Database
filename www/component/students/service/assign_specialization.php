<?php 
class service_assign_specialization extends Service {
	
	public function get_required_rights() { return array("manage_batches"); }
	
	public function documentation() { echo "Assign/Unassign specializations to a student"; }
	public function input_documentation() { echo "<code>student</code>:student's people id, <code>specialization</code>: specialization id or <code>null</code>"; }
	public function output_documentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$component->assign_student_to_specialization($input["student"], $input["specialization"]);
		if (!PNApplication::has_errors())
			echo "true";
	}
	
}
?>