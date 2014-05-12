<?php 
require_once 'component/selection/SelectionJSON.inc';
class service_applicant_manually_assign_to_exam_entity_provider extends Service {
	
	public function getRequiredRights() {return array("can_access_selection_data");}
	
	public function documentation() {
		echo "Get the data about the targets elements of the manually assign applicants to exam entity page";
	}
	public function inputDocumentation() {
		?>
		<ul>
		  <li><code>mode</code> string mode of the manually_assign_to_exam_entity page: can be <ul><li><code>center</code> if the page is handling assignment of applicants to exam center</li><li><code>session</code> if the page is handling the assignment of applicants to exam session and rooms for a given center</li></ul></li>
		  <li><code>EC_id</code> NULL | number required only in the case of mode == "session": in that case must be the exam center ID of the sessions handled</li>
		</ul>

		<?php
	}
	public function outputDocumentation() {
		?>
		Object with one attribute: <code>targets</code>
		Its content depends on mode
		<ul>
			<li>If mode == "center", contains an array of ExamCenter objects for all the exam center</li>
			<li>else, contains an array of objects (one per session planned in this center) with two attributes:<ul><li><code>session</code> ExamSession object</li><li><code>rooms</code> array containing objects (one per room) with three attributes:<ul><li><code>id</code> exam center room ID</li><li><code>name</code> exam center room name</li><li><code>remaining</code> number of remaining slots in this room for the matching session</li></ul></li></ul></li>
		</ul>
		<?php
	}
	
	public function execute(&$component, $input) {
		$mode = $input["mode"];
		$EC_id = @$input["EC_id"];
		echo "{targets:";
		$data = null;
		if($mode == "session" && $EC_id <> null){
			$data = $this->getJSONDataForAssigningApplicantToSession($EC_id);
		} else if($mode == "center"){
			$data = $this->getJSONDataForAssigningApplicantToCenter();
		}
		echo $data;
		echo "}";
	}
	
	/**
	 * Provider called on center mode
	 * @return string JSON array of all the exam centers
	 */
	private function getJSONDataForAssigningApplicantToCenter(){
		$centers = SelectionJSON::getJSONAllExamCenters();
		return $centers;
	}
	
	/**
	 * Provider called on the session mode
	 * @param number $EC_id exam center ID
	 * @return string JSON array (cf service doc for detail)
	 */
	private function getJSONDataForAssigningApplicantToSession($EC_id){
		//Get the remaining slots for the sessions set in this center
		$sessions_ids = SQLQuery::create()
			->select("ExamSession")
			->field("ExamSession","event")
			->whereValue("ExamSession", "exam_center", $EC_id)
			->executeSingleField();
		if($sessions_ids <> NULL){
			$data = "[";
			$first = true;
			foreach ($sessions_ids as $session_id){
				if(!$first) $data .= ", ";
				$first = false;
				$data .= SelectionJSON::ExamCenterRoomsWithRemainingSlotsForSession($session_id);
			}
			$data .= "]";
			return $data;
		} else
			return "[]";
	}
}
?>

	