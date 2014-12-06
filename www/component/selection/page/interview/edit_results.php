<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_interview_edit_results extends SelectionPage {
	
	public function getRequiredRights() { return array("edit_interview_results"); }
	
	public function executeSelectionPage() {
		require_once("component/calendar/CalendarJSON.inc");
		$event = CalendarJSON::getEventFromDB($_GET["session"], PNApplication::$instance->selection->getCalendarId());
		
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
				array_push($applicants_interviewers[$i["applicant"]], $i["interviewer"]);
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
	<div class="page_footer" style="flex:none">
		<button class='action' onclick='save();'><img src='<?php echo theme::$icons_16["save"];?>'/> Save, apply rules, and see passers</button>
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

var cols = [];
for (var i = 0; i < criteria.length; ++i) {
	var col = new GridColumn('criterion_'+criteria[i].id, criteria[i].name, null, 'center', 'field_decimal', true, null, null, {integer_digits:3,decimal_digits:2,min:0,max:criteria[i].max_score,can_be_null:true});
	col = new CustomDataGridColumn(col, function(obj, criterion_id) { return getApplicantResult(obj.people.id, criterion_id); }, true, criteria[i].id, null, true);
	cols.push(col);
}
grid.addColumnContainer(new CustomDataGridColumnContainer("Criteria", cols));
grid.addColumn(new CustomDataGridColumn(
	new GridColumn('interviewers', "Interviewers", null, null, 'field_multiple_choice', true, null, null, {possible_values:interviewers,wrap:'yes'}),
	function(applicant) { return getApplicantInterviewers(applicant.people.id); },
	true,
	null,
	true
));
grid.addColumn(new CustomDataGridColumn(
	new GridColumn('comment', "Comment", 300, null, 'field_text', true, null, null, {can_be_null:true,max_length:1000}),
	function(applicant) { return applicant.interview_comment; },
	true,
	null,
	true
));

for (var i = 0; i < applicants.length; ++i)
	grid.addApplicant(applicants[i]);
</script>
<?php 
	}
	
}
?>