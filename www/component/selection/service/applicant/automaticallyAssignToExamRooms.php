<?php 
class service_applicant_automaticallyAssignToExamRooms extends Service {
	
	public function get_required_rights() {return array("can_access_selection_data","manage_applicant");}
	
	public function documentation() {echo 'Automatically assign applicants to exam center rooms for a given session';}
	public function input_documentation() {
		echo "<code>session_id</code> exam session ID";
	}
	public function output_documentation() {
		?>
		<ul>
		  <li>false if an error occured </li>
		  <li>else object with two attributes (if one is set, the other is null):<ul><li><code>assigned</code> number of applicants assigned</li><li><code>error</code>string if nothing could be done explaining why (no slot remaining, no session, no applicant to assign)</li></ul></li>
		</ul>

		<?php
	}
	
	public function execute(&$component, $input) {
		if(isset($input["session_id"])){
			$res = $component->assignApplicantsToRoomsForASessionAutomatically($input["session_id"]);
			if($res == false)
				echo "false";
			if(is_string($res))
				echo "{assigned:null,error:".json_encode($res)."}";
			else {
				$nb_assigned = 0;
				foreach ($res as $applicants_assigned_ids)
					$nb_assigned += count($applicants_assigned_ids);
				echo "{assigned:".json_encode($nb_assigned).", error:null}";
			}
		} else 
			echo 'false';
	}
	
}
?>