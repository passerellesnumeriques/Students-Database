<?php 
class service_exam_center_get_all_sessions extends Service {
	
	public function get_required_rights() { return array("can_access_selection_data"); }
	public function documentation() {
		
	}
	public function input_documentation() {
		
	}
	public function output_documentation() {
		
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