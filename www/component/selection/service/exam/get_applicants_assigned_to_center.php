<?php 
class service_exam_get_applicants_assigned_to_center extends Service {
	
	public function get_required_rights() { return array("can_access_selection_data","see_exam_center_detail"); }
	public function documentation() {
	
	}
	public function input_documentation() {
	}
	public function output_documentation() {

	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		if(!isset($input["EC_id"]))
			echo "false";
		else{
			if(!isset($input["count"])){
				echo "{applicants:";
				$applicants = null;
				if(isset($input["order_by"]))
					$applicants = $component->getApplicantsAssignedToEC($input["EC_id"],$input["order_by"]);
				else 
					$applicants = $component->getApplicantsAssignedToEC($input["EC_id"]);
				if($applicants == null)
					echo "null";
				else {
					require_once 'component/selection/SelectionJSON.inc';
					$first = true;
					echo "[";
					foreach ($applicants as $applicant){
						if(!$first) echo ', ';
						$first = false;
						echo SelectionJSON::Applicant(null, $applicant);
					}
					echo ']';
				}
				echo "}";
			} else {
				$total = $component->getApplicantsAssignedToEC($input["EC_id"]);
				if($total == null)
					echo "{count:'0'}";
				else 
					echo "{count:'".count($total)."'}";
			}
		}
	}
	
}
?>