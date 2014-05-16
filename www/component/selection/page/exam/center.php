<?php
require_once("component/selection/page/SelectionPage.inc"); 
class page_exam_center extends SelectionPage {
	
	public function getRequiredRights() { return array("see_exam_center_detail"); }
	
	public function executeSelectionPage() {
		$id = @$_GET["id"];
		$onsaved = @$_GET["onsaved"];
		if ($id <> null && $id <= 0) $id = null;
		if ($id <> null) {
			$q = SQLQuery::create()
				->select("ExamCenter")
				->whereValue("ExamCenter", "id", $id)
				;
			PNApplication::$instance->geography->joinGeographicArea($q, "InformationSession", "geographic_area");
			require_once("component/geography/GeographyJSON.inc");
			GeographyJSON::GeographicAreaTextSQL($q);
			$q->fieldsOfTable("ExamCenter");
			$center = $q->executeSingleRow();
		} else
			$center = null;
		if (@$_GET["readonly"] == "true") $editable = false;
		else $editable = $id == null || PNApplication::$instance->user_management->has_right("manage_exam_center");
		$db_lock = null;
		if ($editable && $id <> null) {
			$locked_by = null;
			$db_lock = $this->performRequiredLocks("ExamCenter",$id,null,$this->component->getCampaignID(), $locked_by);
			//if db_lock = null => read only
			if($db_lock == null){
				$editable = false;
				echo "<div class='info_header'>This Exam Center is already open by ".$locked_by.": you cannot edit it</div>";
			}
			//Get rights from steps
			$from_steps = PNApplication::$instance->selection->getRestrictedRightsFromStepsAndUserManagement("exam_center", "manage_exam_center", "manage_exam_center", "manage_exam_center");
			if($from_steps[1])
				PNApplication::warning($from_steps[2]);
			$can_add = $from_steps[0]["add"];
			$can_remove = $from_steps[0]["remove"];
			$can_edit = $from_steps[0]["edit"];
		}
		
		$all_configs = include("component/selection/config.inc");
		$calendar_id = PNApplication::$instance->selection->getCalendarId();

		require_once("component/selection/SelectionExamJSON.inc");
		require_once("component/selection/SelectionApplicantJSON.inc");
		require_once("component/selection/SelectionInformationSessionJSON.inc");
		require_once("component/calendar/CalendarJSON.inc");
		require_once("component/people/PeopleJSON.inc");
		if ($id <> null) {
			$q = SQLQuery::create()->select("Applicant")->whereValue("Application","exam_center", $id);
			SelectionApplicantJSON::ApplicantSQL($q);
			$applicants = $q->execute();
			
			$q = SQLQuery::create()->select("ExamCenterRoom")->whereValue("ExamCenterRoom", "exam_center", $id);
			SelectionExamJSON::ExamCenterRoomSQL($q);
			$rooms = $q->execute();
			
			$q = SQLQuery::create()->select("ExamSession")->whereValue("ExamSession", "exam_center", $id);
			PNApplication::$instance->calendar->joinEvent($q, "ExamSession", "event");
			CalendarJSON::CalendarEventSQL($q);
			$sessions = $q->execute(); 
			
			$linked_is_id = SQLQuery::create()->select("ExamCenterInformationSession")->whereValue("ExamCenterInformationSession", "exam_center", $id)->field("information_session")->executeSingleField();
		} else {
			$applicants = array();
			$rooms = array();
			$sessions = array();
			$linked_is_id = array();
		}
		$q = SQLQuery::create()->select("InformationSession");
		SelectionInformationSessionJSON::InformationSessionSQL($q);
		$all_is = $q->execute();
		
		$this->requireJavascript("section.js");
		theme::css($this, "section.css");
		$this->requireJavascript("exam_center_objects.js");
		$this->requireJavascript("exam_center_rooms.js");
		$this->requireJavascript("exam_center_sessions.js");
		$this->requireJavascript("exam_center_IS.js");
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_text.js");
		$this->requireJavascript("field_integer.js");
		?>
		<div>
		<div id='section_center' title='Exam Center Information' collapsable='true' style='margin:10px;'>
			<div>
				<div style='display:inline-block;margin:10px;vertical-align:top;'>
				<?php if ($this->component->getOneConfigAttributeValue("give_name_to_exam_center")) {
					$this->requireJavascript("center_name.js");
					?>
					<div id='center_name_container'></div>
					<script type='text/javascript'>
					window.center_name = new center_name(
						'center_name_container', 
						<?php echo $center <> null ? json_encode($center["name"]) : "null";?>,
						<?php echo json_encode($editable);?>,
						"Exam Center name"
					); 
					</script>
				<?php } ?>
					<div id='rooms_container'></div>
					<script type='text/javascript'>
					window.center_rooms = new exam_center_rooms(
						'rooms_container',
						<?php echo SelectionExamJSON::ExamCenterRooms($rooms);?>
					);
					</script>
					<div id='IS_container'></div>
					<script type='text/javascript'>
					window.linked_is = new exam_center_IS(
						'IS_container',
						<?php echo SelectionInformationSessionJSON::InformationSessionsJSON($all_is);?>,
						<?php echo json_encode($linked_is_id);?>
					);
					</script>
				</div>
				<div style='display:inline-block;margin:10px;vertical-align:top;' id='location_and_partners'>
				<?php
				require_once("component/selection/page/common_centers/location_and_partners.inc");
				locationAndPartners($this, $id, "ExamCenter", $center <> null ? GeographyJSON::GeographicAreaText($center) : "null", $editable, true); 
				?>
				</div>
			</div>
		</div>
		<div id='section_planning' title='Exam Sessions Planning' collapsable='true' style='margin:10px;'>
			<div id='exam_sessions_container'>
			</div>
			<script type='text/javascript'>
			window.center_sessions = new exam_center_sessions(
				'exam_sessions_container',
				<?php echo CalendarJSON::CalendarEvents($sessions); ?>,
				<?php echo SelectionApplicantJSON::ApplicantsJSON($applicants); ?>,
				window.center_rooms,
				window.linked_is,
				<?php echo intval($this->component->getOneConfigAttributeValue("default_duration_exam_session"))*60;?>,
				<?php echo $calendar_id;?>
			);
			</script>
		</div>
		</div>
		<script type='text/javascript'>
		var center_popup = window.parent.get_popup_window_from_frame(window);
		var center_id = <?php echo $id <> null ? $id : -1;?>;
		var section_center = sectionFromHTML('section_center');
		var section_planning = sectionFromHTML('section_planning');

		function save_center() {
			if (window.center_location.geographic_area_text == null) {
				error_dialog("You must at set a location before saving");
				return;
			}
			// TODO
			<?php if ($onsaved <> null) echo "window.frameElement.".$onsaved."();"?>
		}

		center_popup.removeAllButtons();
		<?php if ($editable && $id <> null && $can_remove) {?>
		center_popup.addIconTextButton(theme.icons_16.remove, "Remove this exam center", "remove", function() {
			confirm_dialog("Are you sure you want to remove this exam center ?",function(res){
				if(res){
					center_popup.freeze();
					service.json("selection","exam/remove_center",{id:<?php echo $id;?>},function(r){
						if(!r){
							center_popup.unfreeze();
							error_dialog("An error occured, this center was not removed properly");
							return;
						}
						window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "Exam center succesfully removed!", [{action:"close"}], 5000));
						window.pnapplication.cancelDataUnsaved();
						<?php if ($onsaved <> null) echo "window.frameElement.".$onsaved."();"?>
						center_popup.close();
					});
				}
			});
		});
		<?php } ?>
		center_popup.addFrameSaveButton(save_center);
		center_popup.addCloseButton();
		</script>
		<?php 
	}
	
}
?>