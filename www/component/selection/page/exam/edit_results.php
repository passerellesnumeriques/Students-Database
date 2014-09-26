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
		if (PNApplication::$instance->selection->getOneConfigAttributeValue("set_correct_answer")) {
			$subjects_versions = SQLQuery::create()->select("ExamSubjectVersion")->execute();
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
		if (count($applicants_ids) > 0) {
			$applicants_answers = SQLQuery::create()->select("ApplicantExamAnswer")->whereIn("ApplicantExamAnswer","applicant", $applicants_ids)->execute();
			$applicants_parts = SQLQuery::create()->select("ApplicantExamSubjectPart")->whereIn("ApplicantExamSubjectPart","applicant", $applicants_ids)->execute();
			$applicants_subjects = SQLQuery::create()->select("ApplicantExamSubject")->whereIn("ApplicantExamSubject","applicant", $applicants_ids)->execute();
		} else {
			$applicants_answers = array();
			$applicants_parts = array();
			$applicants_subjects = array();
		}
		
		$this->requireJavascript("tabs.js");
		$this->requireJavascript("grid.js");
		$this->requireJavascript("custom_data_grid.js");
		$this->requireJavascript("people_data_grid.js");
		$this->requireJavascript("applicant_data_grid.js");
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
	<div style='flex:none;background-color:white;box-shadow: 1px 2px 5px 0px #808080;margin-bottom:5px;padding:5px;display:flex;flex-direction:row;align-items:center'>
		Edit mode: <select id='edit_mode' onchange='buildGrid();'>
			<?php if (PNApplication::$instance->selection->getOneConfigAttributeValue("set_correct_answer")) { ?>
			<option value='answers'>Enter answer for each question</option>
			<?php } ?>
			<option value='questions_scores'>Enter score for each question</option>
			<option value='parts_scores'>Enter score for each part</option>
		</select>
		<button class='flat' id='columns_chooser_button'><img src='/static/data_model/table_column.png'/> Choose columns</button>
	</div>
	<div style='flex:1 1 auto;' id='tabs_container'>
	</div>
	<div class='page_footer' style='flex:none'>
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
	foreach ($applicants_subjects as $as) {
		if ($as["applicant"] <> $id) continue;
		if ($first_subject) $first_subject = false; else echo ",";
		echo "'".$as["exam_subject"]."':{";
		echo "version:".json_encode($as["version"]);
		echo ",score:".json_encode($as["score"]);
		echo ",parts:{";
		foreach ($subjects as $s) if ($s["id"] == $as["exam_subject"]) { $subject = $s; break; }
		$first_part = true;
		foreach ($applicants_parts as $ap) {
			if ($ap["applicant"] <> $id) continue;
			$part = null;
			foreach ($subject["parts"] as $sp) if ($sp["id"] == $ap["exam_subject_part"]) { $part = $sp; break; }
			if ($part == null) continue;
			if ($first_part) $first_part = false; else echo ",";
			echo "'".$ap["exam_subject_part"]."':{";
			echo "score:".json_encode($ap["exam_subject_part"]);
			echo ",questions:{";
			foreach ($applicants_answers as $aa) {
				if ($aa["applicant"] <> $id) continue;
				$found = false;
				foreach ($part["questions"] as $q) if ($q["id"] == $aa["exam_subject_question"]) { $found = true; break; }
				if (!$found) continue;
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

function SubjectGrid(subject, container, edit_mode) {
	var t=this;
	this.data_grid = new applicant_data_grid(container, function(applicant) { return applicant; }, true);
	this.data_grid.setColumnsChooserButton(document.getElementById('columns_chooser_button'));

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
		var part_total = 0;
		var field_part_total = this.data_grid.grid.getCellFieldById(row_id, "total_"+subject_part.id);
		if (edit_mode != 'parts_scores') {
			for (var i = 0; i < subject_part.questions.length; ++i) {
				var field_pts = this.data_grid.grid.getCellFieldById(row_id, subject_part.questions[i].id+(edit_mode == 'answers' ? "_pts" : ""));
				var pts = field_pts.getCurrentData();
				if (pts === null) { part_total = null; break; }
				part_total += pts;
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
		var total = part_total === null ? null : part_total;
		if (total !== null)
			for (var i = 0; i < subject.parts.length; ++i)
				if (subject.parts[i] != subject_part) {
					var pts = this.data_grid.grid.getCellFieldById(row_id, "total_"+subject.parts[i].id).getCurrentData();
					if (pts === null) { total = null; break; }
					total += pts;
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

	if (edit_mode == 'answers' && subject.versions.length > 1) {
		var possible = [];
		for (var i = 0; i < subject.versions.length; ++i)
			possible.push([subject.versions[i],String.fromCharCode("A".charCodeAt(0)+i)]);
		var updateScores = function(field) {
			var cell = t.data_grid.grid.getContainingRowAndColIds(field.getHTMLElement());
			for (var i = 0; i < subject.parts.length; ++i) {
				for (var j = 0; j < subject.parts[i].questions.length; ++j) {
					var q = subject.parts[i].questions[j];
					var ans_field = t.data_grid.grid.getCellFieldById(cell.row_id,q.id);
					t.answerChanged(ans_field);
				}
			}
			if (typeof applicants_results[cell.row_id] == 'undefined')
				applicants_results[cell.row_id] = {};
			if (typeof applicants_results[cell.row_id][subject.id] == 'undefined')
				applicants_results[cell.row_id][subject.id] = {version:null,score:null,parts:{}};
			applicants_results[cell.row_id][subject.id].version = field.getCurrentData();
		};
		var col = new GridColumn("exam_version", "Subject Version", null, null, "field_enum", true, updateScores, updateScores, {possible_values:possible,can_be_null:true});
		var dcol = new CustomDataGridColumn(col, function(applicant) {
			if (typeof applicants_results[applicant.people.id] == 'undefined') return null;
			if (typeof applicants_results[applicant.people.id][subject.id] == 'undefined') return null;
			return applicants_results[applicant.people.id][subject.id].version;
		}, true);
		this.data_grid.addColumn(dcol);
	}

	var col = new GridColumn("exam_total", "Total Score", null, "right", "field_decimal", false, null, null, {can_be_null:true,integer_digits:3,decimal_digits:2});
	var dcol = new CustomDataGridColumn(col, function(applicant) {
		if (typeof applicants_results[applicant.people.id] == 'undefined') return null;
		if (typeof applicants_results[applicant.people.id][subject.id] == 'undefined') return null;
		return applicants_results[applicant.people.id][subject.id].score;
	}, true);
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
					}, true, {part_id:subject.parts[i].id,question_id:q.id}); 
					var col_pts = new GridColumn(q.id+"_pts", "Pts", null, "right", "field_decimal", false, null, null, {can_be_null:true,integer_digits:3,decimal_digits:2});
					var dcol_pts = new CustomDataGridColumn(col_pts, function(applicant, o) {
						if (typeof applicants_results[applicant.people.id] == 'undefined') return null;
						if (typeof applicants_results[applicant.people.id][subject.id] == 'undefined') return null;
						if (typeof applicants_results[applicant.people.id][subject.id].parts[o.part_id] == 'undefined') return null;
						if (typeof applicants_results[applicant.people.id][subject.id].parts[o.part_id].questions[o.question_id] == 'undefined') return null;
						return applicants_results[applicant.people.id][subject.id].parts[o.part_id].questions[o.question_id].score;
					}, true, {part_id:subject.parts[i].id,question_id:q.id}); 
					cols.push(new CustomDataGridColumnContainer("Q"+q_index, [dcol_ans, dcol_pts]));
				} else {
					var col = new GridColumn(q.id, "Q"+q_index, null, "right", "field_decimal", true, null, null, {can_be_null:true,integer_digits:3,decimal_digits:2});
					var dcol = new CustomDataGridColumn(col, function(applicant, o) {
						if (typeof applicants_results[applicant.people.id] == 'undefined') return null;
						if (typeof applicants_results[applicant.people.id][subject.id] == 'undefined') return null;
						if (typeof applicants_results[applicant.people.id][subject.id].parts[o.part_id] == 'undefined') return null;
						if (typeof applicants_results[applicant.people.id][subject.id].parts[o.part_id].questions[o.question_id] == 'undefined') return null;
						return applicants_results[applicant.people.id][subject.id].parts[o.part_id].questions[o.question_id].score;
					}, true, {part_id:subject.parts[i].id,question_id:q.id}); 
					cols.push(dcol);
				}
				q_index++;
			}
			var col = new GridColumn("total_"+subject.parts[i].id, "Total", null, "right", "field_decimal", false, null, null, {can_be_null:true,max:parseFloat(subject.parts[i].max_score),integer_digits:10,decimal_digits:2});
			var dcol = new CustomDataGridColumn(col, function(applicant, part_id) {
				if (typeof applicants_results[applicant.people.id] == 'undefined') return null;
				if (typeof applicants_results[applicant.people.id][subject.id] == 'undefined') return null;
				if (typeof applicants_results[applicant.people.id][subject.id].parts[part_id] == 'undefined') return null;
				return applicants_results[applicant.people.id][subject.id].parts[part_id].score;
			}, true, subject.parts[i].id);
			cols.push(dcol);
			var part_container = new CustomDataGridColumnContainer("Part "+subject.parts[i].index + " - "+subject.parts[i].name, cols);
			this.data_grid.addColumnContainer(part_container);
		} else {
			var col = new GridColumn("total_"+subject.parts[i].id, "Part "+subject.parts[i].index + " - "+subject.parts[i].name, null, null, "field_decimal", true, null, null, {can_be_null:true,max:parseFloat(subject.parts[i].max_score),integer_digits:10,decimal_digits:2});
			var dcol = new CustomDataGridColumn(col, function(applicant, part_id) {
				if (typeof applicants_results[applicant.people.id] == 'undefined') return null;
				if (typeof applicants_results[applicant.people.id][subject.id] == 'undefined') return null;
				if (typeof applicants_results[applicant.people.id][subject.id].parts[part_id] == 'undefined') return null;
				return applicants_results[applicant.people.id][subject.id].parts[part_id].score;
			}, true, subject.parts[i].id);
			this.data_grid.addColumn(dcol);
		}
	}
	
	for (var i = 0; i < applicants.length; ++i)
		this.data_grid.addApplicant(applicants[i]);
	this.data_grid.grid.onallrowsready(function() {
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
	});
}

var edit_mode = document.getElementById('edit_mode');

function buildGrid() {
	var tabs_container = document.getElementById('tabs_container');
	tabs_container.removeAllChildren();
	subjects_grids = [];
	var subjects_tabs = new tabs(tabs_container, true);
	for (var i = 0; i < subjects.length; ++i) {
		var container = document.createElement("DIV");
		subjects_tabs.addTab(subjects[i].name, null, container);
		subjects_grids.push(new SubjectGrid(subjects[i], container, edit_mode.value));
	}
}
buildGrid();
</script>
<?php 
	}
}
?>