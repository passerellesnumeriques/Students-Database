<?php
class service_exam_get_applicants_assigned_to_rooms extends Service{
	public function get_required_rights(){return array("can_access_selection_data");}
	public function input_documentation(){
		?>
		<code>ids</code> array of exam center room ids
		<?php
	}
	public function output_documentation(){
		?>Array containing objects as:
		<ul>
			<li><code>room</code> the exam center room ID</li>
			<li><code>assigned</code> array containing the applicants_ids of the applicants assigned if any, else NULL</li>
		</ul>
		<?php
	}
	public function documentation(){
		echo "Get the applicants assigned to given exam center rooms";
	}
	public function execute(&$component,$input){
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
				foreach ($result as $applicant_id){
					if(!$first)
						$r .= ", ";
					$first = false;
					$r .= json_encode($applicant_id);
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