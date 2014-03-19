<?php
require_once "component/selection/SelectionJSON.inc";
class service_applicant_unassign_from_center extends Service{
	public function get_required_rights(){return array("can_access_selection_data","manage_applicant");}
	public function input_documentation(){
		?>
		<ul>
		<li><code>EC_id</code> the exam center ID</li>
		<li><code>people_id</code> the people_id (primary key of Applicant table)</li>
		</ul>
		<?php
	}
	public function output_documentation(){
		?>
		Object with 3 attributes
		<ul>
		<li><code>done</code> {Boolean} true if the applicant was unassigned</li>
		<li><code>error</code> {Boolean} true if an error occured</li>
		<li><code>session</code> {String|NULL} NULL if not assigned to any session, else error message containing the name of the session</li>
		<li><code>room</code>{String|NULL} NULL if not assigned to any room, else error message containing the name of the room</li>
		</ul>
		<?php
	}
	
	public function documentation(){
	 echo "Unassign an applicant from an exam center, after checking it is possible";
	}
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component,$input){
		if(isset($input["EC_id"]) && isset($input["people_id"])){
			$res = $component->unassignApplicantFromCenter($input["EC_id"],$input["people_id"]);
			if($res == false)
				echo "{done:false,error:true,session:null,room:null}";
			else if($res == true)
				echo "{done:true,error:false,session:null,room:null}";
			else if(is_array($res)){
				echo "{done:false,error:false,session:".json_encode($res[1]).",room".json_encode($res[2])."}";
			}
		} else {
			echo "false";
		}
	}

}	
?>