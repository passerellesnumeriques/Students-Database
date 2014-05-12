<?php
class service_exam_get_applicants_assigned_to_rooms extends Service{
	public function getRequiredRights(){return array("can_access_selection_data");}
	public function inputDocumentation(){
		?>
		<code>ids</code> array of exam center room ids
		<?php
	}
	public function outputDocumentation(){
		?>Array containing objects as:
		<ul>
			<li><code>room</code> the exam center room ID</li>
			<li><code>assigned</code> array containing the Applicants objects of the applicants assigned if any, else NULL</li>
		</ul>
		<?php
	}
	public function documentation(){
		echo "Get the applicants assigned to given exam center rooms";
	}
	public function execute(&$component,$input){
		require_once 'component/selection/SelectionJSON.inc';
		if(!isset($input["ids"])) return false;
		$applicants = PNApplication::$instance->selection->getApplicantsAssignedToRooms($input["ids"]);
		$r = "[";
		$first_room = true;
		foreach ($applicants as $room_id => $result){
			if(!$first_room)
				$r .= ", ";
			$first_room = false;
			$r .= "{room:".json_encode($room_id).", assigned:";
			if($result == null)
				$r .= "null";
			else {
				$r .= "[";
				$first = true;
				foreach ($result as $applicant){
					if(!$first)
						$r .= ", ";
					$first = false;
					$r .= SelectionJSON::Applicant(null, $applicant);
				}
				$r .= "]";
			}
			$r .= "}";
		}
		$r .= "]";
		echo $r;
	}
}	
?>