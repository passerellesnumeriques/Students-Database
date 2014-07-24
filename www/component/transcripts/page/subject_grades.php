<?php 
class page_subject_grades extends Page {
	
	public function getRequiredRights() { return array("consult_students_grades"); }
	
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
			echo "<div class='info_box'>";
			echo "<img src='".theme::$icons_16["question"]."' style='vertical-align:bottom'/> ";
			echo "Please select a subject to display the grades: ";
			echo "<select onchange=\"if (this.value == '') return; window.parent.frameElement.src='/dynamic/transcripts/page/subject_grades?subject='+this.value";
			if (isset($_GET["class"])) echo "+'&class=".$_GET["class"]."'";
			echo ";\">";
			echo "<option value=''></option>";
			foreach ($subjects as $s) {
				echo "<option value='".$s["id"]."'>";
				echo htmlentities($s["code"]." - ".$s["name"]);
				echo "</option>";
			}
			echo "</select>";
			echo "</div>";
			return;
		}

		// get subject
		$subject_id = $_GET["subject"];
		$q = PNApplication::$instance->curriculum->getSubjectQuery($subject_id);
		$q->join("CurriculumSubject", "CurriculumSubjectGrading", array("id"=>"subject"));
		$subject = $q->executeSingleRow();
		
		$edit = false;
		$can_edit = PNApplication::$instance->user_management->has_right("edit_students_grades"); // TODO teacher assigned
		$locker = null;
		if ($can_edit && isset($_GET["edit"])) {
			require_once("component/data_model/DataBaseLock.inc");
			$lock_id = DataBaseLock::lockRow("CurriculumSubjectGrading", $subject["id"], $locker);
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
		$final_grades = SQLQuery::create()->select("StudentSubjectGrade")->whereValue("StudentSubjectGrade","subject",$subject["id"])->whereIn("StudentSubjectGrade","people",$students_ids)->execute();
		
		// get all subjects and classes available to switch
		$all_subjects = PNApplication::$instance->curriculum->getSubjects($batch["id"], $period["id"], $spe <> null ? $spe["id"] : null);
		$all_classes = PNApplication::$instance->curriculum->getAcademicClasses($batch["id"], $period["id"], $spe <> null ? $spe["id"] : null);
		
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
		<span style='margin-left:10px;font-size:12pt;font-style:italic;'>
		<a class='black_link' onclick='selectAnotherSubject(this);return false;' id='select_subject'>
		Subject <b style='font-weight:bold'>
		<?php echo htmlentities($subject["code"]." - ".$subject["name"]);
		?></b></a>
		<?php
		echo " (";
		echo "Batch ".htmlentities($batch["name"]);
		echo ", ".htmlentities($period["name"]);
		if ($spe <> null) echo ", Specialization ".htmlentities($spe["name"]);
		?>,
		<a class='black_link' onclick='selectAnotherClass(this);return false;' id='select_class'>
		<?php 
		if ($class <> null)
			echo "Class ".htmlentities($class["name"]);
		else
			echo "All classes";
		?></a>
		)
		</span>
	</div>
	<?php if ($can_edit && !$edit && $locker <> null) {?>
	<div style='flex:none;'>
		<div class='info_box'>
			<img src='<?php echo theme::$icons_16["warning"];?>' style='vertical-align:bottom'/> You cannot edit the grades of this subject because <i><b><?php echo $locker;?></b></i> is currently editing them
		</div>
	</div>
	<?php } ?>
	<div style='flex:none;background-color:white;box-shadow: 1px 2px 5px 0px #808080;margin-bottom:5px;padding:5px'>
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
			echo ">".htmlentities($name)."</option>";
		}
		?>
		</select>
		<span style='margin-right: 10px'></span>
		<input type='checkbox'
		<?php if ($subject["only_final_grade"] == 1) echo " checked='checked'";?>
		<?php
		if ($edit) {
			echo " onchange=\"if (this.checked) switchToOnlyFinalGradeMode(); else switchToEvaluationsMode();\"";
		} else {
			echo " disabled='disabled'";
		} 
		?>
		/> Enter only final grade for this subject
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
			echo "<button class='action' onclick=\"window.pnapplication.cancelDataUnsaved();var u = new window.URL(location.href);delete u.params.edit;location.href=u.toString();\">";
			echo "<img src='".theme::$icons_16["no_edit"]."'/> Cancel changes and stop editing";
			echo "</button>";
			echo "<button id='save_button' class='action important' onclick=\"save();\">";
			echo "<img src='".theme::$icons_16["save"]."'/> Save";
			echo "</button>";
		}
		?>
	</div>
</div>
<script type='text/javascript'>
var subject_id = <?php echo $subject["id"];?>;
var subjects = <?php echo CurriculumJSON::SubjectsJSON($all_subjects);?>;
var classes = <?php echo CurriculumJSON::AcademicClassesJSON($all_classes);?>;
var students = <?php echo PeopleJSON::Peoples($students);?>;
var only_final = <?php echo $subject["only_final_grade"] == 1 ? "true" : "false";?>;
var original_only_final = only_final;
var final_grades = [<?php
$first = true; 
foreach ($final_grades as $g) {
	if ($first) $first = false; else echo ",";
	echo "{";
	echo "id:".$g["people"];
	echo ",grade:".($g["grade"] === null ? "null" : $g["grade"]);
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
		echo "}";
	}
}
?>];

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
	require("context_menu.js", function() {
		var menu = new context_menu();
		for (var i = 0; i < subjects.length; ++i)
			menu.addIconItem(null, subjects[i].code+" - "+subjects[i].name, function(p) {
				location.href = "?subject="+p.subject_id+(typeof p.class_id != 'undefined' ? "&class="+p.class_id : "");
			}, {subject_id:subjects[i].id<?php if ($class <> null) echo ",class_id:".$class["id"];?>});
		menu.showBelowElement(link);
	});
}
function selectAnotherClass(link) {
	require("context_menu.js", function() {
		var menu = new context_menu();
		for (var i = 0; i < classes.length; ++i)
			menu.addIconItem(null, classes[i].name, function(class_id) {
				location.href = "?subject=<?php echo $subject["id"];?>&class="+class_id;
			}, classes[i].id);
		menu.addIconItem(null, "All classes", function() {
			location.href = "?subject=<?php echo $subject["id"];?>";
		});
		menu.showBelowElement(link);
	});
}

function changeGradingSystem(name, system) {
	setCookie("grading_system",name,365*24*60,"/dynamic/transcripts/page/");
	// refresh final grades
	var col_index = grades_grid.grid.getColumnIndexById('final_grade');
	for (var row = 0; row < grades_grid.grid.getNbRows(); ++row) {
		var field = grades_grid.grid.getCellField(row, col_index);
		field.setGradingSystem(system);
	}
}

<?php if ($edit) {?>

function switchToOnlyFinalGradeMode() {
	only_final = true;
	if (only_final != original_only_final) pnapplication.dataUnsaved("only_final"); else pnapplication.dataSaved("only_final");
	var col = grades_grid.grid.getColumnById("final_grade");
	col.toggleEditable();
}

function switchToEvaluationsMode() {
	only_final = false;
	if (only_final != original_only_final) pnapplication.dataUnsaved("only_final"); else pnapplication.dataSaved("only_final");
	var col = grades_grid.grid.getColumnById("final_grade");
	col.toggleEditable();
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
			}
			unlock_screen(locker);
		});
	};
	var save_evaluations_grades = function() {
		// TODO
		unlock_screen(locker);
	};
	var save_grades = function() {
		if (only_final)
			save_final_grades();
		else
			save_evaluations_grades();
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
				save_grades();
			});
			return;
		}
		save_grades();
	}
	save_subject_grading();
}

<?php } ?> // if editable mode

// init grid

grades_grid.addColumn(new CustomDataGridColumn(new GridColumn("final_grade","Final Grade", 43, "center", "field_grade",<?php echo $edit && $subject["only_final_grade"] == 1 ? "true" : "false";?>,<?php echo $edit ? "finalGradeChanged" : "null";?>,<?php echo $edit ? "finalGradeUnchanged" : "null";?>,{max:<?php echo json_encode($subject["max_grade"]);?>,passing:<?php echo json_encode($subject["passing_grade"]);?>,system:<?php echo json_encode($grading_systems[$grading_system]);?>}), function(people_id){ return getFinalGrade(people_id).grade; }, true));

for (var i = 0; i < students.length; ++i)
	grades_grid.addObject(students[i].id);

</script>
		<?php 
	}
	
}
?>