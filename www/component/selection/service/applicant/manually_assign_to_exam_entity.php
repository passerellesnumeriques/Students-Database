<?php 
require_once 'component/selection/SelectionJSON.inc';
class service_applicant_manually_assign_to_exam_entity extends Service {
	
	public function get_required_rights() {return array("can_access_selection_data");}
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {
	
	}
	
	public function execute(&$component, $input) {
		$mode = $input["mode"];
		$applicants = $input["applicants"];
		$target = $input["target"];
		$res = false;
		if($mode == "center")
			$res = PNApplication::$instance->selection->assignApplicantsToEC($applicants, $target);
		else if ($mode == "session"){
			$session_id = $target["session_id"];
			$room_id = $target["room_id"];
			$res = PNApplication::$instance->selection->assignApplicantsToRoomAndSession($applicants, $session_id, $room_id);
		}
		echo json_encode($res);
	}
}
?>