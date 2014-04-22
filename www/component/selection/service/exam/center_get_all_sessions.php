<?php 
class service_exam_center_get_all_sessions extends Service {
	
	public function get_required_rights() { return array("can_access_selection_data"); }
	public function documentation() {
		echo "Get all the exam sessions planned into an exam center";
	}
	public function input_documentation() {
		?>
		<code>EC_id</code> exam center ID
		<?php
	}
	public function output_documentation() {
		?>
		Array of <code>ExamSession</code> objects
		<?php
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		if(isset($input["EC_id"])){
			require_once 'component/selection/SelectionJSON.inc';
			echo SelectionJSON::ExamSessionsFromExamCenterID($input["EC_id"]);
		} else 
			echo "false";
	}
	
}
?>