<?php
require_once "component/selection/SelectionJSON.inc";
class service_applicant_unassign_from_center_entity extends Service{
	public function get_required_rights(){return array("can_access_selection_data","manage_applicant");}
	public function input_documentation(){
		?>
		<ul>
		<li><code>people_id</code> the people_id (primary key of Applicant table)</li>
		<li><code>EC_id</code> NULL | the exam center ID from which the applicant shall be unassigned</li>		
		<li><code>session_id</code> NULL | the session ID from which the applicant shall be unassigned</li>
		<li><code>room_id</code> NULL | the room ID from which the applicant shall be unassigned</li>
		</ul>
		<?php
	}
	public function output_documentation(){
		?>
		Object with 4 attributes:
		<ul>
		<li><code>done</code> {Boolean} true if the applicant was unassigned</li>
		<li><code>error_performing</code> {Boolean} true if an error occured</li>
		<li><code>error_assigned_to_session</code> {String|NULL} NULL if not assigned to any session, else error message containing the name of the session</li>
		<li><code>error_assigned_to_room</code>{String|NULL} NULL if not assigned to any room, else error message containing the name of the room</li>
		<li><code>error_has_grade</code>{String|NULL} NULL if has no exam grade yet, else error message about the grades</li>
		</ul>
		<?php
	}
	
	public function documentation(){
	 echo "Unassign an applicant from an exam center entity (center, room, session), after checking it is possible";
	}
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component,$input){
		$people_id = $input["people_id"];
		$EC_id = @$input["EC_id"];
		$session_id = @$input["session_id"];
		$room_id = @$input["room_id"];
		$res = $component->unassignApplicantFromCenterEntity($people_id, $EC_id, $session_id, $room_id);
		if($res == false)
			echo "{done:false,error_performing:true,error_assigned_to_session:null,error_assigned_to_room:null,error_has_grade:null}";
		else
			echo "{done:".json_encode($res["done"]).",error_performing:false,error_assigned_to_session:".json_encode($res["error_assigned_to_session"]).",error_assigned_to_room:".json_encode($res["error_assigned_to_room"]).",error_has_grade:".json_encode($res["error_has_grade"])."}";		
	}

}	
?>