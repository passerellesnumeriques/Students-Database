<?php 
class service_exam_get_applicants_assigned_to_center_entity extends Service {
	
	public function get_required_rights() { return array("can_access_selection_data","see_exam_center_detail"); }
	public function documentation() {
		echo "Get the applicants assigned to a center entity (center, session, room), or the number of applicants assigned to this entity";
	}
	public function input_documentation() {
		?>
		<ul>
		  <li><code>EC_id</code> number | NULL exam center ID to get applicants assigned to this exam center, else NULL</li>
		  <li><code>session_id</code> number | NULL exam session event ID to get applicants assigned to this exam session, else NULL</li>
		  <li><code>room_id</code> number | NULL exam center room session ID to get applicants assigned to this room, else NULL</li>
		  <li><code>order_by</code> string | NULL (optional) can be "name" or "applicant_id" the order by condition to set</li>
		  <li><code>count</code> boolean | NULL  true if only the number of applicants is required</li>
		</ul>
		If nor EC_id, nor session_id, nor room_id are set, get all the applicants of the campaign
		<?php
	}
	public function output_documentation() {
		?>
		<ul>
		<li>If count == true:
		<code>count</code> number of applicants</li>
		<li>else <code>applicants</code> NULL if no applicant, or array of JSON Applicants objects</li>
		</ul>
		<?php
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		$order_by = @$input["order_by"];
		$EC_id = @$input["EC_id"];
		$session_id = @$input["session_id"];
		$room_id = @$input["room_id"];
		//Normalize data
		$EC_id = (is_string($EC_id) && strlen($EC_id) == 0) ? null : $EC_id;
		$session_id = (is_string($session_id) && strlen($session_id) == 0) ? null : $session_id;
		$room_id = (is_string($room_id) && strlen($room_id) == 0) ? null : $room_id;
		$order_by = (is_string($order_by) && strlen($order_by) == 0) ? null : $order_by;
		$applicants = $component->getApplicantsAssignedToCenterEntity($EC_id,$session_id,$room_id,$order_by);
		if(!isset($input["count"])){
			echo "{applicants:";			
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
			if($applicants == null)
				echo "{count:'0'}";
			else 
				echo "{count:'".count($applicants)."'}";
		}
	}
	
}
?>