<?php 
class page_students_grades extends Page {
	
	public function get_required_rights() { return array("consult_students_grades"); }
	
	public function execute() {
		$period_id = @$_GET["period"];
		if ($period_id == null) {
			$class_id = $_GET["class"];
			$class = SQLQuery::create()->select("AcademicClass")->where_value("AcademicClass", "id", $class_id)->execute_single_row();
			$period_id = $class["period"];
			$students = SQLQuery::create()
				->select("StudentClass")
				->where_value("StudentClass", "class", $class_id)
				->join("StudentClass", "Student", array("people"=>"people"))
				->join("StudentClass", "People", array("people"=>"id"))
				->field("People", "id", "people")
				->field("People", "first_name", "first_name")
				->field("People", "last_name", "last_name")
				->execute();
			$spe_id = $class["specialization"];
		} else {
			$class = null;
			$spe_id = @$_GET["specialization"];
			$q = SQLQuery::create()
				->select("StudentClass")
				->join("StudentClass", "AcademicClass", array("class"=>"id"))
				->where_value("AcademicClass", "period", $period_id)
				->join("StudentClass", "Student", array("people"=>"people"))
				->join("StudentClass", "People", array("people"=>"id"))
				->field("People", "id", "people")
				->field("People", "first_name", "first_name")
				->field("People", "last_name", "last_name")
				;
			if ($spe_id <> null)
				$q->where_value("AcademicClass", "specialization", $spe_id);
			else
				$q->where_null("AcademicClass", "specialization");
			$students = $q->execute();
		}
		$period = SQLQuery::create()->select("AcademicPeriod")->where("id",$period_id)->execute_single_row();
		$spe = $spe_id <> null ? SQLQuery::create()->select("Specialization")->where("id",$spe_id)->execute_single_row() : null;
		$q = SQLQuery::create()
			->select("CurriculumSubject")
			->where_value("CurriculumSubject", "period", $period_id)
			->join("CurriculumSubject","CurriculumSubjectGrading",array("id"=>"subject"))
			->field("CurriculumSubject","id","id")
			->field("CurriculumSubject","category","category")
			->field("CurriculumSubject","code","code")
			->field("CurriculumSubject","name","name")
			->field("CurriculumSubjectGrading","weight","weight")
			->field("CurriculumSubjectGrading","passing","passing")
			;
		if ($spe_id <> null)
			$q->where_value("CurriculumSubject", "specialization", $spe_id);
		else
			$q->where_null("CurriculumSubject", "specialization");
		$subjects = $q->execute();
		$categories = array();
		foreach ($subjects as $subject) {
			if (!isset($categories[$subject["category"]]))
				$categories[$subject["category"]] = SQLQuery::create()->select("CurriculumSubjectCategory")->where("id", $subject["category"])->execute_single_row();
			if (!isset($categories[$subject["category"]]["subjects"]))
				$categories[$subject["category"]]["subjects"] = array();
			array_push($categories[$subject["category"]]["subjects"], $subject);
		}
		$students_ids = array();
		foreach ($students as $student) array_push($students_ids, $student["people"]);
		$students_grades = SQLQuery::create()->select("StudentSubjectGrade")->where_in("StudentSubjectGrade","people", $students_ids)->execute();
		
		$this->add_javascript("/static/widgets/page_header.js");
		$this->onload("new page_header('grades_page_header', true);");
		?>
		<div id='grades_page_header' icon='' title='Grades for Period <?php echo $period["name"]; if ($spe <> null) echo ", Specialization ".$spe["name"]; if ($class<>null) echo ", Class ".$class["name"];?>'>
		</div>
		<table id='grades_table' style='border-collapse:collapse;border-spacing:0px;'></table>
		<script type='text/javascript'>
		var categories = [<?php
		$first_cat = true;
		foreach ($categories as $cat) {
			if ($first_cat) $first_cat = false; else echo ",";
			echo "{";
			echo "show:true";
			echo ",id:".$cat["id"];
			echo ",name:".json_encode($cat["name"]);
			echo ",subjects:[";
			$first_subject = true;
			foreach ($cat["subjects"] as $subject) {
				if ($first_subject) $first_subject = false; else echo ",";
				echo "{";
				echo "show:true";
				echo ",id:".$subject["id"];
				echo ",name:".json_encode($subject["name"]);
				echo ",code:".json_encode($subject["code"]);
				echo ",weight:".json_encode($subject["weight"]);
				echo ",passing:".json_encode($subject["passing"]);
				echo "}";
			}
			echo "]";
			echo "}";
		} 
		?>];
		var students = [<?php
		$first_student = true;
		foreach ($students as $student) {
			if ($first_student) $first_student = false; else echo ",";
			echo "{";
			echo "people:".$student["people"];
			echo ",first_name:".json_encode($student["first_name"]);
			echo ",last_name:".json_encode($student["last_name"]);
			echo ",grades:[";
			$first = true;
			foreach ($subjects as $subject) {
				if ($first) $first = false; else echo ",";
				echo "{";
				echo "subject:".$subject["id"];
				echo ",grade:";
				$grade = null;
				foreach ($students_grades as $sg) {
					if ($sg["people"] <> $student["people"]) continue;
					if ($sg["subject"] <> $subject["id"]) continue;
					$grade = $sg["grade"];
					break;
				}
				echo json_encode($grade);
				echo "}";
			}
			echo "]";
			echo "}";
		} 
		?>];
		var fields = [
			{name:"first_name",display:"First Name",show:true}
			,{name:"last_name",display:"Last Name",show:true}
		];

		function create_table() {
			var table = document.getElementById('grades_table');
			while (table.childNodes.length > 0) table.removeChild(table.childNodes[0]);
			var tr, td;
			table.appendChild(tr = document.createElement("TR"));
			for (var i = 0; i < fields.length; ++i) {
				var field = fields[i];
				if (!field.show) continue;
				tr.appendChild(td = document.createElement("TH"));
				td.rowSpan = 3;
				td.style.verticalAlign = "bottom";
				td.style.border = "1px solid black";
				td.appendChild(document.createTextNode(field.display));
			}
			tr.appendChild(td = document.createElement("TH"));
			td.appendChild(document.createTextNode("Category"));
			td.style.textAlign = "right";
			td.style.fontStyle = "italic";
			td.style.backgroundColor = "#C0C0C0";
			td.style.border = "1px solid black";
			for (var cat_i = 0; cat_i < categories.length; ++cat_i) {
				var cat = categories[cat_i];
				if (!cat.show) continue;
				var subjects = [];
				for (var i = 0; i < cat.subjects.length; ++i) if (cat.subjects[i].show) subjects.push(cat.subjects[i]);
				if (subjects.length == 0) continue;
				tr.appendChild(td = document.createElement("TH"));
				td.colSpan = subjects.length;
				td.style.border = "1px solid black";
				td.appendChild(document.createTextNode(cat.name));
			}
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TH"));
			td.appendChild(document.createTextNode("Subject"));
			td.style.textAlign = "right";
			td.style.fontStyle = "italic";
			td.style.backgroundColor = "#C0C0C0";
			td.style.border = "1px solid black";
			for (var cat_i = 0; cat_i < categories.length; ++cat_i) {
				var cat = categories[cat_i];
				if (!cat.show) continue;
				var subjects = [];
				for (var i = 0; i < cat.subjects.length; ++i) if (cat.subjects[i].show) subjects.push(cat.subjects[i]);
				if (subjects.length == 0) continue;
				for (var subject_i = 0; subject_i < subjects.length; ++subject_i) {
					var subject = subjects[subject_i];
					tr.appendChild(td = document.createElement("TH"));
					var link = document.createElement("A");
					link.appendChild(document.createTextNode(subject.code));
					link.href = "/dynamic/transcripts/page/subject_grades?subject="+subject.id<?php if ($class <> null) echo "+'&class=".$class_id."'";?>;
					link.style.color = "black";
					td.appendChild(link);
					td.style.border = "1px solid black";
					td.title = subject.name;
				}
			}
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TH"));
			td.appendChild(document.createTextNode("Weight"));
			td.style.textAlign = "right";
			td.style.fontStyle = "italic";
			td.style.backgroundColor = "#C0C0C0";
			td.style.border = "1px solid black";
			for (var cat_i = 0; cat_i < categories.length; ++cat_i) {
				var cat = categories[cat_i];
				if (!cat.show) continue;
				var subjects = [];
				for (var i = 0; i < cat.subjects.length; ++i) if (cat.subjects[i].show) subjects.push(cat.subjects[i]);
				if (subjects.length == 0) continue;
				for (var subject_i = 0; subject_i < subjects.length; ++subject_i) {
					var subject = subjects[subject_i];
					tr.appendChild(td = document.createElement("TH"));
					td.appendChild(document.createTextNode(subject.weight));
					td.style.border = "1px solid black";
				}
			}
			for (var student_i = 0; student_i < students.length; ++student_i) {
				var student = students[student_i];
				table.appendChild(tr = document.createElement("TR"));
				for (var field_i = 0; field_i < fields.length; ++field_i) {
					var field = fields[field_i];
					if (!field.show) continue;
					tr.appendChild(td = document.createElement("TD"));
					td.style.border = "1px solid black";
					td.appendChild(document.createTextNode(student[field.name]));
				}
				tr.appendChild(td = document.createElement("TD"));
				td.style.backgroundColor = "#C0C0C0";
				td.style.border = "1px solid black";
				for (var cat_i = 0; cat_i < categories.length; ++cat_i) {
					var cat = categories[cat_i];
					if (!cat.show) continue;
					var subjects = [];
					for (var i = 0; i < cat.subjects.length; ++i) if (cat.subjects[i].show) subjects.push(cat.subjects[i]);
					if (subjects.length == 0) continue;
					for (var subject_i = 0; subject_i < subjects.length; ++subject_i) {
						var subject = subjects[subject_i];
						tr.appendChild(td = document.createElement("TD"));
						td.style.border = "1px solid black";
						var grade = null;
						for (var i = 0; i < student.grades; ++i)
							if (student.grades[i].subject == subject.id) { grade = student.grades[i].grade; break; }
						if (grade == null)
							td.style.backgroundColor = '#C0C0C0';
						else {
							td.appendChild(document.createTextNode(grade));
							td.style.textAlign = "center";
							// TODO color
						}
					}
				}
			} 
		}
		create_table();
		</script>
		<?php 
	}
	
}
?>