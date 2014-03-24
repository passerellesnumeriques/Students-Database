<?php 
class service_applicant_automaticallyAssignToSessionsAndRooms extends Service {
	
	public function get_required_rights() {return array("can_access_selection_data","manage_applicant");}
	
	public function documentation() {echo "Automatically assign applicants to session and rooms in an exam center";}
	public function input_documentation() {echo "<code>EC_id</code> number the exam center ID";}
	public function output_documentation() {
		?>
		<ul>
		  <li>If any error occured return false</li>
		  <li> else object with one attribute: <code>assigned</code> the number of applicants assigned</li>
		</ul>
		<?php
	}
	
	public function execute(&$component, $input) {
		if(isset($input["EC_id"])){
			//The two assignments are performed within a transaction
			try {
				$applicants_assigned_by_sessions = $component->assignApplicantsToSessionsAutomatically($input["EC_id"]);
				if(!is_array($applicants_assigned_by_sessions)){
					if(is_string($applicants_assigned_by_sessions))
						PNApplication::error($applicants_assigned_by_sessions);
					else if(!PNApplication::has_errors())//Must throw error to be sure that the transaction wont be committed
						PNApplication::error("An error occured, applicants cannot be assigned");						
				} else {
					$total_assigned = 0; //As the assignment by room is based on the remaining places, the number of applicants assigned in the sessions is the same as the number of applicants assigned in the rooms
					foreach ($applicants_assigned_by_sessions as $session_id => $applicants_ids){
						$res = $component->assignApplicantsToRoomsForASessionAutomatically($input["EC_id"], $session_id, $applicants_ids);
						if(!is_array($res)){
							if(is_string($res))
								PNApplication::error($res);
							else if(!PNApplication::has_errors())
								PNApplication::error("An error occured, applicants cannot be assigned");
							break;
						} else {
							foreach ($res as $applicants_assigned_by_room)
								$total_assigned += count($applicants_assigned_by_room);
						}
					}
				}
			} catch (Exception $e){
				PNApplication::error($e);
			}
			if(PNApplication::has_errors()){
				SQLQuery::rollbackTransaction();
				echo "false";
			} else {
				SQLQuery::commitTransaction();
				echo "{assigned:".$total_assigned."}";
			}
		} else 
			echo 'false';
	}
	
}
?>