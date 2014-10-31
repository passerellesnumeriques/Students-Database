<?php 
class page_students_grades extends Page {
	
	public function getRequiredRights() { return array("consult_students_grades"); }
	
	public function execute() {
		$period_id = @$_GET["period"];
		$class_id = @$_GET["class"];
		if ($period_id == null && $class_id == null) {
			echo "<div style='padding:5px'><div class='info_box'>";
			echo "<img src='".theme::$icons_16["info"]."' style='vertical-align:bottom'/> ";
			echo "To display the grades, you need first to select for which period, or which class, by using the tree on the right side.";
			echo "</div></div>";
			return;
		}
		if ($class_id <> null) {
			$class = PNApplication::$instance->curriculum->getAcademicClass($class_id);
			$period_id = $class["period"];
			$spe_id = $class["specialization"];
		} else {
			$class = null;
			$spe_id = @$_GET["specialization"];
		}
		$period = PNApplication::$instance->curriculum->getBatchPeriod($period_id);
		$batch_id = $period["batch"];
		$batch = PNApplication::$instance->curriculum->getBatch($batch_id);
		$spe = $spe_id <> null ? PNApplication::$instance->curriculum->getSpecialization($spe_id) : null;
		
		// get the list of students
		if ($class <> null) {
			$q = PNApplication::$instance->students->getStudentsQueryForClass($class["id"], true);
		} else {
			$q = PNApplication::$instance->students->getStudentsQueryForBatchPeriod($period_id, true, false, $spe <> null ? $spe["id"] : false);
		}
		$students = $q->execute();
		$students_ids = array();
		foreach ($students as $s) array_push($students_ids, $s["people_id"]);
		
		// get subjects and categories
		$subjects = PNApplication::$instance->curriculum->getSubjects($batch_id, $period_id, $spe_id);
		$categories = PNApplication::$instance->curriculum->getSubjectCategories();
		$subjects_ids = array();
		foreach ($subjects as $s) array_push($subjects_ids, $s["id"]);
		
		if (count($subjects) > 0) {
			// get subjects' grading
			$subjects_grading = SQLQuery::create()->select("CurriculumSubjectGrading")->whereIn("CurriculumSubjectGrading", "subject", $subjects_ids)->execute();
			// get students grades
			if (count($students) > 0)
				$students_grades = SQLQuery::create()->select("StudentSubjectGrade")->whereIn("StudentSubjectGrade","subject",$subjects_ids)->whereIn("StudentSubjectGrade","people",$students_ids)->execute();
			else
				$students_grades = array();
		} else {
			$subjects_grading = array();
			$students_grades = array();
		}
		
		if (count($students) > 0)
			$students_comments = SQLQuery::create()->select("StudentTranscriptGeneralComment")->whereIn("StudentTranscriptGeneralComment","people",$students_ids)->whereValue("StudentTranscriptGeneralComment","period",$period_id)->execute();
		else
			$students_comments = array();
		
		// grading systems
		$grading_systems = include("component/transcripts/GradingSystems.inc");
		if (isset($_COOKIE["grading_system"]))
			$grading_system = $_COOKIE["grading_system"];
		else {
			$d = PNApplication::$instance->getDomainDescriptor();
			$grading_system = $d["transcripts"]["default_grading_system"];
		}
		// other display settings
		if (isset($_COOKIE["display_coef"]))
			$display_coef = $_COOKIE["display_coef"];
		else
			$display_coef = "1";
		
		$this->requireJavascript("grid.js");
		$this->requireJavascript("custom_data_grid.js");
		$this->requireJavascript("people_data_grid.js");
		theme::css($this, "grid.css");
		
		require_once("component/curriculum/CurriculumJSON.inc");
		?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;background-color:white'>
	<div class='page_title' style='flex:none'>
		<img src='/static/transcripts/grades_32.png'/>
		Grades
		<span style='margin-left:10px;font-size:12pt;font-style:italic;'>
		<?php
		$title = "Batch ".$batch["name"];
		$title .= ", ".$period["name"];
		if ($spe <> null) $title .= ", Specialization ".$spe["name"];
		if ($class <> null) $title .= ", Class ".$class["name"];
		echo toHTML($title);
		?>
		</span>
	</div>
	<div style='flex:none;background-color:white;box-shadow: 1px 2px 5px 0px #808080;margin-bottom:5px;padding:5px;display:flex;flex-direction:row;align-items:center'>
		<div style='flex:none;display:inline-block;'>
			<img src='<?php echo theme::$icons_16["settings"];?>' style='vertical-align:bottom'/>
			Display settings:
		</div>
		<div style='flex:1 1 auto;display:flex;flex-direction:row;align-items:center;'>
			<span style='margin-left:10px'></span>
			Grading system <select onchange="changeGradingSystem(this.options[this.selectedIndex].text,this.value);">
			<?php
			foreach($grading_systems as $name=>$spec) {
				echo "<option value=\"".$spec."\"";
				if ($name == $grading_system) echo " selected='selected'";
				echo ">".toHTML($name)."</option>";
			}
			?>
			</select>
			<span style='margin-left:10px'></span>
			<input type='checkbox' onchange='setDisplayCoef(this.checked);' <?php if ($display_coef == 1) echo " checked='checked'";?>/><span onclick="this.previousSibling.checked = this.previousSibling.checked ? '' : 'checked';"> Display coefficients</span>
			<span style='margin-left:10px'></span>
			<button class='flat' id='columns_chooser_button'><img src='/static/data_model/table_column.png'/> Choose columns</button>
			<button class='flat' id='export_button'><img src='<?php echo theme::$icons_16["_export"];?>'/> Export</button>
			<button class='flat' id='print_button'><img src='<?php echo theme::$icons_16["print"];?>'/> Print</button>
		</div>
		<?php if (PNApplication::$instance->user_management->has_right("edit_students_grades")) { ?>
		<div style='flex:none;display:inline-block;'>
			<button id='button_edit_general_appreciation' class='action' onclick="editGeneralAppreciation(this);">Edit General Appreciations</button>
		</div>
		<?php } ?>
	</div>
	<div style='flex:1 1 auto;overflow:auto' id='grades_container'>
	</div>
</div>
<?php
if (PNApplication::$instance->help->isShown("students_grades")) {
	$help_div_id = PNApplication::$instance->help->startHelp("students_grades", $this, "left", "bottom", false);
	echo "This screen gives you an overview of the grades, for all subjects,<br/>";
	echo "together with the total grade and rank of the students.<br/>";
	echo "<br/>";
	if (PNApplication::$instance->user_management->has_right("edit_students_grades")) {
		echo "In this screen, you can also ";
		PNApplication::$instance->help->spanArrow($this, "edit the general appreciation", "#button_edit_general_appreciation");
		echo "<br/>of each student which can be included later in the transcripts.<br/>";
		echo "<br/>";
	}
	echo "To display or edit the details of the grades for a given subject,<br/>";
	echo "click on ";
	PNApplication::$instance->help->spanArrow($this, "the subject name", ".grid thead");
	echo ".<br/>";
	PNApplication::$instance->help->endHelp($help_div_id, "students_grades");
} else
	PNApplication::$instance->help->availableHelp("students_grades");
?>
<script type='text/javascript'>
var students = <?php echo PeopleJSON::Peoples($students);?>;
var students_comments = <?php echo json_encode($students_comments);?>;
var categories = <?php echo CurriculumJSON::SubjectCategoriesJSON($categories);?>;
var subjects = <?php echo CurriculumJSON::SubjectsJSON($subjects);?>;
var subjects_grading = [<?php
$first = true;
foreach ($subjects_grading as $sg) {
	if ($first) $first = false; else echo ",";
	echo "{";
	echo "subject:".$sg["subject"];
	echo ",max_grade:".json_encode($sg["max_grade"]);
	echo ",passing_grade:".json_encode($sg["passing_grade"]);
	echo "}";
}
?>];
var students_grades = [<?php
$first = true;
foreach ($students_grades as $sg) {
	if ($first) $first = false; else echo ",";
	echo "{";
	echo "subject:".$sg["subject"];
	echo ",people:".$sg["people"];
	echo ",grade:".json_encode($sg["grade"]);
	echo "}";
} 
?>];

function getSubject(subject_id) {
	for (var i = 0; i < subjects.length; ++i)
		if (subjects[i].id == subject_id)
			return subjects[i];
	return null;
}
function getSubjectGrading(subject_id) {
	for (var i = 0; i < subjects_grading.length; ++i)
		if (subjects_grading[i].subject == subject_id)
			return subjects_grading[i];
	return null;
}
function getStudentGrade(people_id, subject_id) {
	for (var i = 0; i < students_grades.length; ++i)
		if (students_grades[i].subject == subject_id && students_grades[i].people == people_id)
			return students_grades[i].grade;
	return null;
}
function getStudentComment(people_id) {
	for (var i = 0; i < students_comments.length; ++i)
		if (students_comments[i].people == people_id) return students_comments[i].comment;
	return null;
}
var global_coef = 0;
var global_passing = 0;
for (var i = 0; i < subjects.length; ++i) {
	if (!subjects[i].coefficient) continue;
	var sg = getSubjectGrading(subjects[i].id);
	if (!sg || !sg.max_grade || !sg.passing_grade) continue;
	global_coef += subjects[i].coefficient;
	global_passing += parseFloat(sg.passing_grade);
}
if (global_coef > 0) global_passing /= global_coef;
function computeStudentGlobalGrade(people_id) {
	var total = 0;
	var coef = 0;
	for (var i = 0; i < students_grades.length; ++i) {
		if (students_grades[i].people != people_id) continue;
		if (students_grades[i].grade === null) continue;
		var sg = getSubjectGrading(students_grades[i].subject);
		if (!sg) continue;
		if (!sg.max_grade) continue;
		if (!sg.passing_grade) continue;
		var s = getSubject(students_grades[i].subject);
		if (!s || !s.coefficient) continue;
		coef += s.coefficient;
		total += (students_grades[i].grade*100/parseFloat(sg.max_grade))*s.coefficient;
	}
	if (!coef) return null;
	return total/coef;
}
var students_global_grades = [];
for (var i = 0; i < students.length; ++i) {
	var gg = computeStudentGlobalGrade(students[i].id);
	students_global_grades.push(gg);
}
function getStudentGlobalGrade(people_id) {
	for (var i = 0; i < students.length; ++i)
		if (students[i].id == people_id)
			return students_global_grades[i];
	return null;
}
function getStudentRank(people_id) {
	var gg = getStudentGlobalGrade(people_id);
	var rank = 1;
	for (var i = 0; i < students_global_grades.length; ++i)
		if (students_global_grades[i] > gg) rank++;
	return rank;
}

var grades_grid = new people_data_grid('grades_container', function(people) { return people; }, "Student");
grades_grid.setColumnsChooserButton(document.getElementById('columns_chooser_button'));
grades_grid.setExportButton(document.getElementById('export_button'),<?php echo json_encode("Grades of ".$title);?>,'Grades');
grades_grid.setPrintButton(document.getElementById('print_button'));
grades_grid.grid.makeScrollable();
for (var i = 0; i < categories.length; ++i) {
	var cat_subjects = [];
	for (var j = 0; j < subjects.length; ++j)
		if (subjects[j].category_id == categories[i].id) cat_subjects.push(subjects[j]);
	if (cat_subjects.length == 0) continue; // no subject for this category
	var columns = [];
	for (var j = 0; j < cat_subjects.length; ++j) {
		var title = document.createElement("SPAN");
		var sname = document.createElement("A");
		sname.href = "subject_grades?subject="+cat_subjects[j].id<?php if ($class <> null) echo "+'&class=".$class["id"]."'";?>;
		sname.target = "application_frame";
		sname.appendChild(document.createTextNode(cat_subjects[j].code));
		sname.appendChild(document.createElement("BR"));
		sname.appendChild(document.createTextNode(cat_subjects[j].name));
		sname.className = "black_link";
		tooltip(sname, "Click to open grades of subject <i>"+cat_subjects[j].name+"</i>");
		title.appendChild(sname);
		var span_coef = document.createElement("SPAN");
		span_coef.style.fontWeight = "normal";
		span_coef.style.visibility = <?php if ($display_coef == 1) echo "'visible'"; else echo "'hidden'";?>;
		span_coef.style.position = <?php if ($display_coef == 1) echo "'static'"; else echo "'absolute'";?>;
		span_coef.appendChild(document.createElement("BR"));
		span_coef.appendChild(document.createTextNode("Coef. "+cat_subjects[j].coefficient));
		title.appendChild(span_coef);
		title.span_coef = span_coef;
		var sg = getSubjectGrading(cat_subjects[j].id);
		var col = new GridColumn("subject"+cat_subjects[j].id,title,null,"center","field_grade",false,null,null,{max:sg ? sg.max_grade : 1,passing:sg ? sg.passing_grade : 0.5,system:<?php echo json_encode($grading_systems[$grading_system]);?>});
		col.addSorting();
		columns.push(new CustomDataGridColumn(col,function(people,subject_id){return getStudentGrade(people.id,subject_id);},true,cat_subjects[j].id,cat_subjects[j].name));
	}
	grades_grid.addColumnContainer(new CustomDataGridColumnContainer(categories[i].name, columns));
}
if (global_coef > 0) {
	// global grade column
	var col = new GridColumn("student_global_grade","Global Grade",null,"center","field_grade",false,null,null,{max:100,passing:global_passing,system:<?php echo json_encode($grading_systems[$grading_system]);?>});
	col.addSorting();
	grades_grid.addColumn(new CustomDataGridColumn(col, function(people) {
		return getStudentGlobalGrade(people.id);
	}, true, null));
	// ranking column
	col = new GridColumn("student_rank","Rank",null,"center","field_integer",false,null,null,{can_be_null:true,min:1});
	col.addSorting();
	grades_grid.addColumn(new CustomDataGridColumn(col, function(people) {
		return getStudentRank(people.id);
	}, true, null));
}
// General appreciation column
grades_grid.addColumn(new CustomDataGridColumn(new GridColumn("student_comment","General appreciation",null,"left","field_text",false,null,null,{can_be_null:true,max_length:4000,min_size:30}), function(people) {
	return getStudentComment(people.id);
}, true, null));
// add every student
for (var i = 0; i < students.length; ++i)
	grades_grid.addPeople(students[i]);


function changeGradingSystem(name, system) {
	setCookie("grading_system",name,365*24*60,"/dynamic/transcripts/page/");
	// refresh grades
	for (var i = 0; i < subjects.length; ++i) {
		var col_index = grades_grid.grid.getColumnIndexById('subject'+subjects[i].id);
		for (var row = 0; row < grades_grid.grid.getNbRows(); ++row) {
			var field = grades_grid.grid.getCellField(row, col_index);
			field.setGradingSystem(system);
		}
	}
}
function setDisplayCoef(display) {
	setCookie("display_coef",name,365*24*60,"/dynamic/transcripts/page/students_grade");
	var columns = grades_grid.getAllFinalColumns();
	for (var i = 0; i < columns.length; ++i) {
		if (typeof columns[i].grid_column.title.span_coef == 'undefined') continue;
		columns[i].grid_column.title.span_coef.style.visibility = display ? "visible" : "hidden";
		columns[i].grid_column.title.span_coef.style.position = display ? "static" : "absolute";
	}
}
var general_appreciation_lock_id = null;
function editGeneralAppreciation(button) {
	var locker = lock_screen();
	service.json("data_model","lock_table",{table:"StudentTranscriptGeneralComment",get_locker:true},function(res) {
		if (!res) { unlock_screen(locker); return; }
		if (res.locker) {
			unlock_screen(locker);
			error_dialog(res.locker+" is already editing general appreciations, you cannot edit at the same time.");
			return;
		}
		general_appreciation_lock_id = res.lock;
		databaselock.addLock(res.lock);
		button.innerHTML = "<img src='"+theme.icons_16.save+"'/> Save appreciations";
		button.onclick = function() { saveGeneralAppreciation(this); };
		var col = grades_grid.grid.getColumnById('student_comment');
		col.toggleEditable();
		pnapplication.dataUnsaved('general_appreciation');
		unlock_screen(locker);
	});
}
function saveGeneralAppreciation(button) {
	var locker = lock_screen(null, "Saving general appreciations...");
	var comments = [];
	for (var i = 0; i < students.length; ++i) {
		var cell = grades_grid.grid.getCellFieldById(students[i].id, 'student_comment');
		comments.push({people:students[i].id,comment:cell.getCurrentData()});
	}
	service.json("transcripts","save_general_comments",{period:<?php echo $period_id;?>,students:comments},function(res) {
		if (!res) { unlock_screen(locker); return; }
		databaselock.unlock(general_appreciation_lock_id, function(res) {
			general_appreciation_lock_id = null;
			button.innerHTML = "Edit General Appreciations";
			button.onclick = function() { editGeneralAppreciation(this); };
			var col = grades_grid.grid.getColumnById('student_comment');
			col.toggleEditable();
			pnapplication.dataSaved('general_appreciation');
			unlock_screen(locker);
		});
	});
}
window.help_display_ready = true;
</script>
		<?php 
	}
	
}
?>