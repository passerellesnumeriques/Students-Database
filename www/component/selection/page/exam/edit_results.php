<?php
require_once("component/selection/page/SelectionPage.inc"); 
class page_exam_edit_results extends SelectionPage {
	
	public function getRequiredRights() { return array("edit_exam_results"); }
	
	public function executeSelectionPage() {
		$session_id = $_GET["session"];
		$room_id = $_GET["room"];
		
		// lock all subjects
		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		$lock_id = DataBaseLock::lockTable("ExamSubject_".PNApplication::$instance->selection->getCampaignId(), $locked_by);
		if ($locked_by <> null) {
			echo "<div><div class='info_box'>";
			echo toHTML($locked_by)." is currently editing data that avoid us to edit the exam results at the same time.";
			echo "</div></div>";
			return;
		}
		DataBaseLock::generateScript($lock_id);

		// get information about the session and room
		$room = SQLQuery::create()->select("ExamCenterRoom")->whereValue("ExamCenterRoom","id",$room_id)->executeSingleRow();
		$exam_center = SQLQuery::create()->select("ExamCenter")->whereValue("ExamCenter","id",$room["exam_center"])->executeSingleRow();
		$session = PNApplication::$instance->calendar->getEvent(PNApplication::$instance->selection->getCalendarId(), $session_id);
		
		// get the list of subjects with questions
		$subjects = SQLQuery::create()->select("ExamSubject")->execute();
		$subjects_parts = SQLQuery::create()->select("ExamSubjectPart")->execute();
		$questions = SQLQuery::create()->select("ExamSubjectQuestion")->execute();
		$subjects_versions = SQLQuery::create()->select("ExamSubjectVersion")->orderBy("ExamSubjectVersion","id")->execute();
		if (PNApplication::$instance->selection->getOneConfigAttributeValue("set_correct_answer")) {
			$answers = SQLQuery::create()->select("ExamSubjectAnswer")->execute();
		}
		// put the parts inside the subjects
		for ($i = 0; $i < count($subjects); $i++) {
			$subjects[$i]["parts"] = array();
			foreach ($subjects_parts as $sp) {
				if ($sp["exam_subject"] <> $subjects[$i]["id"]) continue;
				$sp["questions"] = array();
				foreach ($questions as $q) {
					if ($q["exam_subject_part"] <> $sp["id"]) continue;
					if (PNApplication::$instance->selection->getOneConfigAttributeValue("set_correct_answer")) {
						$q["answers"] = array();
						foreach ($subjects_versions as $version) {
							if ($version["exam_subject"] <> $subjects[$i]["id"]) continue;
							foreach ($answers as $a) {
								if ($a["exam_subject_question"] <> $q["id"]) continue;
								if ($a["exam_subject_version"] <> $version["id"]) continue;
								array_push($q["answers"],array("version"=>$version["id"],"answer"=>$a["answer"]));
							}
						}
					}
					array_push($sp["questions"], $q);
				}
				usort($sp["questions"],function($q1,$q2) {
					return intval($q1["index"])-intval($q2["index"]);
				});
				array_push($subjects[$i]["parts"], $sp);
			}
			usort($subjects[$i]["parts"],function($p1,$p2) {
				return intval($p1["index"])-intval($p2["index"]);
			});
			$subjects[$i]["versions"] = array();
			foreach ($subjects_versions as $version) {
				if ($version["exam_subject"] <> $subjects[$i]["id"]) continue;
				array_push($subjects[$i]["versions"], $version["id"]);
			}
		}
		
		// get applicants assigned to this session/room
		$q = SQLQuery::create()
			->select("Applicant")
			->whereValue("Applicant", "exam_center_room", $room_id)
			->whereValue("Applicant", "exam_session", $session_id)
			;
		PNApplication::$instance->people->joinPeople($q, "Applicant", "people", false);
		require_once("component/selection/SelectionApplicantJSON.inc");
		SelectionApplicantJSON::ApplicantSQL($q);
		$applicants = $q->execute();
		$applicants_ids = array();
		foreach ($applicants as $a) array_push($applicants_ids, $a["people_id"]);
		
		// get results already in DB for applicants
		$applicants_answers = array();
		$applicants_parts = array();
		$applicants_subjects = array();
		if (count($applicants_ids) > 0) {
			$_applicants_answers = SQLQuery::create()->select("ApplicantExamAnswer")->whereIn("ApplicantExamAnswer","applicant", $applicants_ids)->execute();
			$_applicants_parts = SQLQuery::create()->select("ApplicantExamSubjectPart")->whereIn("ApplicantExamSubjectPart","applicant", $applicants_ids)->execute();
			$_applicants_subjects = SQLQuery::create()->select("ApplicantExamSubject")->whereIn("ApplicantExamSubject","applicant", $applicants_ids)->execute();
			foreach ($_applicants_answers as $a) {
				if (!isset($applicants_answers[$a["applicant"]])) $applicants_answers[$a["applicant"]] = array();
				array_push($applicants_answers[$a["applicant"]], $a);
			}
			foreach ($_applicants_parts as $a) {
				if (!isset($applicants_parts[$a["applicant"]])) $applicants_parts[$a["applicant"]] = array();
				array_push($applicants_parts[$a["applicant"]], $a);
			}
			foreach ($_applicants_subjects as $a) {
				if (!isset($applicants_subjects[$a["applicant"]])) $applicants_subjects[$a["applicant"]] = array();
				array_push($applicants_subjects[$a["applicant"]], $a);
			}
		}
		
		$this->requireJavascript("tabs.js");
		$this->requireJavascript("grid.js");
		$this->requireJavascript("custom_data_grid.js");
		$this->requireJavascript("people_data_grid.js");
		$this->requireJavascript("applicant_data_grid.js");
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_text.js");
		$this->requireJavascript("field_decimal.js");
		$this->requireJavascript("field_enum.js");
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column'>
	<div class='page_title' style='flex:none'>
		<img src='/static/transcripts/grades_32.png'/>
		Exam Results
		<span style='font-size:12pt;color:#606060;margin-left:10px'>
			Exam Center <i><b><?php echo toHTML($exam_center["name"]);?></b></i>
			Session of <i><b><?php echo date("d M", $session["start"])." at ".date("H:ia", $session["start"]);?></b></i>
			in room <i><b><?php echo toHTML($room["name"]);?></b></i>
		</span>
	</div>
	<div id='header' style='flex:none;background-color:white;box-shadow: 1px 2px 5px 0px #808080;margin-bottom:5px;padding:5px;display:flex;flex-direction:row;align-items:center'>
		Edit mode: <select id='edit_mode' onchange='buildGrid();'>
			<?php if (PNApplication::$instance->selection->getOneConfigAttributeValue("set_correct_answer")) { ?>
			<option value='answers'>Enter answer for each question</option>
			<?php } ?>
			<option value='questions_scores'>Enter score for each question</option>
			<option value='parts_scores'>Enter score for each part</option>
		</select>
		<button class='flat' id='columns_chooser_button'><img src='/static/data_model/table_column.png'/> Choose columns</button>
		<button class='flat' onclick='importClickers(event);'><img src='/static/selection/exam/sunvote_16.png'/> Import from Clickers (SunVote)</button>
		<button class='flat' onclick='importScanner(event);'><img src='/static/selection/exam/amc_16.png'/> Import from Scanner (AMC)</button>
	</div>
	<div style='flex:1 1 auto;' id='tabs_container'>
	</div>
	<div id='footer' class='page_footer' style='flex:none'>
		<button class='action' onclick='save();'><img src='<?php echo theme::$icons_16["save"];?>'/> Save, apply rules, and see passers</button>
	</div>
</div>
<script type='text/javascript'>
var subjects = <?php echo json_encode($subjects);?>;
var applicants = <?php echo SelectionApplicantJSON::ApplicantsJSON($applicants);?>;

var applicants_results = {
<?php
$first_app = true;
foreach ($applicants_ids as $id) {
	if ($first_app) $first_app = false; else echo ",";
	echo "'$id':{";
	$first_subject = true;
	if (isset($applicants_subjects[$id]))
	foreach ($applicants_subjects[$id] as $as) {
		if ($first_subject) $first_subject = false; else echo ",";
		echo "'".$as["exam_subject"]."':{";
		echo "version:".json_encode($as["exam_subject_version"]);
		echo ",score:".json_encode($as["score"]);
		echo ",parts:{";
		foreach ($subjects as $s) if ($s["id"] == $as["exam_subject"]) { $subject = $s; break; }
		$first_part = true;
		foreach ($applicants_parts[$id] as $ap) {
			$part = null;
			foreach ($subject["parts"] as $sp) if ($sp["id"] == $ap["exam_subject_part"]) { $part = $sp; break; }
			if ($part == null) continue;
			if ($first_part) $first_part = false; else echo ",";
			echo "'".$ap["exam_subject_part"]."':{";
			echo "score:".json_encode($ap["exam_subject_part"]);
			echo ",questions:{";
			$first_question = true;
			foreach ($applicants_answers[$id] as $aa) {
				$found = false;
				foreach ($part["questions"] as $q) if ($q["id"] == $aa["exam_subject_question"]) { $found = true; break; }
				if (!$found) continue;
				if ($first_question) $first_question = false; else echo ",";
				echo "'".$aa["exam_subject_question"]."':{";
				echo "score:".json_encode($aa["score"]);
				echo ",answer:".json_encode($aa["answer"]);
				echo "}";
			}
			echo "}";
			echo "}";
		}
		echo "}";
		echo "}";
	}
	echo "}";
}
?>
};

var subjects_grids = [];
var subjects_grids_ready = [];
var changing_attendance = false;

function SubjectGrid(subject, container, edit_mode, onready) {
	var t=this;
	this.data_grid = new applicant_data_grid(container, function(applicant) { return applicant; }, true);
	this.data_grid.setColumnsChooserButton(document.getElementById('columns_chooser_button'));
	this.data_grid.grid.makeScrollable();

	this.attendanceChanged = function(field) {
		if (changing_attendance) return;
		changing_attendance = true;
		var cell = t.data_grid.grid.getContainingRowAndColIds(field.getHTMLElement());
		for (var i = 0; i < applicants.length; ++i)
			if (applicants[i].people.id == cell.row_id) {
				applicants[i].exam_attendance = field.getCurrentData();
				break;
			}
		for (var i = 0; i < subjects_grids.length; ++i) {
			if (subjects_grids[i] == t || subjects_grids[i] == null) continue;
			subjects_grids[i].data_grid.grid.getCellFieldById(cell.row_id,cell.col_id).setData(field.getCurrentData());
		}
		changing_attendance = false;
	};
	
	var col = new GridColumn("exam_attendance", "Attendance", null, "center", "field_enum", true, t.attendanceChanged, t.attendanceChanged, {can_be_null:true,possible_values:["Yes","No","Partially","Cheating"]});
	col.addSorting();
	var dcol = new CustomDataGridColumn(col, function(applicant) { return applicant.exam_attendance; }, true, null, null ,true);
	this.data_grid.addColumn(dcol);
	
	this.getQuestion = function(question_id) {
		for (var i = 0; i < subject.parts.length; ++i) {
			for (var j = 0; j < subject.parts[i].questions.length; ++j) {
				var q = subject.parts[i].questions[j];
				if (q.id == question_id) return q;
			}
		}
		return null;		
	};
	this.getApplicantVersion = function(row_id) {
		if (subject.versions.length == 1)
			return subject.versions[0];
		var version_field = t.data_grid.grid.getCellFieldById(row_id,"exam_version");
		return version_field.getCurrentData();
	};
	this.getAnswer = function(question, version) {
		for (var i = 0; i < question.answers.length; ++i)
			if (question.answers[i].version == version)
				return question.answers[i].answer;
		return null;
	};
	this.getPartFromQuestionID = function(question_id) {
		for (var i = 0; i < subject.parts.length; ++i) {
			for (var j = 0; j < subject.parts[i].questions.length; ++j) {
				var q = subject.parts[i].questions[j];
				if (q.id == question_id) return subject.parts[i];
			}
		}
		return null;		
	};

	this.computeTotals = function(row_id, subject_part) {
		// compute part total
		var part_total = null;
		var field_part_total = this.data_grid.grid.getCellFieldById(row_id, "total_"+subject_part.id);
		if (edit_mode != 'parts_scores') {
			for (var i = 0; i < subject_part.questions.length; ++i) {
				var field_pts = this.data_grid.grid.getCellFieldById(row_id, subject_part.questions[i].id+(edit_mode == 'answers' ? "_pts" : ""));
				var pts = field_pts.getCurrentData();
				if (pts !== null) {
					pts = parseFloat(pts);
					if (part_total === null) part_total = pts;
					else part_total += pts;
				}
			}
			field_part_total.setData(part_total);
			field_part_total.getHTMLElement().parentNode.style.backgroundColor = (part_total === null ? "" : "#C0C0FF");
			if (typeof applicants_results[row_id] == 'undefined')
				applicants_results[row_id] = {};
			if (typeof applicants_results[row_id][subject.id] == 'undefined')
				applicants_results[row_id][subject.id] = {version:null,score:null,parts:{}};
			if (typeof applicants_results[row_id][subject.id].parts[subject_part.id] == 'undefined')
				applicants_results[row_id][subject.id].parts[subject_part.id] = {score:null,questions:{}};
			applicants_results[row_id][subject.id].parts[subject_part.id].score = part_total;
		} else
			part_total = field_part_total.getCurrentData();
		var total = part_total === null ? null : parseFloat(part_total);
		for (var i = 0; i < subject.parts.length; ++i)
			if (subject.parts[i] != subject_part) {
				var pts = this.data_grid.grid.getCellFieldById(row_id, "total_"+subject.parts[i].id).getCurrentData();
				if (pts !== null) {
					pts = parseFloat(pts);
					if (total === null) total = pts;
					else total += pts;
				}
			}
		var field = this.data_grid.grid.getCellFieldById(row_id, "exam_total");
		field.setData(total);
		field.getHTMLElement().parentNode.style.backgroundColor = (total === null ? "" : "#C0C0FF");
		applicants_results[row_id][subject.id].score = total;
	};
	
	this.answerChanged = function(field) {
		var ans_cell = t.data_grid.grid.getContainingRowAndColIds(field.getHTMLElement());
		var pts_field = t.data_grid.grid.getCellFieldById(ans_cell.row_id,ans_cell.col_id+"_pts");
		var subject_part = t.getPartFromQuestionID(ans_cell.col_id);
		if (typeof applicants_results[ans_cell.row_id] == 'undefined')
			applicants_results[ans_cell.row_id] = {};
		if (typeof applicants_results[ans_cell.row_id][subject.id] == 'undefined')
			applicants_results[ans_cell.row_id][subject.id] = {version:null,score:null,parts:{}};
		if (typeof applicants_results[ans_cell.row_id][subject.id].parts[subject_part.id] == 'undefined')
			applicants_results[ans_cell.row_id][subject.id].parts[subject_part.id] = {score:null,questions:{}};
		if (typeof applicants_results[ans_cell.row_id][subject.id].parts[subject_part.id].questions[ans_cell.col_id] == 'undefined')
			applicants_results[ans_cell.row_id][subject.id].parts[subject_part.id].questions[ans_cell.col_id] = {score:null,answer:null};
		if (field.getCurrentData() == null) {
			pts_field.setData(0);
			pts_field.getHTMLElement().parentNode.style.backgroundColor = "";
			applicants_results[ans_cell.row_id][subject.id].parts[subject_part.id].questions[ans_cell.col_id].score = 0;
			applicants_results[ans_cell.row_id][subject.id].parts[subject_part.id].questions[ans_cell.col_id].answer = null;
			t.computeTotals(ans_cell.row_id,subject_part);
			return;
		}
		var q = t.getQuestion(ans_cell.col_id);
		var version = t.getApplicantVersion(ans_cell.row_id);
		if (version == null) {
			pts_field.setData(null);
			pts_field.getHTMLElement().parentNode.style.backgroundColor = "";
			applicants_results[ans_cell.row_id][subject.id].parts[subject_part.id].questions[ans_cell.col_id].score = null;
			applicants_results[ans_cell.row_id][subject.id].parts[subject_part.id].questions[ans_cell.col_id].answer = null;
			t.computeTotals(ans_cell.row_id,subject_part);
			return;
		}
		var ans = t.getAnswer(q, version);
		var pts;
		if (ans == field.getCurrentData())
			pts = parseFloat(q.max_score);
		else
			pts = -(parseFloat(q.max_score)/(parseInt(q.type_config)-1));
		pts_field.setData(pts);
		pts_field.getHTMLElement().parentNode.style.backgroundColor = (pts > 0 ? "#C0FFC0" : pts < 0 ? "#FFC0C0" : "");
		applicants_results[ans_cell.row_id][subject.id].parts[subject_part.id].questions[ans_cell.col_id].score = pts;
		applicants_results[ans_cell.row_id][subject.id].parts[subject_part.id].questions[ans_cell.col_id].answer = field.getCurrentData();
		t.computeTotals(ans_cell.row_id,subject_part);
	};

	this.scoreChanged = function(field) {
		var cell = t.data_grid.grid.getContainingRowAndColIds(field.getHTMLElement());
		var subject_part = t.getPartFromQuestionID(cell.col_id);
		if (typeof applicants_results[cell.row_id] == 'undefined')
			applicants_results[cell.row_id] = {};
		if (typeof applicants_results[cell.row_id][subject.id] == 'undefined')
			applicants_results[cell.row_id][subject.id] = {version:null,score:null,parts:{}};
		if (typeof applicants_results[cell.row_id][subject.id].parts[subject_part.id] == 'undefined')
			applicants_results[cell.row_id][subject.id].parts[subject_part.id] = {score:null,questions:{}};
		if (typeof applicants_results[cell.row_id][subject.id].parts[subject_part.id].questions[cell.col_id] == 'undefined')
			applicants_results[cell.row_id][subject.id].parts[subject_part.id].questions[cell.col_id] = {score:null,answer:null};
		if (field.getCurrentData() == null) {
			field.input.style.backgroundColor = "";
			applicants_results[cell.row_id][subject.id].parts[subject_part.id].questions[cell.col_id].score = null;
			applicants_results[cell.row_id][subject.id].parts[subject_part.id].questions[cell.col_id].answer = null;
			t.computeTotals(cell.row_id,subject_part);
			return;
		}
		var q = t.getQuestion(cell.col_id);
		var pts = field.getCurrentData();
		if (pts !== null) pts = parseFloat(pts);
		field.input.style.backgroundColor = (pts == null ? "" : pts > 0 ? "#C0FFC0" : pts < 0 ? "#FFC0C0" : "");
		applicants_results[cell.row_id][subject.id].parts[subject_part.id].questions[cell.col_id].score = pts;
		applicants_results[cell.row_id][subject.id].parts[subject_part.id].questions[cell.col_id].answer = null;
		t.computeTotals(cell.row_id,subject_part);
	};

	if (subject.versions.length > 1) {
		var possible = [];
		for (var i = 0; i < subject.versions.length; ++i)
			possible.push([subject.versions[i],String.fromCharCode("A".charCodeAt(0)+i)]);
		var updateScores = function(field) {
			var cell = t.data_grid.grid.getContainingRowAndColIds(field.getHTMLElement());
			if (edit_mode == 'answers') {
				for (var i = 0; i < subject.parts.length; ++i) {
					for (var j = 0; j < subject.parts[i].questions.length; ++j) {
						var q = subject.parts[i].questions[j];
						var ans_field = t.data_grid.grid.getCellFieldById(cell.row_id,q.id);
						t.answerChanged(ans_field);
					}
				}
			}
			if (typeof applicants_results[cell.row_id] == 'undefined')
				applicants_results[cell.row_id] = {};
			if (typeof applicants_results[cell.row_id][subject.id] == 'undefined')
				applicants_results[cell.row_id][subject.id] = {version:null,score:null,parts:{}};
			applicants_results[cell.row_id][subject.id].version = field.getCurrentData();
		};
		var col = new GridColumn("exam_version", "Subject Version", null, null, "field_enum", true, updateScores, updateScores, {possible_values:possible,can_be_null:true});
		col.addSorting();
		var dcol = new CustomDataGridColumn(col, function(applicant) {
			if (typeof applicants_results[applicant.people.id] == 'undefined') return null;
			if (typeof applicants_results[applicant.people.id][subject.id] == 'undefined') return null;
			return applicants_results[applicant.people.id][subject.id].version;
		}, true, null, null ,true);
		this.data_grid.addColumn(dcol);
	}

	var col = new GridColumn("exam_total", "Total Score", null, "right", "field_decimal", false, null, null, {can_be_null:true,integer_digits:3,decimal_digits:2});
	col.addSorting();
	var dcol = new CustomDataGridColumn(col, function(applicant) {
		if (typeof applicants_results[applicant.people.id] == 'undefined') return null;
		if (typeof applicants_results[applicant.people.id][subject.id] == 'undefined') return null;
		return applicants_results[applicant.people.id][subject.id].score;
	}, true, null, null ,true);
	this.data_grid.addColumn(dcol);
	
	var q_index = 1;
	for (var i = 0; i < subject.parts.length; ++i) {
		if (edit_mode != 'parts_scores') {
			var cols = [];
			for (var j = 0; j < subject.parts[i].questions.length; ++j) {
				var q = subject.parts[i].questions[j];
				if (edit_mode == 'answers') {
					var field_classname = "field_text";
					var field_config = {};
					switch (q.type) {
					case "mcq_single":
						field_classname = "field_enum";
						field_config.can_be_null = true;
						field_config.possible_values = [];
						for (var k = 0; k < parseInt(q.type_config); ++k)
							field_config.possible_values.push(String.fromCharCode("A".charCodeAt(0)+k));
						break;
					}
					var col_ans = new GridColumn(q.id, "Ans.", null, null, field_classname, true, t.answerChanged, t.answerChanged, field_config);
					var dcol_ans = new CustomDataGridColumn(col_ans, function(applicant, o) {
						if (typeof applicants_results[applicant.people.id] == 'undefined') return null;
						if (typeof applicants_results[applicant.people.id][subject.id] == 'undefined') return null;
						if (typeof applicants_results[applicant.people.id][subject.id].parts[o.part_id] == 'undefined') return null;
						if (typeof applicants_results[applicant.people.id][subject.id].parts[o.part_id].questions[o.question_id] == 'undefined') return null;
						return applicants_results[applicant.people.id][subject.id].parts[o.part_id].questions[o.question_id].answer;
					}, true, {part_id:subject.parts[i].id,question_id:q.id}, null, true); 
					var col_pts = new GridColumn(q.id+"_pts", "Pts", null, "right", "field_decimal", false, null, null, {can_be_null:true,integer_digits:3,decimal_digits:2});
					var dcol_pts = new CustomDataGridColumn(col_pts, function(applicant, o) {
						if (typeof applicants_results[applicant.people.id] == 'undefined') return null;
						if (typeof applicants_results[applicant.people.id][subject.id] == 'undefined') return null;
						if (typeof applicants_results[applicant.people.id][subject.id].parts[o.part_id] == 'undefined') return null;
						if (typeof applicants_results[applicant.people.id][subject.id].parts[o.part_id].questions[o.question_id] == 'undefined') return null;
						return applicants_results[applicant.people.id][subject.id].parts[o.part_id].questions[o.question_id].score;
					}, true, {part_id:subject.parts[i].id,question_id:q.id}, null, true); 
					cols.push(new CustomDataGridColumnContainer("Q"+q_index, [dcol_ans, dcol_pts]));
				} else {
					var col = new GridColumn(q.id, "Q"+q_index, null, "right", "field_decimal", true, t.scoreChanged, t.scoreChanged, {can_be_null:true,integer_digits:3,decimal_digits:2});
					col.addSorting();
					var dcol = new CustomDataGridColumn(col, function(applicant, o) {
						if (typeof applicants_results[applicant.people.id] == 'undefined') return null;
						if (typeof applicants_results[applicant.people.id][subject.id] == 'undefined') return null;
						if (typeof applicants_results[applicant.people.id][subject.id].parts[o.part_id] == 'undefined') return null;
						if (typeof applicants_results[applicant.people.id][subject.id].parts[o.part_id].questions[o.question_id] == 'undefined') return null;
						return applicants_results[applicant.people.id][subject.id].parts[o.part_id].questions[o.question_id].score;
					}, true, {part_id:subject.parts[i].id,question_id:q.id}, null, true); 
					cols.push(dcol);
				}
				q_index++;
			}
			var col = new GridColumn("total_"+subject.parts[i].id, "Total", null, "right", "field_decimal", false, null, null, {can_be_null:true,max:parseFloat(subject.parts[i].max_score),integer_digits:10,decimal_digits:2});
			col.addSorting();
			var dcol = new CustomDataGridColumn(col, function(applicant, part_id) {
				if (typeof applicants_results[applicant.people.id] == 'undefined') return null;
				if (typeof applicants_results[applicant.people.id][subject.id] == 'undefined') return null;
				if (typeof applicants_results[applicant.people.id][subject.id].parts[part_id] == 'undefined') return null;
				return applicants_results[applicant.people.id][subject.id].parts[part_id].score;
			}, true, subject.parts[i].id, null, true);
			cols.push(dcol);
			var part_container = new CustomDataGridColumnContainer("Part "+subject.parts[i].index + " - "+subject.parts[i].name, cols);
			this.data_grid.addColumnContainer(part_container);
		} else {
			var updateTotal = function(field) {
				var cell = t.data_grid.grid.getContainingRowAndColIds(field.getHTMLElement());
				var subject_part = null;
				for (var i = 0; i < subject.parts.length; ++i) if ("total_"+subject.parts[i].id == cell.col_id) { subject_part = subject.parts[i]; break; }
				t.computeTotals(cell.row_id,subject_part);
			};
			var col = new GridColumn("total_"+subject.parts[i].id, "Part "+subject.parts[i].index + " - "+subject.parts[i].name, null, null, "field_decimal", true, updateTotal, updateTotal, {can_be_null:true,max:parseFloat(subject.parts[i].max_score),integer_digits:10,decimal_digits:2});
			col.addSorting();
			var dcol = new CustomDataGridColumn(col, function(applicant, part_id) {
				if (typeof applicants_results[applicant.people.id] == 'undefined') return null;
				if (typeof applicants_results[applicant.people.id][subject.id] == 'undefined') return null;
				if (typeof applicants_results[applicant.people.id][subject.id].parts[part_id] == 'undefined') return null;
				return applicants_results[applicant.people.id][subject.id].parts[part_id].score;
			}, true, subject.parts[i].id, null, true);
			this.data_grid.addColumn(dcol);
		}
	}

	this.data_grid.grid.onallrowsready(function() {
		for (var i = 0; i < applicants.length; ++i)
			t.data_grid.addApplicant(applicants[i]);
		t.data_grid.grid.onallrowsready(function() {
			for (var i = 0; i < applicants.length; ++i) {
				for (var j = 0; j < subject.parts.length; ++j) {
					if (edit_mode == 'answers') {
						for (var k = 0; k < subject.parts[j].questions.length; ++k) {
							var f = t.data_grid.grid.getCellFieldById(applicants[i].people.id, subject.parts[j].questions[k].id);
							if (f.getCurrentData() !== null)
								t.answerChanged(f);
						}
					}
					t.computeTotals(applicants[i].people.id, subject.parts[j]);
				}
			}
			if (onready) onready();
		});
	});
}

var edit_mode = document.getElementById('edit_mode');

var subjects_tabs = null;
function buildGrid(onready) {
	var tabs_container = document.getElementById('tabs_container');
	tabs_container.removeAllChildren();
	subjects_grids = [];
	subjects_grids_ready = [];
	subjects_tabs = new tabs(tabs_container, true);
	subjects_tabs.onselect = function() {
		layout.pause();
		var container = subjects_tabs.tabs[subjects_tabs.selected].content;
		if (container.childNodes.length == 0) {
			var grid_container = document.createElement("DIV");
			grid_container.style.flex = "1 1 auto";
			container.appendChild(grid_container);
			subjects_grids[subjects_tabs.selected] = new SubjectGrid(subjects[subjects_tabs.selected], grid_container, edit_mode.value, function() {
				subjects_grids_ready[subjects_tabs.selected] = true;
				layout.resume();
			});
		} else
			layout.resume();
	};
	for (var i = 0; i < subjects.length; ++i) {
		var container = document.createElement("DIV");
		container.style.display = "flex";
		container.style.flexDirection = "column";
		subjects_tabs.addTab(subjects[i].name, null, container);
		subjects_grids.push(null);
		subjects_grids_ready.push(false);
	}
	if (onready) onready();
}
buildGrid();

setTimeout(function() { require(["upload.js","popup_window.js"]); }, 100);
function importClickers(ev) {
	var upl = new upload("/dynamic/selection/service/exam/import_sunvote_results", false);
	var popup = null;
	upl.ondone = function(outputs, errors, warnings) {
		var content = document.createElement("DIV");
		content.style.backgroundColor = "white";
		content.style.padding = "5px";
		popup.setContent(content);
		if (!outputs || outputs.length == 0 || !outputs[0]) errors.push("Upload failed");
		else {
			if (typeof outputs[0].subjects != 'undefined' && outputs[0].subjects.length == 0)
				errors.push("No test found in this file, is it really coming from the SunVote system ?");
		}
		if (warnings.length > 0) {
			for (var i = 0; i < warnings.length; ++i) {
				var div = document.createElement("DIV");
				div.innerHTML = "<img src='"+theme.icons_16.warning+"' style='vertical-align:bottom'/> "+warnings[i];
				content.appendChild(div);
			}
		}
		if (errors.length > 0) {
			for (var i = 0; i < errors.length; ++i) {
				var div = document.createElement("DIV");
				div.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+errors[i];
				content.appendChild(div);
			}
			layout.changed(content);
			return;
		}
		var tests = outputs[0].subjects;
		var div = document.createElement("DIV");
		div.innerHTML = tests.length+" test"+(tests.length > 1 ? "s" : "")+" found in the file:";
		content.appendChild(div);
		var ul = document.createElement("UL");
		content.appendChild(ul);
		// match tests from clickers with subjects from database
		var test_match = [];
		var remaining_subjects = [];
		var subject_matching = [];
		for (var i = 0; i < subjects.length; ++i) remaining_subjects.push(subjects[i]);
		for (var i = 0; i < tests.length; ++i) {
			var possible_subjects = [];
			for (var j = 0; j < remaining_subjects.length; ++j) {
				var nb_questions = 0;
				for (var k = 0; k < remaining_subjects[j].parts.length; ++k)
					nb_questions += remaining_subjects[j].parts[k].questions.length;
				if (nb_questions == 0) continue;
				if (nb_questions == tests[i].nb_questions) possible_subjects.push(remaining_subjects[j]);
			}
			if (possible_subjects.length == 0)
				test_match.push({test:tests[i]});
			else if (possible_subjects.length == 1) {
				var match = {test:tests[i],subject:possible_subjects[0]};
				test_match.push(match);
				subject_matching.push(match);
				remaining_subjects.removeUnique(possible_subjects[0]);
			} else {
				test_match.push({test:tests[i],possible_subjects:possible_subjects});
			}
		}
		// for ambiguous, try to match by name
		for (var i = 0; i < test_match.length; ++i) {
			if (!test_match[i].possible_subjects) continue;
			// first, try with an exact match
			for (var j = 0; j < test_match[i].possible_subjects.length; ++j) {
				if (test_match[i].test.name.isSame(test_match[i].possible_subjects[j].name)) {
					test_match[i].most_probable = test_match[i].possible_subjects[j];
					break;
				}
				if (test_match[i].most_probable) break;
			}
			// if no exact match, try by matching words
			if (!test_match[i].most_probable) {
				var best = null;
				var best_score = -1;
				for (var j = 0; j < test_match[i].possible_subjects.length; ++j) {
					var m = wordsMatch(test_match[i].test.name, test_match[i].possible_subjects[j].name, true);
					if (m.nb_words1_in_words2 == 0 && m.nb_words2_in_words1 == 0) continue;
					var score = Math.max(m.nb_words1_in_words2, m.nb_words2_in_words1);
					if (best == null || score > best_score) {
						best = test_match[i].possible_subjects[j];
						best_score = score;
					}
				}
				if (best != null)
					test_match[i].most_probable = best;
			}
		}
		
		for (var i = 0; i < tests.length; ++i) {
			var li = document.createElement("LI");
			ul.appendChild(li);
			li.appendChild(document.createTextNode(tests[i].name));
			li.appendChild(document.createTextNode(" ("+tests[i].nb_questions+" question"+(tests[i].nb_questions > 1 ? "s" : "")+")"));
			var match = test_match[i];
			if (match.subject) {
				var span = document.createElement("SPAN");
				span.style.marginLeft = "5px";
				span.style.fontWeight = "bold";
				span.appendChild(document.createTextNode("Match with subject "+match.subject.name));
				li.appendChild(span);
				if (match.subject.versions.length > 1) {
					li.appendChild(document.createTextNode(" Version:"));
					var select = document.createElement("SELECT");
					li.appendChild(select);
					var o = document.createElement("OPTION");
					o.value = null;
					o.text = "All (specify manually later)";
					select.add(o);
					for (var j = 0; j < match.subject.versions.length; ++j) {
						o = document.createElement("OPTION");
						o.value = match.subject.versions[j];
						o.text = String.fromCharCode("A".charCodeAt(0)+j);
						select.add(o);
					}
					select._match = match;
					select.onchange = function() {
						this._match.version = this.value;
					};
				}
			} else if (match.possible_subjects) {
				li.appendChild(document.createTextNode(" is subject "));
				var select_subject = document.createElement("SELECT");
				li.appendChild(select_subject);
				var o = document.createElement("OPTION");
				o.value = null;
				o.text = "None";
				select_subject.add(o);
				for (var j = 0; j < match.possible_subjects.length; ++j) {
					o = document.createElement("OPTION");
					o._subject = match.possible_subjects[j];
					o.text = match.possible_subjects[j].name;
					select_subject.add(o);
					if (match.most_probable && match.most_probable == match.possible_subjects[j])
						o.selected = true;
				}
				var span_version = document.createElement("SPAN");
				li.appendChild(span_version);
				span_version.appendChild(document.createTextNode(" Version "));
				var select_version = document.createElement("SELECT");
				select_version._match = match;
				span_version.appendChild(select_version);
				span_version.style.display = "none";
				o = document.createElement("OPTION");
				o.value = null;
				o.text = "All (specify manually later)";
				select_version.add(o);
				select_subject._match = match;
				select_subject._select_version = select_version;
				select_subject._span_version = span_version;
				select_subject.onchange = function() {
					subject_matching.remove(this._match);
					if (this.selectedIndex == 0) {
						this._span_version.style.display = "none";
						this._select_version.onchange = null;
						return;
					}
					var subj = this.options[this.selectedIndex]._subject;
					this._match.subject = subj;
					subject_matching.push(this._match);
					if (subj.versions.length <= 1) {
						this._span_version.style.display = "none";
						this._match.version = null;
						this._select_version.onchange = null;
						
					} else {
						this._span_version.style.display = "";
						while (this._select_version.options.length > 1) this._select_version.options.remove(1);
						for (var j = 0; j < subj.versions.length; ++j) {
							o = document.createElement("OPTION");
							o.value = subj.versions[j];
							o.text = String.fromCharCode("A".charCodeAt(0)+j);
							this._select_version.add(o);
						}
						this._select_version.onchange = function() {
							this._match.version = this.value;
						};
						
					}
				};
				select_subject.ondomremoved(function(e) {
					e._match = null;
					e._select_version = null;
					e._span_version = null;
				});
				if (select_subject.selectedIndex > 0) select_subject.onchange();
			} else {
				var span = document.createElement("SPAN");
				span.style.marginLeft = "5px";
				span.style.fontStyle = "italic";
				span.appendChild(document.createTextNode("No subject match the number of questions, impossible to import this test"));
				li.appendChild(span);
			}
		}
		layout.changed(content);
		popup.addNextButton(function() {
			if (subject_matching.length == 0) {
				popup.close();
				return;
			}
			popup.removeButtons();
			content.removeAllChildren();
			var missing = [];
			for (var i = 0; i < applicants.length; ++i) missing.push(applicants[i]);
			var exam_takers = {};
			var nb_takers = 0;
			for (var i = 0; i < tests.length; ++i) {
				for (var j = 0; j < tests[i].applicants.length; ++j)
					if (typeof exam_takers[tests[i].applicants[j].id] == 'undefined') {
						nb_takers++;
						var app = null;
						for (var k = 0; k < applicants.length; ++k) if (applicants[k].applicant_id == tests[i].applicants[j].id) { app = applicants[k]; break; }
						if (app != null)
							for (var k = 0; k < missing.length; ++k) if (missing[k] == app) { missing.splice(k,1); break; }
						exam_takers[tests[i].applicants[j].id] = app;
					}
			}
			div = document.createElement("DIV");
			div.innerHTML = nb_takers+" clicker"+(nb_takers > 1 ? "s" : "")+" found in the file:";
			content.appendChild(div);
			var table = document.createElement("TABLE");
			var tr,td;
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TH"));
			td.appendChild(document.createTextNode("ID"));
			tr.appendChild(td = document.createElement("TH"));
			td.appendChild(document.createTextNode("Applicant"));
			for (var i = 0; i < subject_matching.length; ++i) {
				tr.appendChild(td = document.createElement("TH"));
				td.appendChild(document.createTextNode(subject_matching[i].subject.name));
			}
			content.appendChild(table);
			var applicants_data = {};
			for (var i = 0; i < applicants.length; ++i) {
				var id = ""+applicants[i].applicant_id;
				applicants_data[id] = {attendance:null,row_id:applicants[i].people.id,answers:{}};
				for (var j = 0; j < subject_matching.length; ++j) {
					applicants_data[id].answers[subject_matching[j].subject.id] = [];
				}
			}
			var keypads_replacements = [];
			for (var id in exam_takers) {
				id = ""+id;
				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.appendChild(document.createTextNode(id));
				tr.appendChild(td = document.createElement("TD"));
				var app = exam_takers[id];
				if (app != null) {
					td.appendChild(document.createTextNode(app.people.first_name+" "+app.people.last_name));
				} else {
				}
				var used = app != null;
				for (var i = 0; i < subject_matching.length; ++i) {
					tr.appendChild(td = document.createElement("TD"));
					for (var j = 0; j < subject_matching[i].test.applicants.length; ++j) {
						if (subject_matching[i].test.applicants[j].id != id) continue;
						var answers = subject_matching[i].test.applicants[j].answers;
						var last_answer = subject_matching[i].test.nb_questions-1;
						while (last_answer >= 0 && (answers.length < last_answer || answers[last_answer] == null)) last_answer--;
						if (last_answer < 0) {
							td.appendChild(document.createTextNode("Didn't attend"));
							if (app != null && applicants_data[id].attendance === null) applicants_data[id].attendance = 0;
						} else {
							used = true;
							var first_answer = 0;
							while (first_answer < subject_matching[i].test.nb_questions && first_answer < answers.length && answers[first_answer] == null) first_answer++;
							if (first_answer == 0 && last_answer == subject_matching[i].test.nb_questions-1) {
								td.appendChild(document.createTextNode("Answered all questions"));
							} else {
								td.appendChild(document.createTextNode("Answered from question "+(first_answer+1)+" to "+(last_answer+1)));
							}
							if (app != null) {
								if (applicants_data[id].attendance === null) applicants_data[id].attendance = 1;
								else applicants_data[id].attendance++;
								for (var k = 0; k <= last_answer; k++)
									applicants_data[id].answers[subject_matching[i].subject.id].push(answers[k]);
							} else {
								td.appendChild(document.createElement("BR"));
								td.appendChild(document.createTextNode("Used by"));
								var select = document.createElement("SELECT");
								td.appendChild(select);
								var o = document.createElement("OPTION");
								o.value = null;
								o.text = "Nobody";
								select.add(o);
								var list = [];
								for (var k = 0; k < applicants.length; ++k) list.push(applicants[k]);
								list.sort(function(a1,a2){return parseInt(a1.applicant_id)-parseInt(a2.applicant_id);});
								for (var k = 0; k < list.length; ++k) {
									o = document.createElement("OPTION");
									o._applicant = list[k];
									o.text = "ID "+list[k].applicant_id+" ("+list[k].people.first_name+" "+list[k].people.last_name+")";
									select.add(o);
								}
								select._replacement = null;
								select._answers = answers;
								select._subject = subject_matching[i].subject;
								select.onchange = function() {
									if (this._replacement) keypads_replacements.remove(this._replacement);
									this._replacement = null;
									if (this.selectedIndex > 0) {
										this._replacement = {applicant:this.options[this.selectedIndex]._applicant, answers:this._answers, subject:this._subject};
										keypads_replacements.push(this._replacement);
									}
								};
							}
						}
						break;
					}
				}
				if (!used) tr.parentNode.removeChild(tr);
				if (app != null && applicants_data[id].attendance !== null) {
					if (applicants_data[id].attendance == subject_matching.length)
						applicants_data[id].attendance = "Yes";
					else if (applicants_data[id].attendance == 0)
						applicants_data[id].attendance = "No";
					else
						applicants_data[id].attendance = "Partially";
				}
			}
			if (missing.length > 0) {
				div = document.createElement("DIV");
				div.innerHTML = missing.length+" applicant"+(missing.length > 1 ? "s are" : " is")+" missing, and will have the attendance set to 'No':";
				content.appendChild(div);
				var ul = document.createElement("UL");
				content.appendChild(ul);
				for (var i = 0; i < missing.length; ++i) {
					var li = document.createElement("LI");
					ul.appendChild(li);
					li.appendChild(document.createTextNode("ID "+missing[i].applicant_id+" ("+missing[i].people.first_name+" "+missing[i].people.last_name+")"));
				}
			}
			layout.changed(content);
			popup.addNextButton(function(){
				popup.removeButtons();
				content.removeAllChildren();
				content.innerHTML = "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Importing results...";
				layout.changed(content);
				var apply_keypads_replacements = function(ondone) {
					for (var i = 0; i < keypads_replacements.length; ++i) {
						var r = keypads_replacements[i];
						var id = ""+r.applicant.applicant_id;
						if (typeof exam_takers[id] == 'undefined')
							exam_takers[id] = r.applicant;
						if (typeof applicants_data[id].answers[""+r.subject.id] == 'undefined')
							applicants_data[id].answers[""+r.subject.id] = r.answers;
						else {
							for (var j = 0; j < r.answers.length; ++j) {
								if (!r.answers[j]) continue;
								while (applicants_data[id].answers[""+r.subject.id].length <= j) applicants_data[id].answers[""+r.subject.id].push(null);
								applicants_data[id].answers[""+r.subject.id][j] = r.answers[j];
							}
						}
						// recalculate attendance
						var nb_attended = 0;
						for (var j = 0; j < subject_matching.length; ++j)
							if (typeof applicants_data[id].answers[""+subject_matching[j].subject.id] != 'undefined' && applicants_data[id].answers[""+subject_matching[j].subject.id].length > 0)
								nb_attended++;
						if (nb_attended == 0)
							applicants_data[id].attendance = "No";
						else if (nb_attended == subject_matching.length)
							applicants_data[id].attendance = "Yes";
						else
							applicants_data[id].attendance = "Partially";
					}
					ondone();
				};
				var set_edit_mode = function(ondone) {
					if (edit_mode.value == 'answers') { ondone(); return; }
					edit_mode.value = "answers";
					buildGrid(ondone);
				};
				var set_attendance = function(ondone) {
					setTimeout(function() {
						for (var i = 0; i < missing.length; ++i) {
							missing[i].exam_attendance = "No";
							var field = subjects_grids[subjects_tabs.selected].data_grid.grid.getCellFieldById(missing[i].people.id, "exam_attendance");
							field.setData("No");
						}
						for (var id in applicants_data) {
							if (!applicants_data[id].attendance) continue;
							exam_takers[id].exam_attendance = applicants_data[id].attendance;
							var field = subjects_grids[subjects_tabs.selected].data_grid.grid.getCellFieldById(applicants_data[id].row_id, "exam_attendance");
							field.setData(applicants_data[id].attendance);
						}
						ondone();
					},10);
				};
				var import_applicant = function(id, subject, version, grid, ondone) {
					id = ""+id;
					if (typeof applicants_data[id] == 'undefined') { ondone(); return; }
					if (typeof applicants_data[id].answers[""+subject.id] == 'undefined') { ondone(); return; }
					setTimeout(function() {
						if (version) {
							var field = grid.getCellFieldById(applicants_data[id].row_id, "exam_version");
							field.setData(version);
						}
						var answers = applicants_data[id].answers[""+subject.id];
						var q_index = 0;
						for (var i = 0; i < subject.parts.length; ++i) {
							if (q_index >= answers.length) break;
							for (var j = 0; j < subject.parts[i].questions.length; ++j) {
								if (q_index >= answers.length) break;
								var q = subject.parts[i].questions[j];
								var field = grid.getCellFieldById(applicants_data[id].row_id, q.id);
								field.setData(answers[q_index]);
								q_index++;
							}
						}
						ondone();
					},1);
				};
				var import_subject = function(test, subject, version, ondone) {
					var subject_index = 0;
					while (subjects[subject_index] != subject) subject_index++;
					subjects_tabs.select(subject_index);
					var check_grid_ready = function() {
						if (!subjects_grids_ready[subject_index]) {
							setTimeout(check_grid_ready, 100);
							return;
						}
						var grid = subjects_grids[subject_index].data_grid.grid;
						var next_applicant = function(index) {
							if (index == applicants.length) { ondone(); return; }
							import_applicant(applicants[index].applicant_id, subject, version, grid, function() {
								next_applicant(index+1);
							});
						};
						next_applicant(0);
					};
					var check_tab = function() {
						if (subjects_grids[subject_index] == null) {
							setTimeout(check_tab, 100);
							return;
						}
						check_grid_ready();
					};
					check_tab();
				};
				setTimeout(function() {
					apply_keypads_replacements(function() {
						set_edit_mode(function() {
							layout.pause();
							set_attendance(function() {
								var next_subject = function(index) {
									setTimeout(function() {
										if (index == subject_matching.length) {
											layout.resume();
											popup.close();
											return;
										}
										import_subject(subject_matching[index].test, subject_matching[index].subject, subject_matching[index].version, function() {
											next_subject(index+1);
										});
									},10);
								};
								next_subject(0);
							});
						});
					});
				},10);
			});
		});
	};
	upl.addUploadPopup("/static/selection/exam/sunvote_16.png", "Import from Clickers", function(pop) { popup = pop; });
	upl.openDialog(ev, ".xls,.xlsx");
}

function importScanner(ev) {
	var upl = new upload("/dynamic/selection/service/exam/import_amc_results", true, true);
	var popup = null;
	upl.ondone = function(outputs, errors, warnings) {
		var content = document.createElement("DIV");
		content.style.backgroundColor = "white";
		content.style.padding = "5px";
		popup.setContent(content);
		// merge results
		var result = [];
		for (var i = 0; i < outputs.length; ++i)
			if (outputs[i] && outputs[i].subjects)
				for (var j = 0; j < outputs[i].subjects.length; ++j)
					result.push(outputs[i].subjects[j]);
		var div, ul;
		div = document.createElement("DIV");
		div.appendChild(document.createTextNode(result.length+" file"+(result.length>1?"s":"")+" uploaded:"));
		content.appendChild(div);
		ul = document.createElement("UL");
		content.appendChild(ul);

		// match tests from clickers with subjects from database
		var test_match = [];
		var remaining_subjects = [];
		var subject_matching = [];
		for (var i = 0; i < subjects.length; ++i) remaining_subjects.push(subjects[i]);
		for (var i = 0; i < result.length; ++i) {
			var possible_subjects = [];
			for (var j = 0; j < remaining_subjects.length; ++j) {
				var nb_questions = 0;
				for (var k = 0; k < remaining_subjects[j].parts.length; ++k)
					nb_questions += remaining_subjects[j].parts[k].questions.length;
				if (nb_questions == 0) continue;
				if (nb_questions == result[i].nb_questions) possible_subjects.push(remaining_subjects[j]);
			}
			if (possible_subjects.length == 0)
				test_match.push({test:result[i]});
			else if (possible_subjects.length == 1) {
				var match = {test:result[i],subject:possible_subjects[0]};
				test_match.push(match);
				subject_matching.push(match);
				remaining_subjects.removeUnique(possible_subjects[0]);
			} else {
				test_match.push({test:result[i],possible_subjects:possible_subjects});
			}
		}
		// for ambiguous, try to match by name
		for (var i = 0; i < test_match.length; ++i) {
			if (!test_match[i].possible_subjects) continue;
			var best = null;
			var best_score = -1;
			for (var j = 0; j < test_match[i].possible_subjects.length; ++j) {
				var m = wordsMatch(test_match[i].test.filename, test_match[i].possible_subjects[j].name, true);
				if (m.nb_words1_in_words2 == 0 && m.nb_words2_in_words1 == 0) continue;
				var score = Math.max(m.nb_words1_in_words2, m.nb_words2_in_words1);
				if (best == null || score > best_score) {
					best = test_match[i].possible_subjects[j];
					best_score = score;
				}
			}
			if (best != null)
				test_match[i].most_probable = best;
		}
		
		for (var i = 0; i < result.length; ++i) {
			var li = document.createElement("LI");
			ul.appendChild(li);
			li.appendChild(document.createTextNode(result[i].filename));
			if (result[i].error) {
				var span = document.createElement("SPAN");
				span.innerHTML = " <img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> ";
				span.appendChild(document.createTextNode(result[i].error));
				li.appendChild(span);
				continue;
			}
			li.appendChild(document.createTextNode(" ("+result[i].nb_questions+" question"+(result[i].nb_questions > 1 ? "s" : "")+")"));
			var match = test_match[i];
			if (match.subject) {
				var span = document.createElement("SPAN");
				span.style.marginLeft = "5px";
				span.style.fontWeight = "bold";
				span.appendChild(document.createTextNode("Match with subject "+match.subject.name));
				li.appendChild(span);
				if (match.subject.versions.length > 1) {
					li.appendChild(document.createTextNode(" Version:"));
					var select = document.createElement("SELECT");
					li.appendChild(select);
					var o = document.createElement("OPTION");
					o.value = null;
					o.text = "All (specify manually later)";
					select.add(o);
					for (var j = 0; j < match.subject.versions.length; ++j) {
						o = document.createElement("OPTION");
						o.value = match.subject.versions[j];
						o.text = String.fromCharCode("A".charCodeAt(0)+j);
						select.add(o);
					}
					select._match = match;
					select.onchange = function() {
						this._match.version = this.value;
					};
				}
			} else if (match.possible_subjects) {
				li.appendChild(document.createTextNode(" is subject "));
				var select_subject = document.createElement("SELECT");
				li.appendChild(select_subject);
				var o = document.createElement("OPTION");
				o.value = null;
				o.text = "None";
				select_subject.add(o);
				for (var j = 0; j < match.possible_subjects.length; ++j) {
					o = document.createElement("OPTION");
					o._subject = match.possible_subjects[j];
					o.text = match.possible_subjects[j].name;
					select_subject.add(o);
					if (match.most_probable && match.most_probable == match.possible_subjects[j])
						o.selected = true;
				}
				var span_version = document.createElement("SPAN");
				li.appendChild(span_version);
				span_version.appendChild(document.createTextNode(" Version "));
				var select_version = document.createElement("SELECT");
				select_version._match = match;
				span_version.appendChild(select_version);
				span_version.style.display = "none";
				o = document.createElement("OPTION");
				o.value = null;
				o.text = "All (specify manually later)";
				select_version.add(o);
				select_subject._match = match;
				select_subject._select_version = select_version;
				select_subject._span_version = span_version;
				select_subject.onchange = function() {
					subject_matching.remove(this._match);
					if (this.selectedIndex == 0) {
						this._span_version.style.display = "none";
						this._select_version.onchange = null;
						return;
					}
					var subj = this.options[this.selectedIndex]._subject;
					this._match.subject = subj;
					subject_matching.push(this._match);
					if (subj.versions.length <= 1) {
						this._span_version.style.display = "none";
						this._match.version = null;
						this._select_version.onchange = null;
						
					} else {
						this._span_version.style.display = "";
						while (this._select_version.options.length > 1) this._select_version.options.remove(1);
						for (var j = 0; j < subj.versions.length; ++j) {
							o = document.createElement("OPTION");
							o.value = subj.versions[j];
							o.text = String.fromCharCode("A".charCodeAt(0)+j);
							this._select_version.add(o);
						}
						this._select_version.onchange = function() {
							this._match.version = this.value;
						};
						
					}
				};
				select_subject.ondomremoved(function(e) {
					e._match = null;
					e._select_version = null;
					e._span_version = null;
				});
				if (select_subject.selectedIndex > 0) select_subject.onchange();
			} else {
				var span = document.createElement("SPAN");
				span.style.marginLeft = "5px";
				span.style.fontStyle = "italic";
				span.appendChild(document.createTextNode("No subject match the number of questions, impossible to import this file"));
				li.appendChild(span);
			}
		}
		
		layout.changed(content);
		popup.addNextButton(function() {
			if (subject_matching.length == 0) {
				popup.close();
				return;
			}
			popup.removeButtons();
			content.removeAllChildren();
			var missing = [];
			var unknown = {};
			var known = {};
			for (var i = 0; i < applicants.length; ++i) missing.push(applicants[i]);
			for (var i = 0; i < subject_matching.length; ++i) {
				for (var j = 0; j < subject_matching[i].test.applicants.length; ++j) {
					var ar = subject_matching[i].test.applicants[j];
					var id = parseInt(ar.id);
					ar.id = id;
					var app = null;
					for (var k = 0; k < applicants.length; ++k) if (applicants[k].applicant_id == id) { app = applicants[k]; break; }
					if (app == null) {
						if (typeof unknown[id] == 'undefined') unknown[id] = {};
						unknown[id][subject_matching[i].subject.id] = ar.scores;
						continue;
					}
					for (var k = 0; k < missing.length; ++k) if (missing[k].applicant_id == id) { missing.splice(k,1); break; }
					if (typeof known[id] == 'undefined') known[id] = {};
					known[id].applicant = app;
					known[id][subject_matching[i].subject.id] = ar.scores;
				}
			}
			var table = document.createElement("TABLE");
			content.appendChild(table);
			var tr,td;
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TH"));
			td.appendChild(document.createTextNode("ID"));
			tr.appendChild(td = document.createElement("TH"));
			td.appendChild(document.createTextNode("Applicant"));
			for (var i = 0; i < subject_matching.length; ++i) {
				tr.appendChild(td = document.createElement("TH"));
				td.appendChild(document.createTextNode(subject_matching[i].subject.name));
			}
			for (var id in known) {
				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.appendChild(document.createTextNode(id));
				tr.appendChild(td = document.createElement("TD"));
				td.appendChild(document.createTextNode(known[id].applicant.people.first_name+" "+known[id].applicant.people.last_name));
				for (var i = 0; i < subject_matching.length; ++i) {
					tr.appendChild(td = document.createElement("TD"));
					if (typeof known[id][subject_matching[i].subject.id] != 'undefined') {
						td.appendChild(document.createTextNode("Present"));
					} else {
						td.style.color = "#F0A000";
						td.style.fontWeight = "bold";
						td.appendChild(document.createTextNode("Absent"));
					}
				}
			}
			for (var id in unknown) {
				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.appendChild(document.createTextNode(id));
				tr.appendChild(td = document.createElement("TD"));
				var span = document.createElement("SPAN");
				span.style.color = "#FF4000";
				span.style.fontWeight = "bold";
				span.appendChild(document.createTextNode("Unknown"));
				td.appendChild(span);
				// TODO can select who it is among the missing ones ?
				for (var i = 0; i < subject_matching.length; ++i) {
					tr.appendChild(td = document.createElement("TD"));
					if (typeof unknown[id][subject_matching[i].subject.id] != 'undefined') {
						td.appendChild(document.createTextNode("Present"));
					} else {
						td.style.color = "#F0A000";
						td.style.fontWeight = "bold";
						td.appendChild(document.createTextNode("Absent"));
					}
				}
			}
			if (missing.length > 0) {
				div = document.createElement("DIV");
				content.appendChild(div);
				div.appendChild(document.createTextNode("The "+missing.length+" following applicant"+(missing.length>1?"s are":" is")+" missing:"));
				ul = document.createElement("UL");
				content.appendChild(ul);
				for (var i = 0; i < missing.length; ++i) {
					var li = document.createElement("LI");
					ul.appendChild(li);
					li.appendChild(document.createTextNode(missing[i].people.first_name+" "+missing[i].people.last_name+" (ID "+missing[i].applicant_id+")"));
				}
			}
			layout.changed(content);
			popup.addNextButton(function() {
				content.removeAllChildren();
				popup.removeButtons();
				content.innerHTML = "<img src='"+theme.icons_16.loading+"' style='verticala-align:bottom'/> Importing results...";
				layout.changed(content);
				var set_edit_mode = function(ondone) {
					if (edit_mode.value == 'questions_scores') { ondone(); return; }
					edit_mode.value = "questions_scores";
					buildGrid(ondone);
				};
				var set_attendance = function(ondone) {
					for (var i = 0; i < applicants.length; ++i) {
						if (typeof known[applicants[i].applicant_id] == 'undefined') continue;
						var nb_attended = 0;
						for (var j = 0; j < subject_matching.length; ++j)
							if (typeof known[applicants[i].applicant_id][subject_matching[j].subject.id] != 'undefined' && known[applicants[i].applicant_id][subject_matching[j].subject.id].length > 0)
								nb_attended++;
						if (nb_attended == 0)
							applicants[i].exam_attendance = "No";
						else if (nb_attended == subject_matching.length)
							applicants[i].exam_attendance = "Yes";
						else
							applicants[i].exam_attendance = "Partially";
						subjects_grids[0].data_grid.grid.getCellFieldById(applicants[i].people.id, "exam_attendance").setData(applicants[i].exam_attendance);
					}
					ondone();
				};
				var import_applicant = function(app, subject, version, grid, ondone) {
					if (typeof known[app.applicant_id] == 'undefined') { ondone(); return; }
					if (typeof known[app.applicant_id][subject.id] == 'undefined') { ondone(); return; }
					// TODO applicant matching for unknown ?
					setTimeout(function() {
						if (version) {
							var field = grid.getCellFieldById(app.people.id, "exam_version");
							field.setData(version);
						}
						var scores = known[app.applicant_id][subject.id];
						var q_index = 0;
						for (var i = 0; i < subject.parts.length; ++i) {
							if (q_index >= scores.length) break;
							for (var j = 0; j < subject.parts[i].questions.length; ++j) {
								if (q_index >= scores.length) break;
								var q = subject.parts[i].questions[j];
								var field = grid.getCellFieldById(app.people.id, q.id);
								field.setData(scores[q_index]);
								q_index++;
							}
						}
						ondone();
					},1);
				};
				var import_subject = function(test, subject, version, ondone) {
					var subject_index = 0;
					while (subjects[subject_index] != subject) subject_index++;
					subjects_tabs.select(subject_index);
					var check_grid_ready = function() {
						if (!subjects_grids_ready[subject_index]) {
							setTimeout(check_grid_ready, 100);
							return;
						}
						var grid = subjects_grids[subject_index].data_grid.grid;
						var next_applicant = function(index) {
							if (index == applicants.length) { ondone(); return; }
							import_applicant(applicants[index], subject, version, grid, function() {
								next_applicant(index+1);
							});
						};
						next_applicant(0);
					};
					var check_tab = function() {
						if (subjects_grids[subject_index] == null) {
							setTimeout(check_tab, 100);
							return;
						}
						check_grid_ready();
					};
					check_tab();
				};
				setTimeout(function() {
					set_edit_mode(function() {
						layout.pause();
						set_attendance(function() {
							var next_subject = function(index) {
								setTimeout(function() {
									if (index == subject_matching.length) {
										layout.resume();
										popup.close();
										return;
									}
									import_subject(subject_matching[index].test, subject_matching[index].subject, subject_matching[index].version, function() {
										next_subject(index+1);
									});
								},10);
							};
							next_subject(0);
						});
					});
				},10);
			});
		});
	};
	upl.addUploadPopup("/static/selection/exam/amc_16.png", "Import from Scanner", function(pop) { popup = pop; });
	upl.openDialog(ev, ".xls,.xlsx,.ods,.csv");
}

function save() {
	var applicants_to_save = [];
	var saveResults = function() {
		var locker = lock_screen(null, "Saving results and applying eligibility rules...");
		var data = {applicants:[],session:<?php echo $session_id;?>,room:<?php echo $room_id;?>,lock:<?php echo $lock_id;?>};
		for (var i = 0; i < applicants_to_save.length; ++i) {
			if (applicants_to_save[i].exam_attendance == null) continue;
			var app = {};
			data.applicants.push(app);
			app.people_id = applicants_to_save[i].people.id;
			app.exam_attendance = applicants_to_save[i].exam_attendance;
			app.subjects = [];
			for (var j = 0; j < subjects.length; ++j) {
				var s = {};
				app.subjects.push(s);
				s.id = subjects[j].id;
				if (!applicants_results[app.people_id]) continue;
				if (!applicants_results[app.people_id][subjects[j].id]) continue;
				var res = applicants_results[app.people_id][subjects[j].id];
				s.version = subjects[j].versions.length > 1 ? res.version : subjects[j].versions[0];
				s.parts = [];
				for (var k = 0; k < subjects[j].parts.length; ++k) {
					if (edit_mode.value == "parts_scores") {
						var p = {id:subjects[j].parts[k].id};
						if (typeof res.parts[subjects[j].parts[k].id] != 'undefined')
							p.score = res.parts[subjects[j].parts[k].id].score;
						else
							p.score = 0;
						s.parts.push(p);
						continue;
					}
					var p = {id:subjects[j].parts[k].id,questions:[]};
					s.parts.push(p);
					for (var l = 0; l < subjects[j].parts[k].questions.length; ++l) {
						var q = {};
						q.id = subjects[j].parts[k].questions[l].id;
						if (edit_mode.value == "answers")
							q.answer = typeof res.parts[subjects[j].parts[k].id].questions[q.id] != 'undefined' ? res.parts[subjects[j].parts[k].id].questions[q.id].answer : null;
						else
							q.score = typeof res.parts[subjects[j].parts[k].id].questions[q.id] != 'undefined' ? res.parts[subjects[j].parts[k].id].questions[q.id].score : 0;
						p.questions.push(q);
					}
				}
			}
		}
		service.json("selection","exam/save_results",data,function(res) {
			if (res === null || res === false) { unlock_screen(locker); return; }
			var e = document.getElementById('header');
			e.removeAllChildren();
			e.className = "page_section_title";
			e.innerHTML = "List of passers";
			e = document.getElementById('footer');
			e.parentNode.removeChild(e);
			e = document.getElementById('tabs_container');
			e.removeAllChildren();
			e.style.backgroundColor = "white";
			e.style.padding = "10px";
			var s = "Results successfully saved.<br/>";
			if (res.length == 0)
				s += "Unfortunately, no one passed. All applicants of this room/session have been excluded from the Selection Process.";
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
			e.innerHTML = s;
			unlock_screen(locker);
		});
	};
	var checkExamVersion = function() {
		var missing = [];
		for (var i = 0; i < applicants_to_save.length; ++i) {
			var subjects_missing = [];
			for (var j = 0; j < subjects.length; ++j)
				if (subjects[j].versions.length > 1 && (!applicants_results[applicants_to_save[i].people.id][subjects[j].id] || !applicants_results[applicants_to_save[i].people.id][subjects[j].id].version))
					subjects_missing.push(subjects[j]);
			if (subjects_missing.length == 0) continue;
			missing.push({applicant:applicants_to_save[i],subjects_missing:subjects_missing});
			applicants_to_save.splice(i,1);
			i--;
		}
		if (missing.length == 0) { saveResults(); return; }
		var msg = "The "+missing.length+" following applicant"+(missing.length>1 ? "s does" : " do")+"n't have the exam version set:<ul>";
		for (var i = 0; i < missing.length; ++i) {
			msg += "<li>"+missing[i].applicant.people.first_name+" "+missing[i].applicant.people.last_name+" (ID "+missing[i].applicant.applicant_id+")";
			msg += " for subject";
			if (missing[i].subjects_missing.length > 1) msg += "s";
			for (var j = 0; j < missing[i].subjects_missing.length; ++j) {
				if (j > 0) msg += ",";
				msg += " "+missing[i].subjects_missing[j].name;
			}
			msg += "</li>";
		}
		msg += "</ul>";
		msg += "For "+(missing.length>1?"those applicants":"this applicant")+" nothing will be saved.<br/>Do you confirm ?";
		confirm_dialog(msg, function(yes) {
			if (!yes) return;
			saveResults();
		});
	};
	var checkAttendance = function() {
		var attendance_missing = [];
		for (var i = 0; i < applicants.length; ++i)
			if (applicants[i].exam_attendance == null)
				attendance_missing.push(applicants[i]);
			else
				applicants_to_save.push(applicants[i]);
		if (attendance_missing.length > 0) {
			var msg = "The "+attendance_missing.length+" following applicant"+(attendance_missing.length>1 ? "s does" : " do")+"n't have the attendance set:<ul>";
			for (var i = 0; i < attendance_missing.length; ++i) {
				msg += "<li>"+attendance_missing[i].people.first_name+" "+attendance_missing[i].people.last_name+" (ID "+attendance_missing[i].applicant_id+")</li>";
			}
			msg += "</ul>";
			msg += "For "+(attendance_missing.length>1?"those applicants":"this applicant")+" nothing will be saved.<br/>Do you confirm ?";
			confirm_dialog(msg, function(yes) {
				if (!yes) return;
				checkExamVersion();
			});
		} else
			checkExamVersion();
	};
	checkAttendance();
}
</script>
<?php 
	}
}
?>