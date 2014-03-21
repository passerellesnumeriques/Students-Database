<?php 
class service_applicant_get_assigned_to_sessions_for_center extends Service {
	
	public function get_required_rights() {return array("can_access_selection_data");}
	
	public function documentation() {
		echo "Get the applicants to all the sessions planned into an exam center";
	}
	public function input_documentation() {
		?>
		<ul>
		  <li><code>EC_id</code> the exam center ID</li>
		  <li><code>count</code> (optional) if true, only get the number of applicants assigned per session</li>
		</ul>

		<?php
	}
	public function output_documentation() {
		?>
		<code>data</code> containing the retrieved data. Can be:
		<ul>
		  <li>NULL if no session is planned into the exam center</li>
		  <li>array containing objects made of two attributes:
		  	<ul><li><code>session</code> the session event ID</li><li><code>count</code> the number of applicants assigned if count input == true, else</li><li><code>applicants</code> array containing all the applicants objects of the applicants assigned to this session</li></ul>
		  </li>
		</ul>

		<?php
	}
	
	public function execute(&$component, $input) {
		if(isset($input["EC_id"])){
			echo "{data:";
			if(isset($input["count"]) && $input["count"] == true){
				$data = $component->getApplicantsAssignedToSessionsInEC($input["EC_id"],true);
				if ($data == null)
					echo "null";
				else{
					echo "[";
					$first = true;
					foreach ($data as $session => $count){
						if(!$first) echo ', ';
						$first = false;
						echo "{session:".json_encode($session);
						echo ", count:".json_encode($count);
						echo "}";
					}
					echo "]";
				}				
			} else {
				require_once 'component/selection/SelectionJSON.inc';
				$data = $component->getApplicantsAssignedToSessionsInEC($input["EC_id"]);
				if ($data == null)
					echo "null";
				else{
					echo "[";
					$first = true;
					foreach ($data as $session => $rows){
						if(!$first) echo ', ';
						$first = false;
						echo "{session:".json_encode($session);
						echo ", applicants:[";
						$first_applicant = true;
						foreach ($rows as $row){
							if(!$first_applicant) echo ", ";
							$first_applicant = false;
							echo SelectionJSON::Applicant(null, $row);
						}
						echo "]";
					}
					echo "]";
				}
			}
			echo "}";
				
		} else {
			echo "false";
		}
	}
	
}
?>