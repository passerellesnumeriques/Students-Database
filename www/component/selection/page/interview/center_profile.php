<?php
require_once("component/selection/page/SelectionPage.inc"); 
class page_interview_center_profile extends SelectionPage {
	
	public function getRequiredRights() { return array("see_interview_center"); }
	
	public function executeSelectionPage() {
		$id = @$_GET["id"];
		$onsaved = @$_GET["onsaved"];
		if ($id <> null && $id <= 0) $id = null;
		if ($id <> null) {
			$q = SQLQuery::create()
				->select("InterviewCenter")
				->whereValue("InterviewCenter", "id", $id)
				;
			PNApplication::$instance->geography->joinGeographicArea($q, "InterviewCenter", "geographic_area");
			require_once("component/geography/GeographyJSON.inc");
			GeographyJSON::GeographicAreaTextSQL($q);
			$q->fieldsOfTable("InterviewCenter");
			$center = $q->executeSingleRow();
		} else
			$center = null;
		if (@$_GET["readonly"] == "true") $editable = false;
		else $editable = $id == null || PNApplication::$instance->user_management->has_right("manage_interview_center");
		$db_lock = null;
		if ($editable && $id <> null) {
			$locked_by = null;
			$db_lock = $this->performRequiredLocks("InterviewCenter",$id,null,$this->component->getCampaignID(), $locked_by);
			//if db_lock = null => read only
			if($db_lock == null){
				$editable = false;
				echo "<div class='info_header'>This Interview Center is already open by ".$locked_by.": you cannot edit it</div>";
			}
		}
		
		$all_configs = include("component/selection/config.inc");
		$calendar_id = PNApplication::$instance->selection->getCalendarId();

		require_once("component/selection/SelectionApplicantJSON.inc");
		require_once("component/calendar/CalendarJSON.inc");
		require_once("component/people/PeopleJSON.inc");
		if ($id <> null) {
			$q = SQLQuery::create()->select("Applicant")->whereValue("Applicant","interview_center", $id);
			SelectionApplicantJSON::ApplicantSQL($q);
			$applicants = $q->execute();
			
			$sessions = SQLQuery::create()->select("InterviewSession")->whereValue("InterviewSession", "interview_center", $id)->execute();
			$sessions_events_ids = array();
			foreach ($sessions as $s) array_push($sessions_events_ids, $s["event"]);
			if (count($sessions) > 0) {
				$sessions_events = CalendarJSON::getEventsFromDB($sessions_events_ids, PNApplication::$instance->selection->getCalendarId());
				for ($i = 0; $i < count($sessions); $i++)
					foreach ($sessions_events as $ev)
						if ($sessions[$i]["event"] == $ev["id"]) { $sessions[$i]["event"] = $ev; break; }
				$peoples_ids = array();
				foreach ($sessions_events as $ev)
					foreach ($ev["attendees"] as $a)
						if ($a["people"] > 0 && !in_array($a["people"], $peoples_ids))
							array_push($peoples_ids, $a["people"]);
				if (count($peoples_ids) > 0) {
					$peoples = PNApplication::$instance->people->getPeoples($peoples_ids, true, false, true, true);
					$can_do = SQLQuery::create()->select("StaffStatus")->whereIn("StaffStatus","people",$peoples_ids)->field("people")->field("interview")->execute();
				} else {
					$peoples = array();
					$can_do = array();
				}
			} else {
				$peoples = array();
				$can_do = array();
			}
			
			$linked_exam_center_id = SQLQuery::create()->select("InterviewCenterExamCenter")->whereValue("InterviewCenterExamCenter", "interview_center", $id)->field("exam_center")->executeSingleField();
		} else {
			$applicants = array();
			$sessions = array();
			$peoples = array();
			$can_do = array();
			$linked_exam_center_id = array();
		}
		$q = SQLQuery::create()->select("ExamCenter");
		$all_exam_centers = $q->execute();
		
		$this->requireJavascript("section.js");
		theme::css($this, "section.css");
		$this->requireJavascript("linked_exam_centers.js");
		$this->requireJavascript("interview_sessions.js");
		$this->requireJavascript("grid.js");
		$this->requireJavascript("custom_data_grid.js");
		$this->requireJavascript("people_data_grid.js");
		$this->requireJavascript("applicant_data_grid.js");
		$this->requireJavascript("who.js");
		$this->requireJavascript("calendar_objects.js");
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_integer.js");
		?>
		<table><tr><td valign=top>
		<div id='section_center' title='Interview Center Information' collapsable='true' style='margin:10px;'>
			<div>
				<div style='display:inline-block;margin:10px;vertical-align:top;'>
				<?php if ($this->component->getOneConfigAttributeValue("give_name_to_interview_center")) {
					$this->requireJavascript("center_name.js");
					?>
					<div id='center_name_container'></div>
					<script type='text/javascript'>
					window.center_name = new center_name(
						'center_name_container', 
						<?php echo $center <> null ? json_encode($center["name"]) : "null";?>,
						<?php echo json_encode($editable);?>,
						"Interview Center name"
					); 
					</script>
				<?php } ?>
					<div id='links_container'></div>
					<script type='text/javascript'>
					window.linked_exam_centers = new linked_exam_centers(
						'links_container',
						<?php echo json_encode($all_exam_centers);?>,
						<?php echo json_encode($linked_exam_center_id);?>,
						<?php echo $editable ? "true" : "false";?>
					);
					</script>
				<?php
				require_once("component/selection/page/common_centers/location_and_partners.inc");
				locationAndPartners($this, $id, "InterviewCenter", $center <> null ? GeographyJSON::GeographicAreaText($center) : "null", $editable, false); 
				?>
				</div>
			</div>
		</div>
		</td><td valign=top>
			<div id='section_planning' title='Applicants and Interviews Planning' collapsable='true' style='margin:10px;'>
				<div id='sessions_container'>
				</div>
				<script type='text/javascript'>
				window.center_sessions = new interview_sessions(
					'sessions_container',
					[<?php
					$first = true;
					foreach ($sessions as $session) {
						if ($first) $first = false; else echo ",";
						echo "{";
						echo "event:".CalendarJSON::JSON($session["event"]);
						echo ",every_minutes:".$session["every_minutes"];
						echo ",parallel_interviews:".$session["parallel_interviews"];
						echo "}";
					} 
					?>],
					<?php echo SelectionApplicantJSON::ApplicantsJSON($applicants); ?>,
					window.linked_exam_centers,
					<?php echo $calendar_id;?>,
					[<?php 
					$first = true;
					foreach ($peoples as $p) {
						if ($first) $first = false; else echo ",";
						echo "{people:".PeopleJSON::People($p);
						echo ",can_do:";
						$val = false;
						foreach ($can_do as $c) if ($c["people"] == $p["people_id"]) { $val = $c["interview"]; break; }
						echo json_encode($val);
						echo "}";
					}
					?>],
					<?php echo $editable ? "true" : "false";?>
				);
				</script>
			</div>
		</td></tr></table>
		<script type='text/javascript'>
		var center_popup = window.parent.get_popup_window_from_frame(window);
		var center_id = <?php echo $id <> null ? $id : -1;?>;
		var section_center = sectionFromHTML('section_center');
		var section_planning = sectionFromHTML('section_planning');
		if (center_id == -1) window.pnapplication.dataUnsaved("NewInterviewCenter");

		function save_center() {
			if (window.center_location.geographic_area_text == null) {
				error_dialog("You must at set a location before saving");
				return;
			}
			var data = {};
			data.center = {id:center_id};
			if (window.pnapplication.isDataUnsaved("SelectionCenterCustomName")) {
				// custom name changed
				data.center.name = window.center_name.getName();
			}
			if (center_id == -1 || window.pnapplication.isDataUnsaved("SelectionLocationAndPartners")) {
				// location and partners have been modified
				data.center.geographic_area = window.center_location.geographic_area_text.id;
				data.partners = [];
				for (var i = 0; i < window.center_location.partners.length; ++i) {
					// add partners, with only organization id
					var p = objectCopy(window.center_location.partners[i]);
					delete p.center_id;
					p.organization = p.organization.id;
					data.partners.push(p);
				}
			}
			if (window.pnapplication.isDataUnsaved("InterviewCenterExamCenter")) {
				// linked exam centers changed
				data.linked_exam_centers = window.linked_exam_centers.linked_ids;
			}
			if (window.pnapplication.isDataUnsaved("InterviewSessions") || window.pnapplication.isDataUnsaved('who')) {
				// sessions changed
				data.sessions = window.center_sessions.sessions;
			}
			if (window.pnapplication.isDataUnsaved("InterviewCenterApplicants") ||
				window.pnapplication.isDataUnsaved("InterviewSessions") ||
				window.pnapplication.isDataUnsaved("InterviewCenterExamCenter")) {
				// need to save applicants
				var list = [];
				for (var i = 0; i < window.center_sessions.applicants.length; ++i) {
					var app = window.center_sessions.applicants[i];
					var a = {
						people_id: app.people.id,
						interview_session_id: app.interview_session_id
					};
					list.push(a);
				}
				data.applicants = list;
			}
			center_popup.freeze("Saving...");
			service.json("selection","interview/save_center",data,function(res) {
				if (!res) {
					center_popup.unfreeze();
					return;
				}
				center_id = res.id;
				for (var j = 0; j < window.center_sessions.applicants.length; ++j)
					window.center_sessions.applicants[j].interview_center_id = center_id;
				if (res.sessions_ids)
					for (var i = 0; i < res.sessions_ids.length; ++i) {
						for (var j = 0; j < window.center_sessions.sessions.length; ++j)
							if (window.center_sessions.sessions[j].event.id == res.sessions_ids[i].given_id) {
								window.center_sessions.sessions[j].event.id = res.sessions_ids[i].new_id;
								break;
							}
						for (var j = 0; j < window.center_sessions.applicants.length; ++j)
							if (window.center_sessions.applicants[j].interview_session_id == res.sessions_ids[i].given_id)
								window.center_sessions.applicants[j].interview_session_id = res.sessions_ids[i].new_id;
					}
				window.pnapplication.cancelDataUnsaved();
				<?php if ($onsaved <> null) echo "window.frameElement.".$onsaved."();"?>
				center_popup.unfreeze();
			});
		}

		center_popup.removeButtons();
		<?php if ($editable && $id <> null) {?>
		center_popup.addIconTextButton(theme.icons_16.remove, "Remove this interview center", "remove", function() {
			confirm_dialog("Are you sure you want to remove this interview center ?",function(res){
				if(res){
					center_popup.freeze();
					service.json("selection","interview/remove_center",{id:<?php echo $id;?>},function(r){
						if(!r){
							center_popup.unfreeze();
							error_dialog("An error occured, this center was not removed properly");
							return;
						}
						window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "Interview center succesfully removed!", [{action:"close"}], 5000));
						window.pnapplication.cancelDataUnsaved();
						<?php if ($onsaved <> null) echo "window.frameElement.".$onsaved."();"?>
						center_popup.close();
					});
				}
			});
		});
		<?php } ?>
		<?php if ($editable || $id == null) {?>
		center_popup.addFrameSaveButton(save_center);
		<?php }?>
		center_popup.addCloseButton();

		<?php 
		if (isset($_POST["input"])) {
			$input = json_decode($_POST["input"], true);
			if (isset($input["host_exam_center"])) {
				echo "window.linked_exam_centers.linkExamCenter(".$input["host_exam_center"].");\n";
				echo "window.linked_exam_centers.setHostFromExamCenter(".$input["host_exam_center"].");\n";
			}
			if (isset($input["others_exam_centers"])) {
				foreach ($input["others_exam_centers"] as $ec_id)
					echo "window.linked_exam_centers.linkExamCenter(".$ec_id.");\n";
			}
		}
		?>
		</script>
		<?php 
	}
	
}
?>