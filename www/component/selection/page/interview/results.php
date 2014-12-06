<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_interview_results extends SelectionPage {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function executeSelectionPage() {
		$this->requireJavascript("section.js");
		$this->requireJavascript("data_list.js");
		theme::css($this, "grid.css");
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
	<div class='page_title' style='flex:none;'>
		<?php if (PNApplication::$instance->user_management->has_right("edit_interview_results")) { ?>
		<button class='action red' style='float:right' onclick='resetAll();'>
			Reset all results
		</button>
		<?php } ?>
		<img src='<?php echo theme::make_icon("/static/selection/interview/interview_32.png", theme::$icons_16["ok"]);?>'/>
		Interview Results
	</div>
	<div style='flex:1 1 auto;overflow:auto;display:flex;flex-direction:row;'>
		<div style="flex:none;width:50%;">
			<div style="padding:5px;padding-right:0px;">
				<div id="sessions_list" title='Exam sessions' icon="/static/calendar/calendar_16.png" css="soft">
					<table class="grid" id="table_sessions" style="width: 100%">
						<thead>
							<tr>
							    <th>Interview Session</th>
							    <th>Applicants</th>
							    <th>Status</th>					      
							</tr>
						</thead>
						<tbody>
						<?php 
						$q = SQLQuery::create()->select("InterviewSession");
						PNApplication::$instance->calendar->joinCalendarEvent($q, "InterviewSession", "event");
						$q->orderBy("CalendarEvent", "start");
						$q->field("InterviewSession", "interview_center", "center_id");
						$q->field("InterviewSession", "event", "id");
						$q->field("CalendarEvent", "start", "start");
						$q->field("CalendarEvent", "end", "end");
						$q->join("InterviewSession","Applicant",array("event"=>"interview_session"));
						$q->groupBy("InterviewSession", "event");
						$q->expression("SUM(IF(people IS NOT NULL,1,0))", "nb_applicants");
						$q->expression("SUM(IF(interview_attendance IS NULL,1,0))", "no_attendance");
						$q->expression("SUM(IF(interview_attendance=0,1,0))", "absents");
						$q->expression("SUM(IF(interview_attendance=1,1,0))", "presents");
						$q->expression("SUM(interview_passer)", "passers");
						$sessions = $q->execute();
						$centers_ids = array();
						foreach ($sessions as $session) if (!in_array($session["center_id"], $centers_ids)) array_push($centers_ids, $session["center_id"]);
						$centers = SQLQuery::create()
							->select("InterviewCenter")
							->whereIn("InterviewCenter","id",$centers_ids)
							->execute();
						while (count($sessions) > 0) {
							$center_id = $sessions[0]["center_id"];
							foreach ($centers as $c) if ($c["id"] == $center_id) { $center = $c; break; }
							$list = array($sessions[0]);
							array_splice($sessions,0,1);
							for ($i = 0; $i < count($sessions); $i++) {
								if ($sessions[$i]["center_id"] <> $center_id) continue;
								array_push($list, $sessions[$i]);
								array_splice($sessions, $i, 1);
								$i--;
							}
							echo "<tr><th colspan=3>".toHTML($center["name"])."</th></tr>";
							foreach ($list as $session) {
								echo "<tr onclick=\"selectSession(this,".$session["id"].")\" style='cursor:pointer;' onmouseover=\"this.style.backgroundColor='#FFF0D0';\" onmouseout=\"this.style.backgroundColor='';\">";
								echo "<td>";
								echo date("d M Y",$session['start'])." (".date("h:ia",$session['start'])." to ".date("h:ia",$session['end']).")";
								echo "</td>";
								echo "<td align=center>".$session["nb_applicants"]."</td>";
								echo "<td align=center>";
								if ($session["no_attendance"] == $session["nb_applicants"])
									echo "<span style='color:red'>No result yet</span>";
								else {
									if ($session["no_attendance"] == 0)
										echo "<span style='color:green'>All attendance set</span>, ";
									if ($session["absents"] > 0)
										echo $session["absents"]." were absent, ";
									echo $session["passers"]."/".$session["presents"]." passed";
								}
								echo "</td>";
								echo "</tr>";
							}
						}
						?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<!--List of applicants-->
		<div style="flex:none;width:50%;align-self:stretch;display:flex;flex-direction:column;">
			<div style="padding:5px;padding-right:0px;display:flex;flex-direction:column;flex:1 1 auto">		
				<div id="session_applicants" title='Applicants for selected session' icon="/static/selection/applicant/applicants_16.png" css="soft" fill_height='true' style='flex:1 1 auto;'>
					<div id="session_applicants_list" style="display:none"></div>
				</div>
			</div>
		</div>
	</div>
	<?php if (PNApplication::$instance->user_management->has_right("edit_interview_results")) { ?>
	<div class="page_footer" style="flex:none">
		<button id="edit_results_button" class="action" disabled="disabled" onclick="editResults();">Edit Results</button>
		<button id="remove_results_button" class="action red" disabled="disabled" onclick="resetSession();">Reset results</button>
	</div>
	<?php } ?>
</div>
<script type='text/javascript'>

new data_list(
	"session_applicants_list",
	"Applicant", <?php echo PNApplication::$instance->selection->getCampaignId();?>,
	[
		"Selection.ID",
		"Personal Information.First Name",
		"Personal Information.Last Name",
		"Personal Information.Gender",
		"Personal Information.Age",
		"Selection.Interview Attendance"
	],
	[{category:"Selection",name:"Interview Session",force:true,data:{values:[-1]}}],
	-1,
	"Personal Information.Last Name", true,
	function(list) {
		window.dl = list;
		list.grid.makeScrollable();
	}
);

var selected_row = null;
var selected_session_id = null;

function selectSession(row, session_id) {
	if (selected_row) removeClassName(selected_row, "selected");
	selected_row = row;
	selected_session_id = session_id;
	addClassName(row, "selected");
	window.dl.resetFilters(true, [{category:"Selection",name:"Interview Session",force:true,data:{values:[session_id]}}]);
	window.dl.reloadData();
	document.getElementById('session_applicants_list').style.display = "";
	<?php if (PNApplication::$instance->user_management->has_right("edit_interview_results")) { ?>
	document.getElementById('edit_results_button').disabled = "";
	document.getElementById('remove_results_button').disabled = "";
	<?php } ?>
}

function editResults() {
	window.top.popup_frame(
		"/static/transcripts/grades_16.png",
		"Interview Session Results",
		"/dynamic/selection/page/interview/edit_results?session="+selected_session_id,
		null,
		95, 95,
		function(frame, pop) {
			pop.onclose = function() { location.reload(); }
		}
	);
}

function resetSession() {
	confirm_dialog("Are you sure you want to remove all interview results and attendance for this interview session ?",function(yes) {
		if (!yes) return;
		var locker = lock_screen(null,"Removing results of applicants...");
		service.json("selection","interview/reset_results",{session:selected_session_id},function(res) {
			unlock_screen(locker);
			location.reload();
		});
	});
}

function resetAll() {
	confirm_dialog("Are you sure you want to remove all interview results and attendance for all applicants ?",function(yes) {
		if (!yes) return;
		var locker = lock_screen(null,"Removing results of applicants...");
		service.json("selection","interview/reset_results",{session:null},function(res) {
			unlock_screen(locker);
			location.reload();
		});
	});
}

sectionFromHTML('sessions_list');
sectionFromHTML('session_applicants');
</script>
<?php 
	}
	
}
?>