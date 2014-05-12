<?php 
class service_applicant_get_assigned_to_sessions_for_center extends Service {
	
	public function getRequiredRights() {return array("can_access_selection_data");}
	
	public function documentation() {
		echo "Get the applicants to all the sessions planned into an exam center";
	}
	public function inputDocumentation() {
		?>
		<ul>
		  <li><code>EC_id</code> the exam center ID</li>
		  <li><code>count</code> (optional) if true, only get the number of applicants assigned per session</li>
		  <li><code>session_detail</code> (optional) if true, get an ExamSession object instead of only the exam session event ID</li>
		</ul>

		<?php
	}
	public function outputDocumentation() {
		?>
		<code>data</code> containing the retrieved data. Can be:
		<ul>
		  <li>NULL if no session is planned into the exam center</li>
		  <li>array containing objects made of two attributes:
		  	<ul><li><code>session</code> the session event ID | ExamSession object if the parameter session_detail was true</li><li><code>count</code> the number of applicants assigned if count input == true, else</li><li><code>applicants</code> array containing all the applicants objects of the applicants assigned to this session</li></ul>
		  </li>
		</ul>

		<?php
	}
	
	public function execute(&$component, $input) {
		if(isset($input["EC_id"])){
			echo "{data:";
			$count = (isset($input["count"]) && $input["count"] == true) ? true : false;
			$session_detail = (isset($input["session_detail"]) && $input["session_detail"] == true) ? true : false;
			$data = $component->getApplicantsAssignedToSessionsInEC($input["EC_id"],$count,$session_detail);
			require_once 'component/selection/SelectionJSON.inc';				
			if ($data == null)
				echo "null";
			else{
				echo "[";
				$first = true;
				foreach ($data as $session => $rows){
					if(!$first) echo ', ';
					$first = false;
					if($session_detail)
						echo "{session:".SelectionJSON::ExamSessionFromID($session);
					else
						echo "{session:".json_encode($session);
					if($count)
						echo ", count:".json_encode($rows);
					else {
						echo ", applicants:[";
						$first_applicant = true;
						foreach ($rows as $row){
							if(!$first_applicant) echo ", ";
							$first_applicant = false;
							echo SelectionJSON::Applicant(null, $row);
						}
						echo "]";
					}
					echo "}";
				}
				echo "]";
			}				
			echo "}";
				
		} else {
			echo "false";
		}
	}
	
}
?>