<?php 
require_once 'component/selection/SelectionJSON.inc';
class service_applicant_manually_assign_to_exam_entity_provider extends Service {
	
	public function get_required_rights() {return array("can_access_selection_data");}
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {
	
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
	 * Provider for the center assignment mode, after checking the user rights
	 * @return array <ul><li>0: json all free applicants</li><li>1: json all exam centers</li></ul>
	 */
	private function getJSONDataForAssigningApplicantToCenter(){
		$centers = SelectionJSON::getJSONAllExamCenters();
		return $centers;
	}
	
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

	