<?php 
class page_subject_grades extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		if (!isset($_GET["subject"])) {
			// direct from menu, we need to select a subject
			if (isset($_GET["batches"])) {
				echo "<div class='info_box'><img src='".theme::$icons_16["question"]."' style='vertical-align:bottom'/> Please select a batch, period or class to display the subjects</div>";
				return;
			}
			if (!isset($_GET["batch"])) {
				// in order to select a subject, we want to be inside the tree_frame
				header("Location: /dynamic/curriculum/page/tree_frame#/dynamic/transcripts/page/subject_grades");
				return;
			}
			$batch_id = $_GET["batch"];
			if (isset($_GET["class"])) {
				$class = PNApplication::$instance->curriculum->getAcademicClass($_GET["class"]);
				$period_id = $class["period"];
				$spe_id = $class["specialization"];
			} else if (isset($_GET["period"])) {
				$period_id = $_GET["period"];
				$spe_id = isset($_GET["specialization"]) ? $_GET["specialization"] : null;
			} else {
				$period_id = null;
				$spe_id = null;
			}
			$subjects = PNApplication::$instance->curriculum->getSubjects($batch_id, $period_id, $spe_id);
			if (!PNApplication::$instance->user_management->has_right("consult_students_grades")) {
				for ($i = 0; $i < count($subjects); $i++) {
					if (!PNApplication::$instance->curriculum->amIAssignedTo($subjects[$i]["id"])) {
						array_splice($subjects, $i, 1);
						$i--;
					}
				}
				if (count($subjects) == 0) {
					echo "<div class='info_box'>You are not assigned to any subject for ".($period_id <> null ? "this period" : "this batch")."</div>";
					return;
				}
			} else if (count($subjects) == 0) {
				echo "<div class='info_box'>No subject defined in the curriculum for ".($period_id <> null ? "this period" : "this batch")."</div>";
				return;
			}
			echo "<div class='info_box'>";
			echo "<img src='".theme::$icons_16["question"]."' style='vertical-align:bottom'/> ";
			echo "Please select a subject to display the grades: ";
			echo "<select onchange=\"if (this.value == '') return; window.parent.frameElement.src='/dynamic/transcripts/page/subject_grades?subject='+this.value";
			if (isset($_GET["class"])) echo "+'&class=".$_GET["class"]."'";
			echo ";\">";
			echo "<option value=''></option>";
			foreach ($subjects as $s) {
				echo "<option value='".$s["id"]."'>";
				echo toHTML($s["code"]." - ".$s["name"]);
				echo "</option>";
			}
			echo "</select>";
			echo "</div>";
			return;
		}
		
		// check access
		if (!PNApplication::$instance->user_management->has_right("consult_students_grades")) {
			if (!PNApplication::$instance->curriculum->amIAssignedTo($_GET["subject"])) {
				PNApplication::error("Access denied");
				return;
			}
		}

		// get subject
		$subject_id = $_GET["subject"];
		$q = PNApplication::$instance->curriculum->getSubjectQuery($subject_id);
		$q->join("CurriculumSubject", "CurriculumSubjectGrading", array("id"=>"subject"));
		$subject = $q->byPassSecurity()->executeSingleRow();
		
		$edit = false;
		$can_edit = PNApplication::$instance->user_management->has_right("edit_students_grades") || PNApplication::$instance->curriculum->amIAssignedTo($subject_id);
		$locker = null;
		$lock_id = null;
		if ($can_edit && isset($_GET["edit"])) {
			require_once("component/data_model/DataBaseLock.inc");
			$lock_id = DataBaseLock::lockRow("CurriculumSubjectGrading", $subject["id"], $locker, true);
			if ($lock_id <> null)
				$edit = true;
		}
		
		// get batch, period, class and specialization
		if (isset($_GET["class"])) {
			$class = PNApplication::$instance->curriculum->getAcademicClass($_GET["class"]);
			$period = PNApplication::$instance->curriculum->getBatchPeriod($class["period"]);
			$spe = $class["specialization"] <> null ? PNApplication::$instance->curriculum->getSpecialization($class["specialization"]) : null;
		} else {
			$class = null;
			$period = PNApplication::$instance->curriculum->getBatchPeriod($subject["period"]);
			$spe = $subject["specialization"] <> null ? PNApplication::$instance->curriculum->getSpecialization($subject["specialization"]) : null;
		}
		$batch = PNApplication::$instance->curriculum->getBatch($period["batch"]);
		
		// get teachers
		$teachers = PNApplication::$instance->curriculum->getTeachersAssignedTo($subject_id, $class <> null ? $class["id"] : null);

		// get evaluations
		if ($subject["only_final_grade"] == null || !$subject["only_final_grade"]) {
			$evaluation_types = SQLQuery::create()->byPassSecurity()
				->select("CurriculumSubjectEvaluationType")
				->where("subject", $subject_id)
				->execute();
			foreach ($evaluation_types as &$type) {
				$type["evaluations"] = SQLQuery::create()->byPassSecurity()
					->select("CurriculumSubjectEvaluation")
					->where("type", $type["id"])
					->execute();
			}
		} else
			$evaluation_types = array();
		$evaluation_types_ids = array();
		$evaluations_ids = array();
		foreach ($evaluation_types as &$type) {
			array_push($evaluation_types_ids, $type["id"]);
			foreach ($type["evaluations"] as $eval)
				array_push($evaluations_ids, $eval["id"]);
		}
		
		// get the list of students
		if ($class <> null) {
			$q = PNApplication::$instance->students->getStudentsQueryForClass($class["id"], true);
		} else {
			$q = PNApplication::$instance->students->getStudentsQueryForBatchPeriod($period["id"], true, false, $spe <> null ? $spe["id"] : false);
		}
		$students = $q->execute();
		$students_ids = array();
		foreach ($students as $s) array_push($students_ids, $s["people_id"]);

		// get students' grades
		$final_grades = array();
		if (count($students_ids) > 0)
			$final_grades = SQLQuery::create()->byPassSecurity()->select("StudentSubjectGrade")->whereValue("StudentSubjectGrade","subject",$subject["id"])->whereIn("StudentSubjectGrade","people",$students_ids)->execute();
		$students_eval_grade = array();
		if (count($evaluations_ids) > 0 && count($students_ids) > 0)
			$students_eval_grade = SQLQuery::create()->byPassSecurity()
				->select("StudentSubjectEvaluationGrade")
				->whereIn("StudentSubjectEvaluationGrade", "people", $students_ids)
				->whereIn("StudentSubjectEvaluationGrade", "evaluation", $evaluations_ids)
				->execute();
		
		// get all subjects and classes available to switch
		$all_subjects = PNApplication::$instance->curriculum->getSubjects($batch["id"], $period["id"], $spe <> null ? $spe["id"] : null);
		$all_classes = PNApplication::$instance->curriculum->getAcademicClasses($batch["id"], $period["id"], $subject["specialization"] == null ? false : $subject["specialization"]);
		
		// grading systems
		$grading_systems = include("component/transcripts/GradingSystems.inc");
		if (isset($_COOKIE["grading_system"]))
			$grading_system = $_COOKIE["grading_system"];
		else {
			$d = PNApplication::$instance->getDomainDescriptor();
			$grading_system = $d["transcripts"]["default_grading_system"];
		}
		
		require_once("component/curriculum/CurriculumJSON.inc");
		$this->requireJavascript("grid.js");
		$this->requireJavascript("custom_data_grid.js");
		$this->requireJavascript("people_data_grid.js");
		theme::css($this, "grid.css");
		if ($edit) {
			$this->requireJavascript("typed_field.js");
			$this->requireJavascript("field_decimal.js");
		}
		?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;background-color:white'>
	<div class='page_title' style='flex:none'>
		<img src='/static/transcripts/grades_32.png'/>
		Grades
		<div style='margin-left:10px;font-size:12pt;font-style:italic;display:inline-block;'>
		<a class='black_link' onclick='selectAnotherSubject(this);return false;' id='select_subject'>
		Subject <b style='font-weight:bold'>
		<?php echo toHTML($subject["code"]." - ".$subject["name"]);
		?></b></a>
		<?php
		echo " (";
		echo "Batch ".toHTML($batch["name"]);
		echo ", ".toHTML($period["name"]);
		if ($spe <> null) echo ", Specialization ".toHTML($spe["name"]);
		?>,
		<a class='black_link' onclick='selectAnotherClass(this);return false;' id='select_class'>
		<?php 
		if ($class <> null)
			echo "Class ".toHTML($class["name"]);
		else
			echo "All classes";
		?></a>
		)
		</div>
	</div>
	<?php if ($can_edit && !$edit && $locker <> null) {?>
	<div style='flex:none;'>
		<div class='info_box'>
			<img src='<?php echo theme::$icons_16["warning"];?>' style='vertical-align:bottom'/> You cannot edit the grades of this subject because <i><b><?php echo $locker;?></b></i> is currently editing them
		</div>
	</div>
	<?php } ?>
	<div style='flex:none;background-color:white;box-shadow: 1px 2px 5px 0px #808080;margin-bottom:5px;padding:5px'>
		Teacher<?php
		if (count($teachers) > 0) echo "s";
		echo ": ";
		for ($i = 0; $i < count($teachers); $i++) {
			if ($i > 0) echo ", ";
			echo toHTML($teachers[$i]["last_name"])." ".toHTML($teachers[$i]["first_name"]);
		} 
		?>
		<br/>
		Maximum grade
		<?php if ($edit) {?>
		<span id='max_grade_container'></span>
		<?php } else { ?>
		<span style='font-family:Courier New'><?php echo $subject["max_grade"] <> null ? $subject["max_grade"] : "<i>Not specified</i>";?></span>
		<?php } ?>
		<span style='margin-right: 10px'></span>
		Passing grade
		<?php if ($edit) {?>
		<span id='passing_grade_container'></span>
		<?php } else { ?>
		<span style='font-family:Courier New'><?php echo $subject["passing_grade"] <> null ? $subject["passing_grade"] : "<i>Not specified</i>";?></span>
		<?php } ?>
		<span style='margin-right: 10px'></span>
		Grading system <select onchange="changeGradingSystem(this.options[this.selectedIndex].text,this.value);">
		<?php
		foreach($grading_systems as $name=>$spec) {
			echo "<option value=\"".$spec."\"";
			if ($name == $grading_system) echo " selected='selected'";
			echo ">".toHTML($name)."</option>";
		}
		?>
		</select>
		<span style='margin-right: 10px'></span>
		<span style='white-space:nowrap'>
		<input type='checkbox'
		<?php if ($subject["only_final_grade"] == 1) echo " checked='checked'";?>
		<?php
		if ($edit) {
			echo " onchange=\"if (this.checked) switchToOnlyFinalGradeMode(); else switchToEvaluationsMode();\"";
		} else {
			echo " disabled='disabled'";
		} 
		?>
		/> Enter only final grade
		</span>
		<?php if ($edit) { ?>
		<span id='actions_evaluations' style='margin-left:5px;<?php if ($subject["only_final_grade"] == 1) echo "display:none;";?>'>
			<button class='action' onclick='newEvaluationType();'>New Evaluation Type</button>
			<button class='action' onclick='newEvaluation(this);'>New Evaluation</button>
		</span>
		<?php } ?>
	</div>
	<div style='flex:1 1 auto;overflow:auto;background-color:white' id='grades_container'>
	</div>
	<div class='page_footer' style='flex:none;'>
		<?php
		if (!$edit && $can_edit) {
			echo "<button class='action' onclick=\"var u = new window.URL(location.href);u.params.edit='true';location.href=u.toString();\">";
			echo "<img src='".theme::$icons_16["edit"]."'/> Edit";
			echo "</button>";
		} else if ($edit) {
			echo "<button class='action' onclick=\"window.onuserinactive();\">";
			echo "<img src='".theme::$icons_16["no_edit"]."'/> Cancel changes and stop editing";
			echo "</button>";
			echo " &nbsp; ";
			echo "<button id='save_button' class='action important' onclick=\"save();\">";
			echo "<img src='".theme::$icons_16["save"]."'/> Save";
			echo "</button>";
			echo " &nbsp; ";
			echo "<button id='import_button' class='action' onclick=\"importFromFile(event);\">";
			echo "<img src='".theme::$icons_16["_import"]."'/> Import Grades From File";
			echo "</button>";
		}
		?>
	</div>
</div>
<?php if ($lock_id <> null) DataBaseLock::generateScript($lock_id); ?>
<script type='text/javascript'>
window.onuserinactive = function() { window.pnapplication.cancelDataUnsaved();var u = new window.URL(location.href);delete u.params.edit;location.href=u.toString(); };
var subject_id = <?php echo $subject["id"];?>;
var subjects = <?php echo CurriculumJSON::SubjectsJSON($all_subjects);?>;
var classes = <?php echo CurriculumJSON::AcademicClassesJSON($all_classes);?>;
var students = <?php echo PeopleJSON::Peoples($students);?>;
var only_final = <?php echo $subject["only_final_grade"] == 1 ? "true" : "false";?>;
var original_only_final = only_final;
var subject_max_grade = <?php echo json_encode($subject["max_grade"]);?>;
var evaluation_types = <?php echo json_encode($evaluation_types); ?>;
var grading_system = <?php echo json_encode($grading_systems[$grading_system]);?>;
var final_grades = [<?php
$first = true; 
foreach ($final_grades as $g) {
	if ($first) $first = false; else echo ",";
	echo "{";
	echo "id:".$g["people"];
	echo ",grade:".($g["grade"] === null ? "null" : $g["grade"]);
	echo ",comment:".json_encode($g["comment"]);
	echo "}";
}
foreach ($students as $s) {
	$found = false;
	foreach ($final_grades as $g)
		if ($g["people"] == $s["people_id"]) { $found = true; break; }
	if (!$found) {
		if ($first) $first = false; else echo ",";
		echo "{";
		echo "id:".$s["people_id"];
		echo ",grade:null";
		echo ",comment:null";
		echo "}";
	}
}
?>];
var eval_grades = <?php echo json_encode($students_eval_grade); ?>;

function getPeople(people_id) {
	for (var i = 0; i < students.length; ++i)
		if (students[i].id == people_id)
			return students[i];
	return null;
}
function getFinalGrade(people_id) {
	for (var i = 0; i < final_grades.length; ++i)
		if (final_grades[i].id == people_id)
			return final_grades[i];
	return null;
}
function getEvaluationGrade(people_id, eval_id) {
	for (var i = 0; i < eval_grades.length; ++i)
		if (eval_grades[i].people == people_id && eval_grades[i].evaluation == eval_id)
			return parseFloat(eval_grades[i].grade);
	return null;
}
function getEvaluationTypeGrade(people_id, eval_type_id) {
	var total = 0;
	var total_coef = 0;
	for (var i = 0; i < evaluation_types.length; ++i) {
		var type = evaluation_types[i];
		if (type.id != eval_type_id) continue;
		for (var j = 0; j < type.evaluations.length; ++j) {
			var grade = getEvaluationGrade(people_id, type.evaluations[j].id);
			if (grade === null) continue;
			total += grade*parseFloat(type.evaluations[j].weight);
			total_coef += parseFloat(type.evaluations[j].weight);
		}
	}
	if (total_coef == 0) return null;
	return total/total_coef;
}

function setStudentGrade(people_id, eval_id, grade) {
	for (var i = 0; i < eval_grades.length; ++i)
		if (eval_grades[i].people == people_id && eval_grades[i].evaluation == eval_id) {
			eval_grades[i].grade = grade;
			return;
		}
	eval_grades.push({
		people: people_id,
		evaluation: eval_id,
		grade: grade
	});
}

function computeStudentGrades(people_id) {
	// get student grades
	var grades = [];
	for (var i = 0; i < eval_grades.length; ++i) {
		if (eval_grades[i].people != people_id) continue;
		grades.push({eval_id:eval_grades[i].evaluation,grade:eval_grades[i].grade});
	}
	// compute evaluation types
	var types_grades = [];
	for (var i = 0; i < evaluation_types.length; ++i) {
		var type_coef = 0;
		var type_grade = 0;
		for (var j = 0; j < evaluation_types[i].evaluations.length; ++j) {
			var max = parseFloat(evaluation_types[i].evaluations[j].max_grade);
			var coef = parseInt(evaluation_types[i].evaluations[j].weight);
			if (!max || !coef || isNaN(max) || isNaN(coef)) continue;
			var grade = null;
			for (var k = 0; k < grades.length; ++k) if (grades[k].eval_id == evaluation_types[i].evaluations[j].id) { grade = grades[k].grade; break; }
			if (grade != null) {
				type_coef += coef;
				type_grade += grade*coef;
			}
		}
		if (type_coef == 0)
			types_grades.push(null);
		else
			types_grades.push(type_grade/type_coef);
	}
	// compute final
	var total_coef = 0;
	var total_grade = 0;
	for (var i = 0; i < evaluation_types.length; ++i) {
		var coef = parseInt(evaluation_types[i].weight);
		if (!coef || isNaN(coef)) continue;
		if (types_grades[i] === null) continue;
		total_coef += coef;
		total_grade += types_grades[i]*coef;
	}
	var final_grade = total_coef > 0 ? total_grade/total_coef : null;
	var found = false;
	for (var i = 0; i < final_grades.length; ++i)
		if (final_grades[i].id == people_id) { found = true; final_grades[i].grade = final_grade; break; }
	if (!found) final_grades.push({id:people_id,grade:final_grade});
	// put values in grid
	for (var i = 0; i < evaluation_types.length; ++i) {
		var field = grades_grid.grid.getCellFieldById(people_id, 'total_eval_type_'+evaluation_types[i].id);
		if (!field) continue;
		field.setData(types_grades[i]);
	}
	var field = grades_grid.grid.getCellFieldById(people_id, 'final_grade');
	if (field) field.setData(final_grade);
}

function computeGrades() {
	for (var i = 0; i < students.length; ++i)
		computeStudentGrades(students[i].id);
}

tooltip(document.getElementById('select_subject'), "Click to select another subject");
tooltip(document.getElementById('select_class'), "Click to select another class");

var grades_grid = new people_data_grid('grades_container', function(people_id) { return getPeople(people_id); }, "Student");
grades_grid.grid.element.style.marginLeft = "5px";
grades_grid.grid.table.parentNode.style.width = "";
grades_grid.addPeopleProfileAction();

<?php if ($edit) {?>
pnapplication.autoDisableSaveButton(document.getElementById('save_button'));

var field_max_grade = new field_decimal(<?php echo json_encode($subject["max_grade"]);?>,true,{integer_digits:3,decimal_digits:2,can_be_null:false,min:1,max:100});
var field_passing_grade = new field_decimal(<?php echo json_encode($subject["passing_grade"]);?>,true,{integer_digits:3,decimal_digits:2,can_be_null:false,min:1,max:<?php if ($subject["max_grade"] <> null) echo $subject["max_grade"]; else echo "100";?>});

field_max_grade.ondatachanged.add_listener(function() {window.pnapplication.dataUnsaved("subject_max_grade");});
field_max_grade.ondataunchanged.add_listener(function() {window.pnapplication.dataSaved("subject_max_grade");});
field_max_grade.onchange.add_listener(function() {
	field_passing_grade.config.max = parseFloat(field_max_grade.getCurrentData());
	field_passing_grade.validate();
	subject_max_grade = field_max_grade.getCurrentData();
	// refresh final grades
	var col_index = grades_grid.grid.getColumnIndexById('final_grade');
	for (var row = 0; row < grades_grid.grid.getNbRows(); ++row) {
		var field = grades_grid.grid.getCellField(row, col_index);
		field.config.max = field_passing_grade.config.max;
		field._setData(field.getCurrentData());
		field.validate();
	}
});
document.getElementById('max_grade_container').appendChild(field_max_grade.getHTMLElement());
field_passing_grade.ondatachanged.add_listener(function() {window.pnapplication.dataUnsaved("subject_passing_grade");});
field_passing_grade.ondataunchanged.add_listener(function() {window.pnapplication.dataSaved("subject_passing_grade");});
field_passing_grade.onchange.add_listener(function() {
	// refresh final grades
	var col_index = grades_grid.grid.getColumnIndexById('final_grade');
	for (var row = 0; row < grades_grid.grid.getNbRows(); ++row) {
		var field = grades_grid.grid.getCellField(row, col_index);
		field.config.passing = parseFloat(field_passing_grade.getCurrentData());
		field._setData(field.getCurrentData());
		field.validate();
	}
});
document.getElementById('passing_grade_container').appendChild(field_passing_grade.getHTMLElement());
<?php }?>

function selectAnotherSubject(link) {
	var w=window;
	require("context_menu.js", function() {
		var menu = new context_menu();
		for (var i = 0; i < subjects.length; ++i)
			menu.addIconItem(null, subjects[i].code+" - "+subjects[i].name, function(ev,p) {
				w.location.href = "/dynamic/transcripts/page/subject_grades?subject="+p.subject_id+(typeof p.class_id != 'undefined' ? "&class="+p.class_id : "");
			}, {subject_id:subjects[i].id<?php if ($class <> null) echo ",class_id:".$class["id"];?>});
		menu.showBelowElement(link);
	});
}
function selectAnotherClass(link) {
	var w=window;
	require("context_menu.js", function() {
		var menu = new context_menu();
		for (var i = 0; i < classes.length; ++i)
			menu.addIconItem(null, classes[i].name, function(ev,class_id) {
				w.location.href = "/dynamic/transcripts/page/subject_grades?subject=<?php echo $subject["id"];?>&class="+class_id;
			}, classes[i].id);
		menu.addIconItem(null, "All classes", function() {
			w.location.href = "/dynamic/transcripts/page/subject_grades?subject=<?php echo $subject["id"];?>";
		});
		menu.showBelowElement(link);
	});
}

function changeGradingSystem(name, system) {
	setCookie("grading_system",name,365*24*60,"/dynamic/transcripts/page/");
	grading_system = system;
	// refresh all grades
	for (var i = 0; i < grades_grid.grid.columns.length; ++i) {
		var col = grades_grid.grid.columns[i];
		if (col.field_type != "field_grade") continue;
		//if (col.id.startsWith("total_eval_type_")) continue;
		for (var row = 0; row < grades_grid.grid.getNbRows(); ++row) {
			var field = grades_grid.grid.getCellField(row, i);
			field.setGradingSystem(system);
		}
	}
}

function createEvaluation(type, eval) {
	var div = document.createElement("DIV");
	div.style.display = "inline-block";
	div.style.textAlign = "center";
	<?php if ($edit) { ?>
	div.style.cursor = "pointer";
	div.title = "Click to edit or remove this evaluation";
	div.onclick = function() {
		require("context_menu.js",function() {
			var menu = new context_menu();
			menu.addTitleItem(null, "Evalutation: "+eval.name);
			menu.addIconItem(theme.icons_16.edit, "Edit", function() {
				evaluationDialog(type, eval, false);
			});
			menu.addIconItem(theme.icons_16.remove, "Remove", function() {
				type.evaluations.remove(eval);
				grades_grid.removeColumn('eval_'+eval.id);
				pnapplication.dataUnsaved("evaluations");
				for (var i = 0; i < eval_grades.length; ++i)
					if (eval_grades[i].evaluation == eval.id) {
						eval_grades.splice(i,1);
						i--;
					}
				computeGrades();
			});
			menu.showBelowElement(div);
		});
	};
	<?php } ?>
	eval.div_name = document.createElement("DIV");
	div.appendChild(eval.div_name);
	eval.div_name.appendChild(document.createTextNode(eval.name));
	eval.div_max = document.createElement("DIV");
	eval.div_max.style.fontWeight = "normal";
	eval.div_max.appendChild(document.createTextNode("Max. "+eval.max_grade));
	div.appendChild(eval.div_max);
	eval.div_coef = document.createElement("DIV");
	eval.div_coef.style.fontWeight = "normal";
	eval.div_coef.appendChild(document.createTextNode("Coef. "+eval.weight));
	div.appendChild(eval.div_coef);
	eval.col = new CustomDataGridColumn(new GridColumn('eval_'+eval.id, div, null, null, "field_grade", <?php if ($edit) echo "true,evalGradeChanged,evalGradeUnchanged"; else echo "false,null,null";?>, {max:eval.max_grade,passing:1,system:grading_system}, eval), function(people_id) {
		return getEvaluationGrade(people_id, eval.id);
	}, true, null, eval.name);
	var update_passing = function() {
		<?php if ($edit) { ?>
		var max = field_max_grade.getCurrentData();
		var passing = field_passing_grade.getCurrentData();
		<?php } else { ?>
		var max = <?php echo json_encode($subject["max_grade"]);?>;
		var passing = <?php echo json_encode($subject["passing_grade"]);?>;
		<?php } ?>
		passing = passing*eval.max_grade/max;
		eval.col.grid_column.field_args.passing = passing;
		var col_index = grades_grid.grid.getColumnIndexById('eval_'+eval.id);
		for (var row = 0; row < grades_grid.grid.getNbRows(); ++row) {
			var field = grades_grid.grid.getCellField(row, col_index);
			field.setMaxAndPassingGrades(eval.max_grade, passing);
		}
		computeGrades();
	};
	<?php if ($edit) { ?>
	field_max_grade.onchange.add_listener(update_passing);
	field_passing_grade.onchange.add_listener(update_passing);
	<?php } ?>
	grades_grid.addColumnInContainer(type.col_container,eval.col,type.col_container.sub_columns.length-1);
	update_passing();
}

function createEvaluationType(eval) {
	var next_col = grades_grid.getColumnById('final_grade');
	var cols = [];
	eval.col_total = new CustomDataGridColumn(new GridColumn('total_eval_type_'+eval.id, "Total", null, null, "field_grade", false, null, null, {max:100,passing:50,system:grading_system}), function(people_id) {
		return getEvaluationTypeGrade(people_id, eval.id);
	}, true);
	var update_passing = function() {
		<?php if ($edit) { ?>
		var max = field_max_grade.getCurrentData();
		var passing = field_passing_grade.getCurrentData();
		<?php } else { ?>
		var max = <?php echo json_encode($subject["max_grade"]);?>;
		var passing = <?php echo json_encode($subject["passing_grade"]);?>;
		<?php } ?>
		passing = passing*100/max;
		eval.col_total.grid_column.field_args.passing = passing;
		var col_index = grades_grid.grid.getColumnIndexById('total_eval_type_'+eval.id);
		for (var row = 0; row < grades_grid.grid.getNbRows(); ++row) {
			var field = grades_grid.grid.getCellField(row, col_index);
			field.setMaxAndPassingGrades(100, passing);
		}
		computeGrades();
	};
	<?php if ($edit) { ?>
	field_max_grade.onchange.add_listener(update_passing);
	field_passing_grade.onchange.add_listener(update_passing);
	<?php } ?>
	cols.push(eval.col_total);
	var div = document.createElement("DIV");
	div.style.display = "inline-block";
	<?php if ($edit) { ?>
	div.style.cursor = "pointer";
	div.title = "Click to edit or remove this evaluation type";
	div.onclick = function() {
		require("context_menu.js",function() {
			var menu = new context_menu();
			menu.addTitleItem(null, "Evalutation Type: "+eval.name);
			menu.addIconItem(theme.icons_16.edit, "Edit", function() {
				evaluationTypeDialog(eval, false);
			});
			menu.addIconItem(theme.icons_16.remove, "Remove", function() {
				evaluation_types.remove(eval);
				grades_grid.removeColumnContainer(eval.col_container);
				pnapplication.dataUnsaved("evaluations_types");
				for (var i = 0; i < eval_grades.length; ++i) {
					var found = false;
					for (var j = 0; j < eval.evaluations.length; ++j) if (eval.evaluations[j].id == eval_grades[i].evaluation) { found = true; break; }
					if (found) {
						eval_grades.splice(i,1);
						i--;
					}
				}
				computeGrades();
			});
			menu.showBelowElement(div);
		});
	};
	<?php } ?>
	eval.div_name = document.createElement("DIV");
	div.appendChild(eval.div_name);
	eval.div_name.appendChild(document.createTextNode(eval.name));
	eval.div_coef = document.createElement("DIV");
	eval.div_coef.style.fontWeight = "normal";
	eval.div_coef.appendChild(document.createTextNode("Coef. "+eval.weight));
	div.appendChild(eval.div_coef);
	eval.col_container = new CustomDataGridColumnContainer(div, cols, eval.name);
	grades_grid.addColumnContainer(eval.col_container, grades_grid.columns.indexOf(next_col));
	update_passing();
	for (var i = 0; i < eval.evaluations.length; ++i)
		createEvaluation(eval, eval.evaluations[i]);
}

<?php if ($edit) {?>

function switchToOnlyFinalGradeMode() {
	only_final = true;
	if (only_final != original_only_final) pnapplication.dataUnsaved("only_final"); else pnapplication.dataSaved("only_final");
	var col = grades_grid.grid.getColumnById("final_grade");
	col.toggleEditable();
	document.getElementById('actions_evaluations').style.display = "none";
	if (evaluation_types.length > 0) {
		pnapplication.dataUnsaved("evaluations");
		for (var i = 0; i < evaluation_types.length; ++i)
			grades_grid.removeColumnContainer(evaluation_types[i].col_container);
	}
	eval_grades = [];
}

function switchToEvaluationsMode() {
	only_final = false;
	if (only_final != original_only_final) pnapplication.dataUnsaved("only_final"); else pnapplication.dataSaved("only_final");
	var col = grades_grid.grid.getColumnById("final_grade");
	col.toggleEditable();
	document.getElementById('actions_evaluations').style.display = "";
	for (var i = 0; i < evaluation_types.length; ++i)
		createEvaluationType(evaluation_types[i]);
	computeGrades();
}

function finalGradeChanged(field) {
	var people_id = field.getHTMLElement().parentNode.parentNode.row_id;
	pnapplication.dataUnsaved("final_grade_student_"+people_id);
	getFinalGrade(people_id).grade = field.getCurrentData();
}
function finalGradeUnchanged(field) {
	var people_id = field.getHTMLElement().parentNode.parentNode.row_id;
	pnapplication.dataSaved("final_grade_student_"+people_id);
	getFinalGrade(people_id).grade = field.getCurrentData();
}
function commentChanged(field) {
	var people_id = field.getHTMLElement().parentNode.parentNode.row_id;
	pnapplication.dataUnsaved("comment_student_"+people_id);
	getFinalGrade(people_id).comment = field.getCurrentData();
}
function commentUnchanged(field) {
	var people_id = field.getHTMLElement().parentNode.parentNode.row_id;
	pnapplication.dataSaved("comment_student_"+people_id);
	getFinalGrade(people_id).comment = field.getCurrentData();
}
function evalGradeChanged(field) {
	var col_row = grades_grid.grid.getContainingRowAndColIds(field.getHTMLElement());
	if (!col_row) return;
	var col = grades_grid.grid.getColumnById(col_row.col_id);
	var eval = col.attached_data;
	var people_id = col_row.row_id;
	setStudentGrade(people_id, eval.id, field.getCurrentData());
	computeStudentGrades(people_id);
	pnapplication.dataUnsaved('student_'+people_id+'_grade_'+eval.id);
}
function evalGradeUnchanged(field) {
	var col_row = grades_grid.grid.getContainingRowAndColIds(field.getHTMLElement());
	if (!col_row) return;
	var col = grades_grid.grid.getColumnById(col_row.col_id);
	var eval = col.attached_data;
	var people_id = col_row.row_id;
	setStudentGrade(people_id, eval.id, field.getCurrentData());
	computeStudentGrades(people_id);
	pnapplication.dataSaved('student_'+people_id+'_grade_'+eval.id);
}

function evaluationTypeDialog(eval,is_new) {
	var content = document.createElement("DIV");
	content.style.backgroundColor = "white";
	content.style.padding = "10px";
	var table = document.createElement("TABLE");
	content.appendChild(table);
	var tr, td;
	table.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TD"));
	td.innerHTML = "Type of evaluation";
	tr.appendChild(td = document.createElement("TD"));
	var input_name = document.createElement("INPUT");
	input_name.type = "text";
	input_name.value = eval.name;
	td.appendChild(input_name);
	table.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TD"));
	td.innerHTML = "Coefficient";
	tr.appendChild(td = document.createElement("TD"));
	var input_coef = document.createElement("INPUT");
	input_coef.type = "text";
	input_coef.value = eval.weight;
	td.appendChild(input_coef);
	require("popup_window.js",function() {
		var popup = new popup_window("Evaluation Type", null, content);
		popup.addOkCancelButtons(function() {
			var name = input_name.value.trim();
			if (name.length == 0) { alert("Please enter a name"); return; }
			var coef = input_coef.value.trim();
			if (coef.length == 0) { alert("Please enter a coefficient"); return; }
			coef = coef.parseNumber();
			if (isNaN(coef) || coef <= 0) { alert("Please enter a valid coefficient"); return; }
			if (name != eval.name || coef != eval.weight) {
				pnapplication.dataUnsaved("evaluations_types");
				eval.name = name;
				eval.weight = coef;
				if (is_new) {
					evaluation_types.push(eval);
					createEvaluationType(eval);
				} else {
					eval.div_name.removeAllChildren();
					eval.div_name.appendChild(document.createTextNode(name));
					layout.invalidate(eval.div_name);
					eval.div_coef.removeAllChildren();
					eval.div_coef.appendChild(document.createTextNode("Coef. "+coef));
					layout.invalidate(eval.div_coef);
					eval.col_container.select_menu_name = eval.name;
					computeGrades();
				}
			}
			popup.close();
		});
		popup.show();
	});
}

function evaluationDialog(type,eval,is_new) {
	var content = document.createElement("DIV");
	content.style.backgroundColor = "white";
	content.style.padding = "10px";
	var table = document.createElement("TABLE");
	content.appendChild(table);
	var tr, td;
	table.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TD"));
	td.innerHTML = "Type of the evaluation";
	tr.appendChild(td = document.createElement("TD"));
	td.appendChild(document.createTextNode(type.name));
	table.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TD"));
	td.innerHTML = "Name of the evaluation";
	tr.appendChild(td = document.createElement("TD"));
	var input_name = document.createElement("INPUT");
	input_name.type = "text";
	input_name.value = eval.name;
	td.appendChild(input_name);
	table.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TD"));
	td.innerHTML = "Maximum Grade";
	tr.appendChild(td = document.createElement("TD"));
	var input_max = document.createElement("INPUT");
	input_max.type = "text";
	input_max.value = eval.max_grade;
	td.appendChild(input_max);
	table.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TD"));
	td.innerHTML = "Coefficient";
	tr.appendChild(td = document.createElement("TD"));
	var input_coef = document.createElement("INPUT");
	input_coef.type = "text";
	input_coef.value = eval.weight;
	td.appendChild(input_coef);
	require("popup_window.js",function() {
		var popup = new popup_window("Evaluation Type", null, content);
		popup.addOkCancelButtons(function() {
			var name = input_name.value.trim();
			if (name.length == 0) { alert("Please enter a name"); return; }
			var max = input_max.value.trim();
			if (max.length == 0) { alert("Please enter a maximum grade"); return; }
			max = parseFloat(max);
			if (isNaN(max) || max <= 0) { alert("Please enter a valid grade"); return; }
			var coef = input_coef.value.trim();
			if (coef.length == 0) { alert("Please enter a coefficient"); return; }
			coef = coef.parseNumber();
			if (isNaN(coef) || coef <= 0) { alert("Please enter a valid coefficient"); return; }
			if (name != eval.name || coef != eval.weight || max != eval.max_grade) {
				pnapplication.dataUnsaved("evaluations");
				eval.name = name;
				eval.weight = coef;
				eval.max_grade = max;
				if (is_new) {
					type.evaluations.push(eval);
					createEvaluation(type, eval);
				} else {
					eval.div_name.removeAllChildren();
					eval.div_name.appendChild(document.createTextNode(name));
					layout.invalidate(eval.div_name);
					eval.div_max.removeAllChildren();
					eval.div_max.appendChild(document.createTextNode("Max. "+max));
					layout.invalidate(eval.div_max);
					eval.div_coef.removeAllChildren();
					eval.div_coef.appendChild(document.createTextNode("Coef. "+coef));
					layout.invalidate(eval.div_coef);
					eval.col.select_menu_name = eval.name;
					computeGrades();
				}
			}
			popup.close();
		});
		popup.show();
	});
}

var new_evaluation_type_id = -1;
function newEvaluationType() {
	evaluationTypeDialog({id:new_evaluation_type_id--,name:"",weight:1,evaluations:[]},true);
	pnapplication.dataUnsaved("evaluations_types");
}

var new_evaluation_id = -1;
function newEvaluation(button) {
	require("context_menu.js",function() {
		var menu = new context_menu();
		if (evaluation_types.length == 0)
			menu.addIconItem(theme.icons_16.error, "You need to create an evaluation type first");
		else {
			menu.addTitleItem(null, "Which Evaluation Type ?");
			for (var i = 0; i < evaluation_types.length; ++i) {
				menu.addIconItem(null, evaluation_types[i].name, function(ev,type) {
					evaluationDialog(type,{id:new_evaluation_id--,name:"",max_grade:100,weight:1},true);
				}, evaluation_types[i]);
			}
		}
		menu.showBelowElement(button);
	});
}

function save() {
	if (field_max_grade.error != null) { alert("Please enter a valid maximum grade"); return; }
	if (field_passing_grade.error != null) { alert("Please enter a valid passing grade"); return; }
	var locker = lock_screen(null, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Saving...");
	var save_final_grades = function() {
		if (!pnapplication.hasDataUnsavedStartingWith("final_grade_student_")) {
			unlock_screen(locker);
			return;
		}
		var data = {subject_id:subject_id,students:[]};
		for (var i = 0; i < final_grades.length; ++i) {
			if (!pnapplication.hasDataUnsaved("final_grade_student_"+final_grades[i].id)) continue;
			data.students.push({people:final_grades[i].id,final_grade:final_grades[i].grade});
		}
		service.json("transcripts","save_students_final_grade",data,function(res) {
			if (res) {
				for (var i = 0; i < final_grades.length; ++i)
					pnapplication.dataSaved("final_grade_student_"+final_grades[i].id);
				pnapplication.cancelDataUnsaved();
			}
			unlock_screen(locker);
		});
	};
	var save_evaluations_grades = function() {
		var data = {subject_id:<?php echo $subject_id;?>,students:[]};
		for (var i = 0; i < eval_grades.length; ++i) {
			var student = null;
			for (var j = 0; j < data.students.length; ++j) if (data.students[j].people == eval_grades[i].people) { student = data.students[j]; break; }
			if (!student) {
				student = {people:eval_grades[i].people,grades:[]};
				data.students.push(student);
			}
			student.grades.push({evaluation:eval_grades[i].evaluation,grade:eval_grades[i].grade});
		}
		service.json("transcripts","save_students_evaluations_grades",data,function(res){
			if (res) {
				for (var i = 0; i < data.students.length; ++i)
					for (var j = 0; j < data.students[i].grades.length; ++j)
						pnapplication.dataSaved("student_"+data.students[i].people+"_grade_"+data.students[i].grades[j].evaluation);
			}
			pnapplication.cancelDataUnsaved();
			unlock_screen(locker);
		});
	};
	var save_evaluations = function() {
		var data = { subject_id: <?php echo $subject_id;?>, types: []};
		for (var i = 0; i < evaluation_types.length; ++i) {
			var type = {
				id: evaluation_types[i].id,
				name: evaluation_types[i].name,
				weight: evaluation_types[i].weight,
				evaluations: []
			};
			for (var j = 0; j < evaluation_types[i].evaluations.length; ++j) {
				var eval = {
					id: evaluation_types[i].evaluations[j].id,
					name: evaluation_types[i].evaluations[j].name,
					weight: evaluation_types[i].evaluations[j].weight,
					max_grade: evaluation_types[i].evaluations[j].max_grade
				};
				type.evaluations.push(eval);
			}
			data.types.push(type);
		}
		service.json("transcripts","save_subject_evaluations",data,function(res) {
			if (!res) { unlock_screen(locker); return; }
			pnapplication.dataSaved("evaluations_types");
			pnapplication.dataSaved("evaluations");
			// update ids of evaluation types
			for (var j = 0; j < evaluation_types.length; ++j)
				if (evaluation_types[j].id < 0)
					for (var i = 0; i < res.types.length; ++i)
						if (evaluation_types[j].id == res.types[i].input_id) {
							evaluation_types[j].id = res.types[i].output_id;
							var col = grades_grid.grid.getColumnById('total_eval_type_'+res.types[i].input_id);
							col.setId('total_eval_type_'+res.types[i].output_id);
							break;
						}
			// update evaluations' ids
			for (var i = 0; i < evaluation_types.length; ++i)
				for (var j = 0; j < evaluation_types[i].evaluations.length; ++j)
					if (evaluation_types[i].evaluations[j].id < 0)
						for (var k = 0; k < res.evaluations.length; ++k)
							if (res.evaluations[k].input_id == evaluation_types[i].evaluations[j].id) { evaluation_types[i].evaluations[j].id = res.evaluations[k].output_id; break; }
			// update evaluations' ids in students grades
			for (var i = 0; i < eval_grades.length; ++i)
				if (eval_grades[i].evaluation < 0)
					for (var k = 0; k < res.evaluations.length; ++k)
						if (eval_grades[i].evaluation == res.evaluations[k].input_id) { eval_grades[i].evaluation = res.evaluations[k].output_id; break; }
			// update evaluations' ids in the columns ids
			for (var i = 0; i < grades_grid.grid.columns.length; ++i) {
				var col = grades_grid.grid.columns[i];
				if (!col.id.startsWith("eval_")) continue;
				for (var k = 0; k < res.evaluations.length; ++k)
					if (col.id == "eval_"+res.evaluations[k].input_id) { col.setId("eval_"+res.evaluations[k].output_id); break; }
			}
			// save students' grades
			save_evaluations_grades();
		});
	};
	var save_grades = function() {
		if (only_final)
			save_final_grades();
		else
			save_evaluations();
	};
	var save_comments = function() {
		if (!pnapplication.hasDataUnsavedStartingWith("comment_student_")) {
			save_grades();
			return;
		}
		var data = {subject_id:subject_id,students:[]};
		for (var i = 0; i < final_grades.length; ++i)
			if (pnapplication.hasDataUnsaved("comment_student_"+final_grades[i].id))
				data.students.push({people:final_grades[i].id,comment:final_grades[i].comment});
		service.json("transcripts","save_subject_comments",data,function(res) {
			if (!res) {
				unlock_screen(locker);
				return;
			}
			pnapplication.dataSavedStartingWith("comment_student_");
			save_grades();
		});
	};
	var save_subject_grading = function() {
		if (pnapplication.isDataUnsaved("subject_max_grade") || pnapplication.isDataUnsaved("subject_passing_grade") || pnapplication.isDataUnsaved("only_final")) {
			service.json("transcripts","save_subject_grading_info",{id:subject_id,only_final_grade:only_final,max_grade:field_max_grade.getCurrentData(),passing_grade:field_passing_grade.getCurrentData()},function(res) {
				if (!res) {
					unlock_screen(locker);
					return;
				}
				pnapplication.dataSaved("subject_max_grade");
				pnapplication.dataSaved("subject_passing_grade");
				pnapplication.dataSaved("only_final");
				save_comments();
			});
			return;
		}
		save_comments();
	};
	save_subject_grading();
}

function importFromFile(event) {
	if (grades_grid.grid._import_with_match) return;
	require("import_with_match.js",function() {
		var prov = new import_with_match_provider_custom_data_grid(grades_grid);
		prov.getColumnsCanBeMatched = function() {
			var cols = [];
			var gcols = grades_grid.getAllFinalColumns();
			for (var i = 0; i < gcols.length; ++i) {
				if (!gcols[i].shown) continue;
				var id = gcols[i].grid_column.id;
				if (id == 'final_grade') continue;
				if (id.startsWith("total_eval_type_")) continue;
				if (id.startsWith("eval_")) continue;
				cols.push({ id: id, name: gcols[i].select_menu_name ? gcols[i].select_menu_name : gcols[i].grid_column.title });
			}
			return cols;
		};
		prov.getColumnsCanBeImported = function() {
			if (only_final)
				return [{id:'final_grade',name:"Final Grade"}];
			var cols = [];
			for (var i = 0; i < evaluation_types.length; ++i)
				for (var j = 0; j < evaluation_types[i].evaluations.length; ++j)
					cols.push({id:'eval_'+evaluation_types[i].evaluations[j].id,name:evaluation_types[i].evaluations[j].name});
			return cols;
		};
		new import_with_match(prov, event, true);
	});
}

<?php } ?> // if editable mode

// init grid

grades_grid.addColumn(new CustomDataGridColumn(new GridColumn("final_grade","Final Grade", 43, "center", "field_grade",<?php echo $edit && $subject["only_final_grade"] == 1 ? "true" : "false";?>,<?php echo $edit ? "finalGradeChanged" : "null";?>,<?php echo $edit ? "finalGradeUnchanged" : "null";?>,{max:<?php echo json_encode($subject["max_grade"]);?>,passing:<?php echo json_encode($subject["passing_grade"]);?>,system:grading_system}), function(people_id){ return getFinalGrade(people_id).grade; }, true));
for (var i = 0; i < evaluation_types.length; ++i)
	createEvaluationType(evaluation_types[i]);
grades_grid.addColumn(new CustomDataGridColumn(new GridColumn("student_comment","Comment",null,"left","field_text",<?php echo $edit ? "true" : "false";?>,<?php echo $edit ? "commentChanged" : "null";?>,<?php echo $edit ? "commentUnchanged" : "null";?>,{can_be_null:true,max_length:4000,min_size:30}), function(people_id) {
	var g = getFinalGrade(people_id);
	if (!g) return null;
	return g.comment;
}, true, null));

for (var i = 0; i < students.length; ++i)
	grades_grid.addObject(students[i].id);

</script>
		<?php 
	}
	
}
?>