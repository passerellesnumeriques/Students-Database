<?php 
class service_applicant_get_assigned_to_rooms_for_session extends Service {
	
	public function get_required_rights() {return array("can_access_selection_data");}
	
	public function documentation() {
		echo "Get the applicants to all the rooms for an exam session";
	}
	public function input_documentation() {
		?>
		<ul>
		  <li><code>session_id</code> the exam session event ID</li>
		  <li><code>count</code> (optional) if true, only get the number of applicants assigned per session</li>
		</ul>

		<?php
	}
	public function output_documentation() {
		?>
		Object with two attributes:
		<ul>
			<li><code>rooms</code> can be:
				<ul>
					<li>NULL if no room in this center</li>
					<li>else array, with for each room of the center:<ul><li><code>id</code> room ID</li><li><code>name</code></li><li><code>capacity</code> room capacity</li><li><code>applicants</code> depends on the <code>count</code> input. If true, <code>applicants</code> contains the number of applicants assigned by room, else contains array of the applicants assigned (Applicants objects)</li></ul>
					</li>
				</ul>
			</li>
			<li><code>count_session</code> number of applicants assigned to this session</li>						
		</ul>

		<?php
	}
	
	public function execute(&$component, $input) {
		if(isset($input["session_id"])){
			echo "{rooms:";
			$data = $component->getApplicantsAssignedByRoomForSession($input["session_id"]);
			if ($data == null)
				echo "null";
			else {						
				echo "[";
				$first = true;
				foreach ($data as $room_id => $room){
					if(!$first) echo ', ';
					$first = false;
					echo "{id:".json_encode($room_id);
					echo ", name:".json_encode($room["name"]);
					echo ", capacity:".json_encode($room["capacity"]);
					if(isset($input["count"]) && $input["count"] == true){
						$count = $room["applicants"] == null ? 0 : count($room["applicants"]);
						echo ", applicants:".json_encode($count);
					} else {
						require_once 'component/selection/SelectionJSON.inc';
						echo ", applicants:[";
						$first_applicant = true;
						if($room["applicants"] <> null){
							foreach ($room["applicants"] as $row){
								if(!$first_applicant) echo ", ";
								$first_applicant = false;
								echo SelectionJSON::Applicant(null, $row);
							}
						}
						echo "]";
					}
					echo "}";
				}
				echo "]";				
			}
			$total_applicants_in_session = $component->getApplicantsAssignedToCenterEntity(null, $input["session_id"]);
			if ($total_applicants_in_session == null)
				$total_applicants_in_session = 0;
			else 
				$total_applicants_in_session = count($total_applicants_in_session);
			echo ", count_session:".json_encode($total_applicants_in_session);
			echo "}";				
		} else {
			echo "false";
		}
	}
	
}
?>