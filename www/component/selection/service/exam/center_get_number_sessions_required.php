<?php 
class service_exam_center_get_number_sessions_required extends Service {
	
	public function getRequiredRights() { return array("can_access_selection_data","see_exam_center_detail"); }
	public function documentation() {
		echo "Get the number of sessions required for an exam center (based on the number of applicants assigned and the rooms capacity)";
	}
	public function inputDocumentation() {
		echo "<code>EC_id</code> number the exam center ID";
	}
	public function outputDocumentation() {
		?>
		Objects with two attributes:
		<ul>
		  <li><code>required</code> number | string the number of sessions required, or "No room yet" if no room is set in this exam center yet</li>
		  <li><code>total_assigned</code> number the number of applicants assigned to the exam center</li>
		</ul>

		<?php
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		if(isset($input["EC_id"])){
			//Get the number of applicants assigned to the exam center
			$assigned = $component->getApplicantsAssignedToCenterEntity($input["EC_id"]);		
			$assigned = $assigned == null ? 0 : count($assigned); 
			//Get the max capacity from the rooms
			$max = $component->getExamCenterCapacity($input["EC_id"]);			
			$nb_sessions = 0;
			if($max == 0)
				$nb_sessions = "No room yet";
			if($assigned > 0 && $max > 0){
				while (($assigned - ($nb_sessions * $max)) > 0){
					$nb_sessions++;
				}
			}
			echo "{required:".json_encode($nb_sessions).', total_assigned:'.json_encode($assigned).'}';
		} else 
			echo "false";
	}
	
}
?>