<?php
require_once("/../selection_page.inc");
require_once("component/selection/SelectionJSON.inc");
class page_applicant_manually_assign_to_exam_entity extends selection_page {
	
	public function get_required_rights() {return array("manage_applicant");}
	
	public function execute_selection_page() {		
		$lock = @$_GET["lock"];//Get the lock if already exist
		$mode = $_GET["mode"];
		$applicants = @$_GET["a"];//people id of the selected applicants
		$target = @$_GET["target"];//id of the selected target
		$session_id = @$_GET["session"];
		$EC_id = @$_GET["center"];
		//Perform the required actions
		if(isset($applicants) && isset($target)){
			if($mode == "center"){
				PNApplication::$instance->selection->assignApplicantsToEC($applicants, $target);
			}
			else if ($mode == "session")
				PNApplication::$instance->selection->assignApplicantsToSession($applicants, $target);
			else if ($mode == "room")
				PNApplication::$instance->selection->assignApplicantsToRoomForSession($applicants, $session_id, $target);
		}
	
		//generate the page
		$this->require_javascript("vertical_layout.js");
		if($mode == "session"){
			//Add the field time for the sessions names
			$this->require_javascript("typed_field.js");
			$this->require_javascript("field_time.js");
		}
		$this->onload("new vertical_layout('assign_container');");
		?>
		<div id = "assign_container" style = "width:100%; height:100%; overflow:hidden;">
			<div id = "sections_container" layout = "fill"></div>
		</div>
		<?php
		//Lock the Applicant table
		require_once("component/data_model/DataBaseLock.inc");
		$sm = PNApplication::$instance->selection->getCampaignId();
		if(!isset($lock))
			$lock = $this->performRequiredLocks("Applicant",null,null,$sm);
		else //script is handled by the page#performRequiredLocks method
			DataBaseLock::generateScript($lock);
		
		if($lock == null){
			?>
			<script type = 'text/javascript'>
			error_dialog("Database is busy so the operation cannot be well processed. Please try again later.");
			</script>
			<?php
			return;
		}
		//Get the data from the matching provider
		if($mode == "center"){
			$data = $this->getJSONDataForAssigningApplicantToCenter();
		} else if($mode == "session")
			$data = $this->getJSONDataForAssigningApplicantToSession($EC_id);
		else if ($mode == "room")
			$data = $this->getJSONDataForAssigningApplicantToRoom($session_id);
		?>
		<script type='text/javascript'>
			require("applicant_manually_assign_to_entity.js",function(){
				var targets = <?php echo $data[1];?>;
				var mode = <?php echo json_encode($mode);?>;
				if(mode == "session"){
					//Process the targets to comply with applicant_manually_assign_to_entity.js requirements (target objects must have name & id attributes)
					for(var i = 0; i < targets.length; i++){
						targets[i].name = getExamSessionNameFromEvent(targets[i].event);
						targets[i].id = targets[i].event.id;
					}
				}
				new applicant_manually_assign_to_entity(
						"sections_container",
						<?php echo $data[0];?>,
						targets,
						mode,
						<?php echo $lock;?>,
						<?php echo json_encode($EC_id);?>,
						<?php echo json_encode($session_id);
							if(isset($data[2])){
								echo ", ".$data[2];
							}
						?>
						);
			});
		
		</script>
		<?php
	}
	
	/**
	 * Provider for the center assignment mode, after checking the user rights
	 * @return array <ul><li>0: json all free applicants</li><li>1: json all exam centers</li></ul>
	 */
	private function getJSONDataForAssigningApplicantToCenter(){
		$all_free_applicants = PNApplication::$instance->selection->getApplicantsNotAssignedToAnyEC();
		$json_all_free_applicants = $this->getJSONArrayApplicantsFromRows($all_free_applicants);
		$centers = SelectionJSON::getJSONAllExamCenters();
		return array($json_all_free_applicants, $centers);
	}
	
	private function getJSONDataForAssigningApplicantToSession($EC_id){
		$applicants = PNApplication::$instance->selection->getApplicantsAssignedToCenterEntity($EC_id,null,null,null,"exam_session");
		$json_applicants = $this->getJSONArrayApplicantsFromRows($applicants);
		$sessions = SelectionJSON::ExamSessionsFromExamCenterID($EC_id);
		//Get the remaining slots for the sessions set in this center
		$sessions_ids = SQLQuery::create()
			->bypassSecurity()
			->select("ExamSession")
			->field("ExamSession","event")
			->whereValue("ExamSession", "exam_center", $EC_id)
			->executeSingleField();
		if($sessions_ids <> NULL){
			$remaining_per_session = "[";
			$first = true;			
			foreach ($sessions_ids as $session_id){
				if(!$first) $remaining_per_session .= ", ";
				$first = false;
				$remaining_per_session .= "{id:".json_encode($session_id).", additional:".json_encode(PNApplication::$instance->selection->getRemainingSlotsForExamSession($session_id))."}";
			}
			$remaining_per_session .= "]";
			return array($json_applicants,$sessions,$remaining_per_session);
		} else 		
			return array($json_applicants,$sessions);	
	}
	
	private function getJSONDataForAssigningApplicantToRoom($session_id){
		$applicants = PNApplication::$instance->selection->getApplicantsAssignedToCenterEntity(null,$session_id,null,null,"exam_center_room");
		$json_applicants = $this->getJSONArrayApplicantsFromRows($applicants);
		//Get the rooms
		$EC_id = SQLQuery::create()->select("ExamSession")->field("ExamSession","exam_center")->whereValue("ExamSession", "event", $session_id)->executeSingleValue();
		$json_rooms = SelectionJSON::ExamCenterRoomsFromCenterID($EC_id);
		//Get the remaining slots for the rooms during this session
		$rooms = SQLQuery::create()
			->select("ExamCenterRoom")
			->whereValue("ExamCenterRoom", "exam_center", $EC_id);
		SelectionJSON::ExamCenterRoomSQL($rooms);
		$rooms = $rooms->execute();
		if($rooms <> null){
			$remaining_per_room = "[";
			$first = true;
			foreach ($rooms as $room){
				if(!$first) $remaining_per_room .= ", ";
				$first = false;
				$remaining_per_room .= "{id:".json_encode($room["id"]).", additional:".json_encode(PNApplication::$instance->selection->getRemainingSlotsForExamRoomDuringSession($room["id"],$session_id,$room["capacity"]))."}";
			}
			$remaining_per_room .= "]";
			return array($json_applicants, $json_rooms, $remaining_per_room);
		} else
			return array($json_applicants, $json_rooms);	
	}
	
	/**
	 * Convert applicants rows into JSON array
	 * @param NULL|array $rows
	 * @return string JSON array of applicants objects
	 */
	private function getJSONArrayApplicantsFromRows($rows){
		$json = "[";
		if($rows <> null){
			$first = true;
			foreach ($rows as $applicant){
				if(!$first) $json .= ", ";
				$first = false;
				$json .= SelectionJSON::Applicant(null, $applicant);
			}
		}
		$json .= "]";
		return $json;
	}
	
}
?>