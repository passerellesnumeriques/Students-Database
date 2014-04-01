<?php 
require_once "component/selection/SelectionJSON.inc";
class service_exam_remove_session extends Service {
	
	public function get_required_rights() { return array("can_access_selection_data","manage_applicant"); }
	public function documentation() {
		echo "Remove an exam session from its ID, if possible. If any applicants are assigned to this session, they are unassigned (if possible) and then the session is removed. All the query are performed within an SQL transaction";
	}
	public function input_documentation() {
		echo "<code>id</code> exam session ID";
	}
	public function output_documentation() {
		?>Object with two attributes:
		<ul>
			<li><code>applicants</code> NULL if no applicant was assigned to this session, else array of objects (one per applicant) with following attributes:<ul><li><code>done</code> {Boolean} true if the applicant was unassigned</li><li><code>error_performing</code> {Boolean} true if an error occured</li><li><code>error_has_grade</code>{String|NULL} NULL if has no exam grade yet, else error message about the grades</li><li><code>applicant</code> {Applicant} JSON applicant object</li></ul></li>
			<li><code>performed</code> {Boolean} true if the exam session was well removed (and the applicants assigned)</li>			
		</ul>
		
		<?php
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		if(isset($input["id"])){
			//Get all the applicants assigned
			$applicants = $component->getApplicantsAssignedToCenterEntity(null,$input["id"]);
			echo "{applicants:";
			if($applicants == null)
				echo "null";
			SQLQuery::startTransaction();
			$rollback = false;
			if($applicants <> null){
				//Unassign
				$first = true;
				echo "[";
				foreach($applicants as $applicant){
					if(!$first) echo ", ";
					$first = false;
					$res = $component->unassignApplicantFromCenterEntity($applicant["people_id"], null, $input["id"]);
					$applicant = SelectionJSON::Applicant(null,$applicant);
					if($res == false){
						echo "{done:false,error_performing:true,error_has_grade:null,applicant:".$applicant."}";
						$rollback = true;
					} else
						echo "{done:".json_encode($res["done"]).",error_performing:false,error_has_grade:".json_encode($res["error_has_grade"]).",applicant:".$applicant."}";
				}
				echo "]";
			}
			echo ", performed:";
			//Remove the session
			if(!$rollback){
				try{
					SQLQuery::create()
						->removeKey("ExamSession", $input["id"]);
				} catch (Exception $e){
					PNApplication::error($e);
				}
			
			}
			if(PNApplication::has_errors() || $rollback){
				SQLQuery::rollbackTransaction();
				echo "false";
			} else {
				SQLQuery::commitTransaction();
				echo "true";
			}
			echo "}";
		} else
				echo "false";
	}
	
}
?>