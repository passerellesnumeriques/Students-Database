<?php 
class service_exam_can_rooms_be_removed_for_center extends Service {
	
	public function get_required_rights() { return array("can_access_selection_data"); }
	public function documentation() {
		
	}
	public function input_documentation() {
	}
	public function output_documentation() {
		?>
		
		<?php
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		if(isset($input["EC_id"])){
			require_once 'component/selection/SelectionJSON.inc';
			echo '{rooms:';
			//Get the center rooms
			$rooms = SQLQuery::create()
				->select("ExamCenterRoom")
				->field("ExamCenterRoom","id")
				->whereValue("ExamCenterRoom","exam_center",$input["EC_id"])
				->executeSingleField();
			if($rooms == null)
				echo 'null';
			else {
				echo '[';
				$first = true;
				foreach ($rooms as $room_id){
					if(!$first) echo ', ';
					$first = false;
					$can_be_removed = $component->canRoomBeRemovedWithoutConsequencesOnApplicantsAssignment($room_id);
					echo "{id:".json_encode($room_id).", can_be_removed:".json_encode($can_be_removed[0]);
					$name = SQLQuery::create()->select("ExamCenterRoom")->field("ExamCenterRoom","name")->whereValue("ExamCenterRoom","id",$room_id)->executeSingleValue();
					echo ", name:".json_encode($name);
					echo ', error_applicants:';
					if($can_be_removed[1] <> null){
						$first_applicant = true;
						echo '[';
						foreach ($can_be_removed[1] as $applicant){
							if(!$first_applicant) echo ', ';
							$first_applicant = false;
							echo SelectionJSON::Applicant(null, $applicant);
						}
						echo ']';
					} else echo "null";
					echo ', error_capacity:';
					if($can_be_removed[2] <> null){
						require_once 'component/calendar/CalendarJSON.inc';
						echo "[";
						$first_session = true;
						foreach ($can_be_removed[2] as $session_id => $count_assigned){
							if(!$first_session) echo ', ';
							echo '{session_event:';
							$first_session = false;
							echo CalendarJSON::CalendarEventFromID($session_id,PNApplication::$instance->selection->getCalendarId());
							echo ', assigned:'.json_encode($count_assigned)."}";
						}
						echo ']';
					} else echo "null";
					echo '}';
				}				
				echo ']';
			}
			echo '}';
		} else
			echo "false";
	}
	
}
?>