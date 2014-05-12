<?php 
class service_applicant_get_assignment_to_EC_figures extends Service {
	
	public function getRequiredRights() {return array("can_access_selection_data");}
	
	public function documentation() {echo "Get the main figures about applicants assignment to exam centers";}
	public function inputDocumentation() {
		echo 'No';
	}
	public function outputDocumentation() {
		?>
		<ul>
			<li><code>not_assigned</code> Number of applicants not assigned to any exam center</li>
			<li><code>total</code> total number of applicants</li>
			<li><code>remaining_per_center</code> array with objects for each exam center:<ul><li><code>EC_name</code> exam center name</li><li><code>EC_id</code> exam center ID</li><li><code>no_session</code> NULL | Number if not null, number > 0 of applicants assigned to the center but with no session</li><li><code>no_room</code> NULL | number if not null, number > 0 of applicants assigned to a session but not to any room</li></ul></li>
		</ul>		
		<?php
	}
	
	public function execute(&$component, $input) {
		$applicants_total = SQLQuery::create()
			->bypassSecurity()
			->select("Applicant")
			->count()
			->executeSingleValue();
		$applicants_total == null ? 0 : $applicants_total;
		
		$applicants_not_assigned = SQLQuery::create()
			->bypassSecurity()
			->select("Applicant")
			->count()
			->whereNull('Applicant', "exam_center")
			->executeSingleValue();
		$applicants_not_assigned == null ? 0 : $applicants_not_assigned;
		
		$all_centers = SQLQuery::create()
			->bypassSecurity()
			->select("ExamCenter");
		require_once 'component/selection/SelectionJSON.inc';
		SelectionJSON::ExamCenterSQL($all_centers);
		$all_centers = $all_centers->execute();
		$remaining_per_center = array();
		$json_remaining_per_center = null;
		if($all_centers <> null){
			foreach ($all_centers as $center){
				//Get the applicants assigned to center but not assigned to any session
				$no_session = SQLQuery::create()
					->select("Applicant")
					->count()
					->whereValue("Applicant", "exam_center", $center["id"])
					->whereNull("Applicant", "exam_session")
					->executeSingleValue();
				$remaining_per_center[$center["id"]] = array();
				$remaining_per_center[$center["id"]]["center"] = $center;
				if($no_session <> null && $no_session > 0){
					$remaining_per_center[$center["id"]]["no_session"] = $no_session; 
				} else 
					$remaining_per_center[$center["id"]]["no_session"] = null;
				//Get the applicants assigned to session but no assigned to any room
				$no_room = SQLQuery::create()
					->bypassSecurity()
					->select("Applicant")
					->count()
					->whereValue("Applicant", "exam_center", $center["id"])
					->whereNotNull("Applicant", "exam_session")
					->whereNull("Applicant", "exam_center_room")
					->executeSingleValue();
				if($no_room <> null && $no_room > 0){
					$remaining_per_center[$center["id"]]["no_room"] = $no_room;
				} else 
					$remaining_per_center[$center["id"]]["no_room"] = null;
			}
			if(count($remaining_per_center) > 0){
				$first = true;
				$json_remaining_per_center = "[";
				foreach ($remaining_per_center as $data){
					if(!$first) $json_remaining_per_center .= ", ";
					$first = false;
					$json_remaining_per_center .=  "{EC_id:".json_encode($data["center"]["id"]).", EC_name:";
					$json_remaining_per_center .=  json_encode($data["center"]["name"]).", ";
					$json_remaining_per_center .=  "no_session:".json_encode($data["no_session"]).", ";
					$json_remaining_per_center .=  "no_room:".json_encode($data["no_room"])."}";
				}
				$json_remaining_per_center .= "]";
			}
		}
		
		echo '{not_assigned:'.json_encode($applicants_not_assigned).",total:".json_encode($applicants_total);
		echo ', remaining_per_center:';
		if($json_remaining_per_center <> null)
			echo $json_remaining_per_center;
		else 
			echo "null";
		echo "}";
	}
	
}
?>