<?php 
class page_subject_grades extends Page {
	
	public function get_required_rights() { return array("consult_students_grades"); }
	
	public function execute() {
		// get subject
		$subject = SQLQuery::create()
			->select("CurriculumSubject")
			->where_value("CurriculumSubject", "id", $_GET["subject"])
			->execute_single_row();
		$subject_grading = SQLQuery::create()
			->select("CurriculumSubjectGrading")
			->where("subject", $subject["id"])
			->execute_single_row();
		
		// get period
		$period = SQLQuery::create()
			->select("AcademicPeriod")
			->where_value("AcademicPeriod", "id", $subject["period"])
			->execute_single_row();
		
		// get specialization or null
		$specialization = $subject["specialization"] <> null ? SQLQuery::create()->select("Specialization")->where("id",$subject["specialization"])->execute_single_row() : null;
		
		// get batch
		$batch = SQLQuery::create()->select("StudentBatch")->where("id", $period["batch"])->execute_single_row();
		
		// get list of classes
		$class_id = @$_GET["class"];
		if ($class_id == null) {
			$q = SQLQuery::create()
				->select("AcademicClass")
				->where("period", $subject["period"])
				;
			if ($specialization <> null)
				$q->where_value("AcademicClass", "specialization", $specialization["id"]);
			else
				$q->where_null("AcademicClass", "specialization");
			$classes = $q->execute();
			$class = null;
		} else {
			$class = SQLQuery::create()->select("AcademicClass")->where("id", $class_id)->execute_single_row();
			$classes = array($class);
		}
		$classes_ids = array();
		foreach ($classes as $c) array_push($classes_ids, $c["id"]);
		
		// get list of students
		$students = SQLQuery::create()
			->select("StudentClass")
			->where_in("StudentClass", "class", $classes_ids)
			->join("StudentClass", "Student", array("people"=>"people"))
			->join("StudentClass", "People", array("people"=>"id"))
			->join("StudentClass", "StudentSubjectGrade", array("people"=>"people"))
			->field("StudentClass", "people", "people")
			->field("People", "first_name", "first_name")
			->field("People", "last_name", "last_name")
			->field("StudentSubjectGrade", "grade", "final_grade")
			->execute();
		
		$can_edit = PNApplication::$instance->user_management->has_right("edit_students_grades");
		
		$this->add_javascript("/static/widgets/page_header.js");
		$this->add_javascript("/static/widgets/vertical_layout.js");
		?>
		<div id='page_container' style='width:100%;height:100%'>
			<div id='subject_grades_header'>
				<?php if ($can_edit) {?>
					<div class='button' onclick="edit();" id='edit_button'><img src='<?php echo theme::$icons_16["edit"];?>'/> Edit</div>
				<?php } ?>
				<div class='button' onclick="location.href='/dynamic/transcripts/page/students_grades?<?php if ($class <> null) echo "class=".$class_id; else echo "period=".$period["id"];?>';">
					<img src='<?php echo theme::$icons_16["left"];?>'/>
					Back to general grades
				</div>
			</div>
			<div id='header2' style='background-color:#FFFFA0;border-bottom:1px solid #A0A080;'>
				<div>
					Subject Weight: <span id='subject_weight'></span>
					Maximum Grade: <span id='subject_max_grade'></span>
					Passing Grade: <span id='subject_passing_grade'></span>
				</div>
				<form name='only_final_grade_selection' onsubmit='return false;'>
				<input type='radio' name='only_final_grade' value='true' disabled='disabled' onchange='grade_type_changed();'/> Give only a final grade for this subject<br/>
				<input type='radio' name='only_final_grade' value='false' disabled='disabled' onchange='grade_type_changed();'/> Specify evaluations, enter grades for each evaluation, and automatically compute the final grade<br/> 
				</form>
				<?php if ($can_edit) {?>
				<div id='edit_buttons' style='visibility:hidden;position:absolute;top:-10000px'>
					<div class='button' onclick='new_evaluation_type();'>New type of evaluation</div>
					<div class='button' onclick='new_evaluation();'>New evaluation</div>
				</div>
				<?php } ?>
			</div>
			<div id='grades_container' layout='fill' style='overflow:auto'>
				<table id='grades_table' style='border-collapse:collapse;border-spacing:0px;'>
				</table>
			</div>
		</div>
		
		<script type='text/javascript'>
		var subject_info = <?php
			if ($subject_grading == null) echo "{weight:1,passing_grade:50,max_grade:100,only_final_grade:true}";
			else {
				echo "{";
				echo "weight:".json_encode($subject_grading["weight"]);
				echo ",passing:".json_encode($subject_grading["passing_grade"]);
				echo ",max:".json_encode($subject_grading["max_grade"]);
				echo ",only_final_grade:".json_encode($subject_grading["only_final_grade"]);
				echo "}";
			} 
		?>;
		var students = [<?php
		$first_student = true;
		foreach ($students as $student) {
			if ($first_student) $first_student = false; else echo ",";
			echo "{";
			echo "people:".$student["people"];
			echo ",first_name:".json_encode($student["first_name"]);
			echo ",last_name:".json_encode($student["last_name"]);
			echo ",grades:[";
			// TODO
			echo "]";
			echo ",final_grade:".json_encode($student["final_grade"]);
			echo "}";
		} 
		?>];
		var student_fields = [];

		var field_subject_weight, field_subject_max_grade, field_subject_passing_grade;
		
		function init() {
			var header = new page_header('subject_grades_header', true);
			var title = document.createElement("SPAN");
			title.innerHTML = "<img src='/static/transcripts/grades.gif' style='vertical-align:bottom;padding-right:3px'/>";
			title.style.fontWeight = 'normal';
			title.appendChild(document.createTextNode("Grades of "));
			var e = document.createElement("B");
			e.appendChild(document.createTextNode(<?php echo json_encode($subject["code"]." - ".$subject["name"]);?>));
			title.appendChild(e);
			title.appendChild(document.createTextNode(" for Period "));
			var e = document.createElement("B");
			e.appendChild(document.createTextNode(<?php echo json_encode($period["name"]);?>));
			title.appendChild(e);
			<?php if ($class <> null) {?>
			title.appendChild(document.createTextNode(", Class "));
			var e = document.createElement("B");
			e.appendChild(document.createTextNode(<?php echo json_encode($class["name"]);?>));
			title.appendChild(e);
			<?php } ?>
			<?php if ($specialization <> null) {?>
			title.appendChild(document.createTextNode(", Specialization "));
			var e = document.createElement("B");
			e.appendChild(document.createTextNode(<?php echo json_encode($specialization["name"]);?>));
			title.appendChild(e);
			<?php } ?>
			title.appendChild(document.createTextNode(", Batch "));
			var e = document.createElement("B");
			e.appendChild(document.createTextNode(<?php echo json_encode($batch["name"]);?>));
			title.appendChild(e);
			header.setTitle(title);

			<?php PNApplication::$instance->widgets->create_typed_field($this, "field_subject_weight", "CurriculumSubjectGrading", "weight", "false", $subject_grading == null || $subject_grading["weight"] == null ? "1" : $subject_grading["weight"]);?>
			document.getElementById('subject_weight').appendChild(field_subject_weight.getHTMLElement());
			field_subject_weight.onchange.add_listener(function(f){
				subject_info.weight = f.getCurrentData();
			});
			<?php PNApplication::$instance->widgets->create_typed_field($this, "field_subject_max_grade", "CurriculumSubjectGrading", "max_grade", "false", $subject_grading == null || $subject_grading["max_grade"] == null ? "100" : $subject_grading["max_grade"]);?>
			document.getElementById('subject_max_grade').appendChild(field_subject_max_grade.getHTMLElement());
			field_subject_max_grade.onchange.add_listener(function(f){
				subject_info.max_grade = f.getCurrentData();
				for (var i = 0; i < students.length; ++i) {
					students[i].field_max_grade.config.max = subject_info.max_grade;
					if (subject_info.only_final_grade) {
						if (students[i].field_max_grade.getCurrentData() > subject_info.max_grade) {
							students[i].field_max_grade.setData(subject_info.max_grade);
							students[i].final_grade = subject_info.max_grade;
						}
					} else
						compute_grades();
				}
			});
			<?php PNApplication::$instance->widgets->create_typed_field($this, "field_subject_passing_grade", "CurriculumSubjectGrading", "passing_grade", "false", $subject_grading == null || $subject_grading["passing_grade"] == null ? "50" : $subject_grading["passing_grade"]);?>
			document.getElementById('subject_passing_grade').appendChild(field_subject_passing_grade.getHTMLElement());
			field_subject_passing_grade.onchange.add_listener(function(f){
				subject_info.passing_grade = f.getCurrentData();
				for (var i = 0; i < students.length; ++i)
					update_color(students[i].td_final_grade, subject_info.passing_grade, students[i].final_grade);
			});
			var only_final_grade = document.forms['only_final_grade_selection'].elements['only_final_grade'];
			if (subject_info.only_final_grade)
				only_final_grade.value = 'true';
			else
				only_final_grade.value = 'false';

			new vertical_layout('page_container');
		}
		init();

		var types = [<?php 
		$types = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->where("subject", $subject["id"])->execute();
		$first_type = true;
		foreach ($types as $type) {
			if ($first_type) $first_type = false; else echo ",";
			echo "{";
			echo "id:".$type["id"];
			echo ",name:".json_encode($type["name"]);
			echo ",weight:".json_encode($type["weight"]);
			echo ",evaluations:[";
			$evaluations = SQLQuery::create()->select("CurriculumSubjectEvaluation")->where("type", $type["id"])->execute();
			$first_eval = true;
			foreach ($evaluations as $eval) {
				if ($first_eval) $first_eval = false; else echo ",";
				echo "{";
				echo "id:".$eval["id"];
				echo "name:".json_encode($eval["name"]);
				echo ",weight:".json_encode($eval["weight"]);
				echo "}";
			}
			echo "]";
			echo "}";
		}
		?>];

		var table;

		function _hide(e) {
			e.style.visibility = 'hidden';
			e.style.position = 'absolute';
			e.style.top = '-10000px';
		}
		function _show(e) {
			e.style.visibility = 'visible';
			e.style.position = 'static';
		}

		function update_color(cell, passing_grade, grade) {
			if (grade == null)
				cell.style.backgroundColor = '#A0A0A0';
			else if (grade < passing_grade)
				cell.style.backgroundColor = '#FF0000';
			else
				cell.style.backgroundColor = '#00FF00';
		}
		
		function add_student_field(field) {
			// add header
			var td = document.createElement("TH");
			table.childNodes[0].insertBefore(td, table.childNodes[0].childNodes[student_fields.length]);
			td.rowSpan = 4;
			td.style.verticalAlign = "bottom";
			td.style.border = "1px solid black";
			td.appendChild(document.createTextNode(field.display));
			for (var i = 0; i < students.length; ++i) {
				var td = document.createElement("TD");
				td.appendChild(document.createTextNode(students[i][field.name]));
				td.style.border = "1px solid black";
				students[i].tr.insertBefore(td, students[i].tr.childNodes[student_fields.length]);
			}
			student_fields.push(field);
		}
		
		function add_evaluation_type(type) {
			// add header, before final grade
			var td = document.createElement("TH");
			table.childNodes[0].insertBefore(td, table.childNodes[0].childNodes[table.childNodes[0].childNodes.length-1]);
			td.style.border = "1px solid black";
			<?php PNApplication::$instance->widgets->create_typed_field($this, "type.field_name", "CurriculumSubjectEvaluationType", "name", "edit_mode", "type.name");?>
			td.appendChild(type.field_name.getHTMLElement());
			type.field_name.onchange.add_listener(function(f){
				type.name = f.getCurrentData();
			});
			td = document.createElement("TH");
			table.childNodes[1].appendChild(td);
			td.style.border = "1px solid black";
			td.appendChild(document.createTextNode("Weight: "));
			<?php PNApplication::$instance->widgets->create_typed_field($this, "type.field_weight", "CurriculumSubjectEvaluationType", "weight", "edit_mode", "type.weight");?>
			td.appendChild(type.field_weight.getHTMLElement());
			type.field_weight.onchange.add_listener(function(f){
				type.weight = f.getCurrentData();
			});
			// add empty cell for evaluations
			td = document.createElement("TH");
			td.style.border = "1px solid black";
			table.childNodes[2].appendChild(td); 
			td = document.createElement("TH");
			td.style.border = "1px solid black";
			table.childNodes[3].appendChild(td); 
			// add empty cell for each student
			for (var i = 0; i < students.length; ++i) {
				td = document.createElement("TD");
				td.style.border = "1px solid black";
				students[i].tr.insertBefore(td, students[i].td_final_grade);
			}
		}
		function add_eval(type, eval) {
			// determine the index of the column
			var index = 0;
			index += fields.length; // after information about the student
			for (var i = 0; i < types.length; ++i) {
				if (types[i] == type) break;
				var type_cols = type.evaluations.length;
				if (type_cols == 0) type_cols = 1;
				index += type_cols;
			}
			if (type.evaluations.length == 1 && type.evaluations[0] == eval) {
				// this is the first evaluation: remove the corresponding column
				tr_eval.removeChild(tr_eval.childNodes[index]);
				tr_eval_weight.removeChild(tr_eval_weight.childNodes[index]);
				for (var i = 0; i < students.length; ++i)
					students[i].tr.removeChild(students[i].tr.childNodes[index]);
			} else
				for (var i = 0; i < type.evaluations.length; ++i) {
					if (type.evaluations[i] == eval) break;
					index++;
				}

			// add the header
			var td = document.createElement("TH");
			table.childNodes[2].insertBefore(td, table.childNodes[2].childNodes[index]);
			td.appendChild(document.createTextNode(eval.name));
			td.style.border = "1px solid black";
			td = document.createElement("TH");
			table.childNodes[3].insertBefore(td, table.childNodes[3].childNodes[index]);
			td.appendChild(document.createTextNode("Weight: "));
			<?php PNApplication::$instance->widgets->create_typed_field($this, "eval.field_weight", "CurriculumSubjectEvaluation", "weight", "edit_mode", "eval.weight");?>
			td.appendChild(eval.field_weight.getHTMLElement());
			eval.field_weight.onchange.add_listener(function(f){
				eval.weight = f.getCurrentData();
			});
			td.style.border = "1px solid black";
			// add the cell for each student
			for (var i = 0; i < students.length; ++i) {
				var td = document.createElement("TD");
				students[i].tr.insertBefore(td, students[i].tr.childNodes[index]);
			}
		}

		function create_table() {
			table = document.getElementById('grades_table');
			while (table.childNodes.length > 0) table.removeChild(table.childNodes[0]);
			var tbody = document.createElement("TBODY");
			table.appendChild(tbody);
			table = tbody;

			// create 4 rows for the headers
			table.appendChild(tr = document.createElement("TR"));
			table.appendChild(tr = document.createElement("TR"));
			table.appendChild(tr = document.createElement("TR"));
			table.appendChild(tr = document.createElement("TR"));

			// add the final grade column
			td = document.createElement("TH");
			td.appendChild(document.createTextNode("Final Grade"));
			td.rowSpan = 4;
			td.style.border = "1px solid black";
			table.childNodes[0].appendChild(td);

			// add a row for each student
			for (var i = 0; i < students.length; ++i) {
				table.appendChild(students[i].tr = document.createElement("TR"));
				// add the final grade column
				students[i].tr.appendChild(students[i].td_final_grade = document.createElement("TD"));
				students[i].td_final_grade.style.border = "1px solid black";
				<?php PNApplication::$instance->widgets->create_typed_field($this, "students[i].field_final_grade", "StudentSubjectGrade", "grade", "false", "students[i].final_grade");?>
				students[i].td_final_grade.appendChild(students[i].field_final_grade.getHTMLElement());
				students[i].field_final_grade.student = students[i];
				students[i].field_final_grade.onchange.add_listener(function(f) {
					f.student.final_grade = f.getCurrentData();
				});
			}

			// add information about the students
			add_student_field({name:"first_name",display:"First Name"});
			add_student_field({name:"last_name",display:"Last Name"});

			// add evaluation types and evaluations
			for (var type_i = 0; type_i < types.length; ++type_i) {
				var type = types[type_i];
				add_evaluation_type(type);
				for (var eval_i = 0; eval_i < type.evaluations.length; ++eval_i) {
					var eval = type.evaluations[eval_i];
					add_eval(type, eval);
				}
			}
		}
		create_table();

		var edit_mode = false;
		function edit() {
			// subject info
			field_subject_weight.setEditable(true);
			field_subject_max_grade.setEditable(true);
			field_subject_passing_grade.setEditable(true);
			var radios = document.forms['only_final_grade_selection'].elements['only_final_grade'];
			for (var i = 0; i < radios.length; ++i)
				radios[i].disabled = '';

			if (!subject_info.only_final_grade) {
				_show(document.getElementById('edit_buttons'));
				// TODO enable editing the grades
			} else {
				// enable editing the final grades
				for (var i = 0; i < students.length; ++i)
					students[i].field_final_grade.setEditable(true);
			}
			
			edit_mode = true;
			var edit = document.getElementById('edit_button');
			edit.innerHTML = "<img src='"+theme.icons_16.save+"'/> Save";
			edit.onclick = function() { save(); };
		}

		function grade_type_changed() {
			var grade_type = document.forms['only_final_grade_selection'].elements['only_final_grade'].value;
			if (grade_type == 'true') {
				if (types.length > 0) {
					confirm_dialog("Are you sure you want to enter only the final grade ? This will remove all evaluations.",function(yes){
						if (!yes) {
							document.forms['only_final_grade_selection'].elements['only_final_grade'].value = 'false';
							return;
						}
						subject_info.only_final_grade = true;
						show_only_final_grade();
					});
				} else {
					subject_info.only_final_grade = true;
					show_only_final_grade();
				}
			} else {
				var has_final = false;
				for (var i = 0; i < students.length; ++i)
					if (students[i].final_grade != null) { has_final = true; break; }
				if (has_final) {
					confirm_dialog("Are you sure you want to specify all evaluations ? This is remove all final grades already entered, because the final grades will now be computed based on evaluations.",function(yes){
						if (!yes) {
							document.forms['only_final_grade_selection'].elements['only_final_grade'].value = 'true';
							return;
						}
						subject_info.only_final_grade = false;
						show_evaluations();
					}); 
				} else {
					subject_info.only_final_grade = false;
					show_evaluations();
				}
			}
		}

		function show_only_final_grade() {
			_hide(document.getElementById('edit_buttons'));
			// remove headers
			while (table.childNodes[0].childNodes.length > student_fields.length+1)
				table.childNodes[0].removeChild(table.childNodes[0].childNodes[table.childNodes[0].childNodes.length-2]);
			while (table.childNodes[1].childNodes.length > 0)
				table.childNodes[1].removeChild(table.childNodes[1].childNodes[0]);
			while (table.childNodes[2].childNodes.length > 0)
				table.childNodes[2].removeChild(table.childNodes[2].childNodes[0]);
			while (table.childNodes[3].childNodes.length > 0)
				table.childNodes[3].removeChild(table.childNodes[3].childNodes[0]);
			// remove columns for each student
			for (var i = 0; i < students.length; ++i)
				while (students[i].tr.childNodes.length > student_fields.length+1)
					students[i].tr.removeChild(students[i].tr.childNodes[students[i].tr.childNodes.length-2]);
			// reset all information
			evaluations = [];
			// TODO reset info in students
			// enable editing the final grades
			for (var i = 0; i < students.length; ++i)
				students[i].field_final_grade.setEditable(true);
		}
		function show_evaluations() {
			_show(document.getElementById('edit_buttons'));
			// disable editing the final grades
			for (var i = 0; i < students.length; ++i)
				students[i].field_final_grade.setEditable(false);
		}

		function new_evaluation_type() {
			var t = {id:-1,name:"",weight:1,evaluations:[]};
			types.push(t);
			add_evaluation_type(t);
		}
		function new_evaluation(type) {
		}
		</script>
		<?php 
	}
	
}
?>