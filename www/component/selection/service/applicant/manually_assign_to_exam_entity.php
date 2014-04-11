<?php 
require_once 'component/selection/SelectionJSON.inc';
class service_applicant_manually_assign_to_exam_entity extends Service {
	
	public function get_required_rights() {return array("can_access_selection_data");}
	
	public function documentation() {
		echo "Assign given applicants to an exam center entity (center, session, room)";
	}
	public function input_documentation() {
		?>
		<ul>
			<li><code>mode</code> string can be "center" if the given applicants shall be assigned to an exam center, or "session" if the applicants shall be assigned to an exam session and a room</li>
			<li><code>applicants</code> array | number ID(s) of the applicants to assign to the target</li>
			<li><code>target</code> Its content depends on the mode parameter: If mode == "center" must contain the exam center ID targeted. Else associative array containing two attributes:<ul><li><code>session_id</code> the ID of the session targeted</li><li><code>room_id</code> the ID of the room targeted during the selected session</li></ul></li>
		</ul>
		<?php
	}
	public function output_documentation() {
		echo "Returns boolean, true if well assigned, else false";
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