<?php 
class page_students_grades extends Page {
	
	public function getRequiredRights() { return array("consult_students_grades"); }
	
	public function execute() {
		$period_id = @$_GET["period"];
		$class_id = @$_GET["class"];
		if ($period_id == null && $class_id == null) {
			echo "<img src='".theme::$icons_16["info"]."'/> ";
			echo "Please select a period or a class to display the grades of the students";
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
			$students_grades = SQLQuery::create()->select("StudentSubjectGrade")->whereIn("StudentSubjectGrade","subject",$subjects_ids)->whereIn("StudentSubjectGrade","people",$students_ids)->execute();
		} else {
			$subjects_grading = array();
			$students_grades = array();
		}		
		
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
		echo "Batch ".htmlentities($batch["name"]);
		echo ", ".htmlentities($period["name"]);
		if ($spe <> null) echo ", Specialization ".htmlentities($spe["name"]);
		if ($class <> null) echo ", Class ".htmlentities($class["name"]);
		?>
		</span>
	</div>
	<div style='flex:none;background-color:white;box-shadow: 1px 2px 5px 0px #808080;margin-bottom:5px;padding:5px'>
		<img src='<?php echo theme::$icons_16["settings"];?>' style='vertical-align:bottom'/>
		Display settings:
		<span style='margin-left:10px'></span>
		Grading system <select onchange="changeGradingSystem(this.options[this.selectedIndex].text,this.value);">
		<?php
		foreach($grading_systems as $name=>$spec) {
			echo "<option value=\"".$spec."\"";
			if ($name == $grading_system) echo " selected='selected'";
			echo ">".htmlentities($name)."</option>";
		}
		?>
		</select>
		<span style='margin-left:10px'></span>
		<input type='checkbox' onchange='setDisplayCoef(this.checked);' <?php if ($display_coef == 1) echo " checked='checked'";?>/><span onclick="this.previousSibling.checked = this.previousSibling.checked ? '' : 'checked';"> Display coefficients</span>
	</div>
	<div style='flex:1 1 auto;overflow:auto' id='grades_container'>
	</div>
</div>
<script type='text/javascript'>
var students = <?php echo PeopleJSON::Peoples($students);?>;
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

var grades_grid = new people_data_grid('grades_container', function(people) { return people; }, "Student");
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
		sname.appendChild(document.createTextNode(cat_subjects[j].name));
		sname.className = "black_link";
		tooltip(sname, "Open grade for subject "+cat_subjects[j].name);
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
		columns.push(new CustomDataGridColumn(new GridColumn("subject"+cat_subjects[j].id,title,null,"center","field_grade",false,null,null,{max:sg ? sg.max_grade : 1,passing:sg ? sg.passing_grade : 0.5,system:<?php echo json_encode($grading_systems[$grading_system]);?>}),function(people,subject_id){return getStudentGrade(people.id,subject_id);},true,cat_subjects[j].id,cat_subjects[j].name));
	}
	grades_grid.addColumnContainer(new CustomDataGridColumnContainer(categories[i].name, columns));
}
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
</script>
		<?php 
	}
	
}
?>