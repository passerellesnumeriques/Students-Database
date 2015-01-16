<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_interview_edit_results extends SelectionPage {
	
	public function getRequiredRights() { return array("edit_interview_results"); }
	
	public function executeSelectionPage() {
		require_once("component/calendar/CalendarJSON.inc");
		$event = CalendarJSON::getEventFromDB($_GET["session"], PNApplication::$instance->selection->getCalendarId());
		
		// lock criteria
		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		$lock_id = DataBaseLock::lockTable("InterviewCriterion_".PNApplication::$instance->selection->getCampaignId(), $locked_by);
		if ($locked_by <> null) {
			echo "<div><div class='info_box'>";
			echo toHTML($locked_by)." is currently editing data that avoid us to edit the interview results at the same time.";
			echo "</div></div>";
			return;
		}
		DataBaseLock::generateScript($lock_id);
		// lock event
		$locked_by = null;
		$event_lock_id = DataBaseLock::lockRow("CalendarEvent", $_GET["session"], $locked_by, true);
		if ($locked_by <> null) {
			echo "<div><div class='info_box'>";
			echo toHTML($locked_by)." is currently editing data that avoid us to edit the interview results at the same time.";
			echo "</div></div>";
			DataBaseLock::unlock($lock_id);
			return;
		}
		DataBaseLock::generateScript($event_lock_id);
		
		require_once("component/selection/SelectionApplicantJSON.inc");
		$q = SQLQuery::create()->select("Applicant")->whereValue("Applicant","interview_session",$_GET["session"]);
		SelectionApplicantJSON::ApplicantSQL($q);
		$applicants = $q->execute();
		
		$applicants_ids = array();
		foreach ($applicants as $a) array_push($applicants_ids, $a["people_id"]);
		
		if (count($applicants_ids) > 0) {
			$results = SQLQuery::create()->select("ApplicantInterviewCriterionGrade")->whereIn("ApplicantInterviewCriterionGrade","people",$applicants_ids)->execute();
			$interviewers = SQLQuery::create()->select("ApplicantInterviewer")->whereIn("ApplicantInterviewer","applicant",$applicants_ids)->execute();
			$applicants_results = array();
			foreach ($results as $r) {
				if (!isset($applicants_results[$r["people"]])) $applicants_results[$r["people"]] = array();
				$applicants_results[$r["people"]][$r["criterion"]] = $r["grade"];
			}
			$applicants_interviewers = array();
			foreach ($interviewers as $i) {
				if (!isset($applicants_interviewers[$i["applicant"]])) $applicants_interviewers[$i["applicant"]] = array();
				array_push($applicants_interviewers[$i["applicant"]], intval($i["interviewer"]));
			}
		} else {
			$applicants_results = array();
			$applicants_interviewers = array();
		}
		
		$criteria = SQLQuery::create()->select("InterviewCriterion")->execute();
		
		$this->requireJavascript("grid.js");
		$this->requireJavascript("custom_data_grid.js");
		$this->requireJavascript("people_data_grid.js");
		$this->requireJavascript("applicant_data_grid.js");
		theme::css($this, "grid.css");
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_text.js");
		$this->requireJavascript("field_decimal.js");
		$this->requireJavascript("field_multiple_choice.js");
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
	<div class='page_title' style='flex:none;'>
		<img src='<?php echo theme::make_icon("/static/selection/interview/interview_32.png", theme::$icons_16["ok"]);?>'/>
		Results <span style='font-size:12pt'><b><i><?php echo toHTML($event["title"]);?></i></b> on <b><i><?php echo date("d M", $event["start"]);?></i></b> at <b><i><?php echo date("H:ia", $event["start"]);?></i></b></span>
	</div>
	<div style='flex:1 1 auto;' id='grid_container'>
	</div>
	<div class="page_footer" style="flex:none" id='footer'>
		<button class='action' onclick='save();'><img src='<?php echo theme::$icons_16["save"];?>'/> Save, apply rules, and see passers</button>
		<button class='action' onclick='importFile(event);'><img src='<?php echo theme::$icons_16["_import"];?>'/> Import From File</button>
	</div>
</div>
<script type='text/javascript'>
var applicants = <?php echo SelectionApplicantJSON::ApplicantsJSON($applicants);?>;
var applicants_results = <?php echo json_encode($applicants_results); ?>;
var applicants_interviewers = <?php echo json_encode($applicants_interviewers);?>;
var criteria = <?php echo json_encode($criteria);?>;
var interviewers = [<?php
$first = true;
foreach ($event["attendees"] as $interviewer) {
	if ($interviewer["role"] == "NONE") continue;
	if ($first) $first = false; else echo ",";
	echo "[".$interviewer["id"].",".json_encode($interviewer["name"])."]";
}
?>];

function getApplicantResult(applicant_id, criterion_id) {
	if (typeof applicants_results[applicant_id] == 'undefined') return null;
	var grade = applicants_results[applicant_id][criterion_id];
	if (typeof grade == 'undefined') return null;
	return grade;
}

function getApplicantInterviewers(applicant_id) {
	if (typeof applicants_interviewers[applicant_id] == 'undefined') return [];
	return applicants_interviewers[applicant_id];
}

var grid = new applicant_data_grid('grid_container',function(obj){return obj;},true);
grid.grid.makeScrollable();

function absentChanged(f) {
	var row_index = grid.grid.getContainingRowIndex(f.getHTMLElement());
	var applicant_id = grid.grid.getRowIDFromIndex(row_index);
	for (var i = 0; i < criteria.length; ++i) {
		var field = grid.grid.getCellField(row_index, grid.grid.getColumnIndexById('criterion_'+criteria[i].id));
		if (f.getCurrentData()) {
			field.setEditable(false);
			field.setData(null);
		} else
			field.setEditable(true);
	}
	var field = grid.grid.getCellField(row_index, grid.grid.getColumnIndexById('interviewers'));
	if (f.getCurrentData()) {
		field.setEditable(false);
		field.setData([]);
	} else
		field.setEditable(true);
}

grid.addColumn(new CustomDataGridColumn(
	new GridColumn('attendance', "Absent", null, 'center', 'field_boolean', true, absentChanged, absentChanged, {}),
	function(applicant) { return applicant.interview_attendance === false; },
	true,
	null,null,
	true
));
var cols = [];
for (var i = 0; i < criteria.length; ++i) {
	var col = new GridColumn('criterion_'+criteria[i].id, criteria[i].name, null, 'center', 'field_decimal', true, null, null, {integer_digits:3,decimal_digits:2,min:0,max:criteria[i].max_score,can_be_null:true});
	col = new CustomDataGridColumn(col, function(obj, criterion_id) { return getApplicantResult(obj.people.id, criterion_id); }, true, criteria[i].id, null, null, true);
	cols.push(col);
}
grid.addColumnContainer(new CustomDataGridColumnContainer("Criteria", cols));
grid.addColumn(new CustomDataGridColumn(
	new GridColumn('interviewers', "Interviewers", null, null, 'field_multiple_choice', true, null, null, {possible_values:interviewers,wrap:'yes'}),
	function(applicant) { return getApplicantInterviewers(applicant.people.id); },
	true,
	null,null,
	true
));
grid.addColumn(new CustomDataGridColumn(
	new GridColumn('comment', "Comment", 300, null, 'field_text', true, null, null, {can_be_null:true,max_length:1000}),
	function(applicant) { return applicant.interview_comment; },
	true,
	null,null,
	true
));

for (var i = 0; i < applicants.length; ++i)
	grid.addApplicant(applicants[i]);

function importFile(event) {
	if (grid.grid._import_with_match) return;
	require("import_with_match.js",function() {
		var prov = new import_with_match_provider_custom_data_grid(grid);
		prov.getColumnsCanBeMatched = function() {
			var cols = [];
			var gcols = grid.getAllFinalColumns();
			for (var i = 0; i < gcols.length; ++i) {
				if (!gcols[i].shown) continue;
				var id = gcols[i].grid_column.id;
				if (id == 'attendance') continue;
				if (id == 'interviewers') continue;
				if (id == 'comment') continue;
				if (id.startsWith("criterion_")) continue;
				cols.push({ id: id, name: gcols[i].select_menu_name ? gcols[i].select_menu_name : gcols[i].grid_column.title });
			}
			return cols;
		};
		prov.getColumnsCanBeImported = function() {
			var cols = [];
			var gcols = grid.getAllFinalColumns();
			for (var i = 0; i < gcols.length; ++i) {
				if (!gcols[i].shown) continue;
				var id = gcols[i].grid_column.id;
				if (id == 'attendance' || id == 'comment' || id.startsWith("criterion_"))
					cols.push({ id: id, name: gcols[i].select_menu_name ? gcols[i].select_menu_name : gcols[i].grid_column.title });
			}
			return cols;
		};
		new import_with_match(prov, event, true);
	});
}

function save() {
	var data = [];
	var col_absent = grid.grid.getColumnIndexById('attendance');
	var col_criteria = [];
	for (var i = 0; i < criteria.length; ++i) col_criteria.push(grid.grid.getColumnIndexById('criterion_'+criteria[i].id));
	var col_interviewers = grid.grid.getColumnIndexById('interviewers');
	var col_comment = grid.grid.getColumnIndexById('comment');
	var applicants_missing_grades = [];
	var applicants_missing_interviewers = [];
	for (var i = 0; i < applicants.length; ++i) {
		var row_index = grid.grid.getRowIndexById(applicants[i].people.id);
		var absent = grid.grid.getCellField(row_index, col_absent).getCurrentData();
		var comment = grid.grid.getCellField(row_index, col_comment).getCurrentData();
		if (absent) {
			data.push({applicant:applicants[i].people.id,attendance:false,comment:comment});
			continue;
		}
		var grades = [];
		for (var j = 0; j < criteria.length; ++j) {
			var grade = grid.grid.getCellField(row_index, col_criteria[j]).getCurrentData();
			if (grade === null) {
				applicants_missing_grades.push(applicants[i]);
				break;
			}
			grades.push({grade:grade,criterion:criteria[j].id});
		}
		if (grades.length < criteria.length) continue;
		var interviewers = grid.grid.getCellField(row_index, col_interviewers).getCurrentData();
		if (interviewers.length == 0) applicants_missing_interviewers.push(applicants[i]);
		data.push({
			applicant: applicants[i].people.id,
			attendance: true,
			comment: comment,
			grades: grades,
			interviewers: interviewers
		});
	}
	var error = "";
	if (applicants_missing_grades.length > 0) {
		error += "The following applicants are missing grades, and not marked as absent, so nothing will be saved for them:<ul>";
		for (var i = 0; i < applicants_missing_grades.length; ++i)
			error += "<li>"+applicants_missing_grades[i].people.first_name+" "+applicants_missing_grades[i].people.last_name+" (ID "+applicants_missing_grades[i].applicant_id+")</li>";
		error += "</ul>";
	}
	if (applicants_missing_interviewers.length > 0) {
		error += "The following applicants don't have any interviewer ?? We can still save, but make sure this is correct:<ul>";
		for (var i = 0; i < applicants_missing_interviewers.length; ++i)
			error += "<li>"+applicants_missing_interviewers[i].people.first_name+" "+applicants_missing_interviewers[i].people.last_name+" (ID "+applicants_missing_interviewers[i].applicant_id+")</li>";
		error += "</ul>";
	}
	var doit = function() {
		if (data.length == 0) { errorDialog("Nothing to save !"); return; }
		var locker = lockScreen(null, "Saving results, and applying eligibility rules...");
		service.json("selection","interview/save_results",{session:<?php echo $_GET["session"];?>,applicants:data},function(res) {
			if (res === null || res === false) { unlockScreen(locker); return; }
			var e = document.getElementById('footer');
			e.parentNode.removeChild(e);
			e = document.getElementById('grid_container');
			e.removeAllChildren();
			e.style.overflow = "auto";
			e.style.backgroundColor = "white";
			e.innerHTML = "<div class='page_section_title'>List of passers</div>";
			var div = document.createElement("DIV");
			e.appendChild(div);
			div.style.padding = "10px";
			var s = "Results successfully saved.<br/>";
			if (res.length == 0)
				s += "Unfortunately, no one passed. All applicants of this session have been excluded from the Selection Process.";
			else {
				s += "Here is the list of applicants who passed:<ul>";
				for (var i = 0; i < res.length; ++i) {
					s += "<li>";
					var app = null;
					for (var j = 0; j < applicants.length; ++j) if (applicants[j].people.id == res[i]) { app = applicants[j]; break; }
					s += app.people.first_name+" "+app.people.last_name+" (ID "+app.applicant_id+")";
					s += "</li>";
				}
				s += "</ul>All others have been exluded from the Selection Process.";
			}
			div.innerHTML = s;
			unlockScreen(locker);
		});
	};
	if (error.length == 0) doit();
	else confirmDialog(error,function(yes){ if(yes) doit(); });
}
</script>
<?php 
	}
	
}
?>