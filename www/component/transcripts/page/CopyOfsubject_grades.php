<?php 
class page_subject_grades extends Page {
	
	public function getRequiredRights() { return array("consult_students_grades"); }
	
	public function execute() {
		$subject_id = $_GET["subject"];
		
		// get subject
		$subject = SQLQuery::create()
		->select("CurriculumSubject")
		->whereValue("CurriculumSubject", "id", $_GET["subject"])
		->executeSingleRow();
		$subject_grading = SQLQuery::create()
		->select("CurriculumSubjectGrading")
		->where("subject", $subject["id"])
		->executeSingleRow();
		
		// get period
		$period = SQLQuery::create()
		->select("AcademicPeriod")
		->whereValue("AcademicPeriod", "id", $subject["period"])
		->executeSingleRow();
		
		// get specialization or null
		$specialization = $subject["specialization"] <> null ? SQLQuery::create()->select("Specialization")->where("id",$subject["specialization"])->executeSingleRow() : null;
		
		// get batch
		$batch = SQLQuery::create()->select("StudentBatch")->where("id", $period["batch"])->executeSingleRow();
		
		// get list of classes
		$class_id = @$_GET["class"];
		if ($class_id == null) {
			$q = SQLQuery::create()
			->select("AcademicClass")
			->where("period", $subject["period"])
			;
			if ($specialization <> null)
				$q->whereValue("AcademicClass", "specialization", $specialization["id"]);
			else
				$q->whereNull("AcademicClass", "specialization");
			$classes = $q->execute();
			$class = null;
		} else {
			$class = SQLQuery::create()->select("AcademicClass")->where("id", $class_id)->executeSingleRow();
			$classes = array($class);
		}
		$classes_ids = array();
		foreach ($classes as $c) array_push($classes_ids, $c["id"]);
		
		// build the table with students info
		require_once("component/data_model/page/custom_data_list.inc");
		$available_fields = PNApplication::$instance->data_model->getAvailableFields("StudentClass");
		for ($i = 0; $i < count($available_fields); $i++) {
			$f = $available_fields[$i];
			if ($f[0]->handler->category <> "Personal Information" &&
				$f[0]->handler->category <> "Student") {
				array_splice($available_fields, $i, 1);
				$i--;
			}
		}
		$filters = array();
		array_push($filters, array(
			"category"=>"Student",
			"name"=>"Period",
			"data"=>array("value"=>$period["id"])
		));
		if ($specialization <> null)
			array_push($filters, array(
				"category"=>"Student",
				"name"=>"Specialization",
				"data"=>array("value"=>$specialization["id"])
			));
		if ($class_id <> null)
			array_push($filters, array(
				"category"=>"Student",
				"name"=>"Class",
				"data"=>array("value"=>$class_id)
			));
		$data = custom_data_list($this, "StudentClass", null, $available_fields, $filters);
		
		// get evaluations
		if ($subject_grading <> null && !$subject_grading["only_final_grade"]) {
			$types = SQLQuery::create()
				->select("CurriculumSubjectEvaluationType")
				->where("subject", $subject_id)
				->execute();
			foreach ($types as &$type) {
				$type["evaluations"] = SQLQuery::create()
					->select("CurriculumSubjectEvaluation")
					->where("type", $type["id"])
					->execute();
			}			
		} else
			$types = array();
		$types_ids = array();
		$evaluations_ids = array();
		foreach ($types as &$type) {
			array_push($types_ids, $type["id"]);
			foreach ($type["evaluations"] as $eval)
				array_push($evaluations_ids, $eval["id"]);
		}
		
		// get students grades
		$people_id_alias = $data["query"]->getFieldAlias("People", "id");
		$students_ids = array();
		foreach ($data["data"] as $row)
			array_push($students_ids, $row[$people_id_alias]);
		$final_grades = count($students_ids) == 0 ? array() : SQLQuery::create()
			->select("StudentSubjectGrade")
			->whereValue("StudentSubjectGrade", "subject", $subject_id)
			->whereIn("StudentSubjectGrade", "people", $students_ids)
			->execute();
		$types_grades = count($types_ids) == 0 || count($students_ids) == 0 ? array() : SQLQuery::create()
			->select("StudentSubjectEvaluationTypeGrade")
			->whereIn("StudentSubjectEvaluationTypeGrade", "type", $types_ids)
			->whereIn("StudentSubjectEvaluationTypeGrade", "people", $students_ids)
			->execute();
		$eval_grades = count($evaluations_ids) == 0 || count($students_ids) == 0 ? array() : SQLQuery::create()
			->select("StudentSubjectEvaluationGrade")
			->whereIn("StudentSubjectEvaluationGrade", "evaluation", $evaluations_ids)
			->whereIn("StudentSubjectEvaluationGrade", "people", $students_ids)
			->execute();
		
		$can_edit = PNApplication::$instance->user_management->has_right("edit_students_grades");
		
		$this->addJavascript("/static/widgets/header_bar.js");
		$this->addJavascript("/static/widgets/vertical_layout.js");
		$this->addStylesheet("/static/transcripts/grades.css");
		?>
		<style type='text/css'>
		#data_list_container table {
			border-collapse: collapse;
			border-spacing: 0px;
		}
		#data_list_container th, #data_list_container td {
			border: 1px solid black;
			padding: 1px;
		}
		.subject_header_table, .subject_header_table input {
			font-size:8pt;
			font-family:Verdana;
		}
		table.subject_header_table {
			border-collapse: collapse;
			border-spacing: 0px;
		}
		.subject_header_field {
			background-color:#FFFFFF;
		}
		.subject_header_field input {
			padding: 0px;
			margin: 0px;
		}
		</style>
		<div id='page_container' style='width:100%;height:100%'>
			<div id='subject_grades_header'>
				<?php if ($can_edit) {?>
					<div class='button' onclick="edit();" id='edit_button'><img src='<?php echo theme::$icons_16["edit"];?>'/> Edit</div>
					<div class='button' onclick="save();" id='save_button' style='visibility:hidden;position:absolute'><img src='<?php echo theme::$icons_16["save"];?>'/> Save</div>
				<?php } ?>
				<div class='button' onclick="location.href='/dynamic/transcripts/page/students_grades?<?php if ($class <> null) echo "class=".$class_id; else echo "period=".$period["id"];?>';">
					<img src='<?php echo theme::$icons_16["left"];?>'/>
					Back to general grades
				</div>
			</div>
			<div id='header2'>
				<form name='only_final_grade_selection' onsubmit='return false;'>
				<table class='subject_header_table'>
					<tr>
						<td>Subject Code</td>
						<td class='subject_header_field'><?php echo $subject["code"];?></td>
						<td>Maximum Grade</td>
						<td id='subject_max_grade' class='subject_header_field'></td>
						<td>
							<input type='radio' name='only_final_grade' value='true' disabled='disabled' onchange='grade_type_changed();'/> Give only a final grade for this subject
						</td>
					</tr>
					<tr>
						<td>Subject Name</td>
						<td class='subject_header_field'><?php echo $subject["name"];?></td>
						<td>Passing Grade</td>
						<td id='subject_passing_grade' class='subject_header_field'></td>
						<td>
							<input type='radio' name='only_final_grade' value='false' disabled='disabled' onchange='grade_type_changed();'/> Specify evaluations, and automatically compute the final grade<br/>
						</td>
					</tr>
					<tr>
						<td>Coefficient</td>
						<td id='subject_weight' class='subject_header_field'></td>
						<td></td>
						<td style=''></td>
						<td></td>
					</tr>
				</table>
				</form>
				<div style='border-top: 1px solid #E0E0F0'>
					<div class='button' onclick='select_students_columns(this);'><img src='/static/data_model/table_column.png'/>Select students information to display</div>
					<?php if ($can_edit) {?>
					<div class='button' id='new_evaluation_type_button' onclick='new_evaluation_type();' style='visibility:hidden;position:absolute;top:-10000px'>New type of evaluation</div>
					<div class='button' id='new_evaluation_button' onclick='new_evaluation_menu(this);' style='visibility:hidden;position:absolute;top:-10000px'>New evaluation</div>
					<div class='button' id='import_grades_button' onclick='import_grades();' style='visibility:hidden;position:absolute;top:-10000px'><img src='/static/data_import/import_excel_16.png'/> Import grades</div>
					<?php } ?>
				</div>
			</div>
			<div id='grades_container' layout='fill' style='overflow:auto'>
				<div id='data_list_container'>
				</div>
			</div>
		</div>
		
		<script type='text/javascript'>
		function style() {
			var e = document.getElementById("header2");
			setBackgroundGradient(e, "vertical", [{pos:0,color:"#F0F0F0"},{pos:100,color:"#D0D0D0"}]);
			e.style.borderBottom = "1px solid #D0D0F0";
			setBorderRadius(e, 0, 0, 0, 0, 8, 8, 8, 8);
			e.style.marginBottom = "5px";
			setBoxShadow(e, 3, 3, 3, 0, "#E0E0F0");
		}
		style();
		var subject_info = <?php
			if ($subject_grading == null) echo "{id:".$subject_id.",weight:1,passing_grade:50,max_grade:100,only_final_grade:true}";
			else {
				echo "{";
				echo "id:".$subject_id;
				echo ",weight:".$subject_grading["weight"];
				echo ",passing_grade:".$subject_grading["passing_grade"];
				echo ",max_grade:".$subject_grading["max_grade"];
				echo ",only_final_grade:".($subject_grading["only_final_grade"] == 1 ? "true" : "false");
				echo "}";
			} 
		?>;
		var types = [<?php
		$first_type = true;
		foreach ($types as &$type) {
			if ($first_type) $first_type = false; else echo ",";
			echo "{";
			echo "id:".$type["id"];
			echo ",name:".json_encode($type["name"]);
			echo ",weight:".$type["weight"];
			echo ",evaluations:[";
			$first_eval = true;
			foreach ($type["evaluations"] as $eval) {
				if ($first_eval) $first_eval = false; else echo ",";
				echo "{";
				echo "id:".$eval["id"];
				echo ",name:".json_encode($eval["name"]);
				echo ",weight:".$eval["weight"];
				echo ",max_grade:".$eval["max_grade"];
				echo "}";
			}
			echo "]";
			echo "}";
		} 
		?>];
		var students_grades = [<?php
		$first_student = true;
		foreach ($students_ids as $student_id) {
			if ($first_student) $first_student = false; else echo ",";
			echo "{";
			echo "people:".$student_id;
			echo ",final_grade:";
			$found = false;
			foreach ($final_grades as $grade)
				if ($grade["people"] == $student_id) { echo json_encode($grade["grade"]); $found = true; break; }
			if (!$found) echo "null";
			echo ",types_grades:[";
			$first_grade = true;
			foreach ($types_grades as $grade) {
				if ($grade["people"] <> $student_id) continue;
				if ($first_grade) $first_grade = false; else echo ",";
				echo "{";
				echo "type_id:".$grade["type"];
				echo ",grade:".json_encode($grade["grade"]);
				echo "}";
			}
			echo "]";
			echo ",eval_grades:[";
			$first_grade = true;
			foreach ($eval_grades as $grade) {
				if ($grade["people"] <> $student_id) continue;
				if ($first_grade) $first_grade = false; else echo ",";
				echo "{";
				echo "eval_id:".$grade["evaluation"];
				echo ",grade:".json_encode($grade["grade"]);
				echo "}";
			}
			echo "]";
			echo "}";
		} 
		?>];
		var field_subject_weight, field_subject_max_grade, field_subject_passing_grade;
		function init_page() {
			var header = new header_bar('subject_grades_header', 'small');
			var title = document.createElement("SPAN");
			title.innerHTML = "<img src='/static/transcripts/grades.gif' style='vertical-align:bottom;padding-right:3px'/>";
			title.style.fontWeight = 'normal';
			title.appendChild(document.createTextNode("Grades for Period "));
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
				for (var i = 0; i < students_grades.length; ++i) {
					students_grades[i].field_final_grade.config.max = subject_info.max_grade;
					if (subject_info.only_final_grade) {
						if (students_grades[i].field_final_grade.getCurrentData() > subject_info.max_grade) {
							students_grades[i].field_final_grade.setData(subject_info.max_grade);
							students_grades[i].final_grade = subject_info.max_grade;
							update_grade_color(students_grades[i].td_final_grade, students_grades[i].final_grade, subject_info.passing_grade, subject_info.max_grade);
						}
					} else
						calculate_all_grades();
				}
			});
			<?php PNApplication::$instance->widgets->create_typed_field($this, "field_subject_passing_grade", "CurriculumSubjectGrading", "passing_grade", "false", $subject_grading == null || $subject_grading["passing_grade"] == null ? "50" : $subject_grading["passing_grade"]);?>
			document.getElementById('subject_passing_grade').appendChild(field_subject_passing_grade.getHTMLElement());
			field_subject_passing_grade.onchange.add_listener(function(f){
				subject_info.passing_grade = f.getCurrentData();
				for (var i = 0; i < students_grades.length; ++i)
					update_grade_color(students_grades[i].td_final_grade, students_grades[i].final_grade, subject_info.passing_grade, subject_info.max_grade);
			});

			custom_data_list.init('data_list_container');
			custom_data_list.select_field('Personal Information', 'First Name', true, customize_student_header);
			custom_data_list.select_field('Personal Information', 'Last Name', true, customize_student_header);
			<?php if (count($classes) > 1) {?>
			custom_data_list.select_field('Student', 'Class', true, customize_student_header);
			<?php } ?>
			
			var only_final_grade = document.forms['only_final_grade_selection'].elements['only_final_grade'];
			if (subject_info.only_final_grade) {
				only_final_grade.value = 'true';
				show_only_final_grades();
			} else {
				only_final_grade.value = 'false';
				show_evaluations();
			}

			new vertical_layout('page_container');
		}
		function customize_student_header(th) {
			th.className = "grades_student_info_header";
		}
		init_page();

		function select_students_columns(button) {
			var div = document.createElement("DIV");
			for (var i = 0; i < custom_data_list.fields_from_request.length; ++i) {
				var f = custom_data_list.fields_from_request[i];
				var cb = document.createElement("INPUT");
				cb.type = 'checkbox';
				cb.checked = custom_data_list.selected_fields_from_request.contains(i) ? 'checked' : '';
				cb.f = f;
				cb.onchange = function() {
					custom_data_list.select_field(this.f.category, this.f.name, this.checked, customize_student_header);
				};
				div.appendChild(cb);
				div.appendChild(document.createTextNode(f.name));
				div.appendChild(document.createElement("BR"));
			}
			require("context_menu.js",function() {
				var menu = new context_menu();
				menu.addItem(div, true);
				menu.showBelowElement(button);
			});
		}

		var edit_mode = false;
		function edit() {
			edit_mode = true;
			// subject info
			field_subject_weight.setEditable(true);
			field_subject_max_grade.setEditable(true);
			field_subject_passing_grade.setEditable(true);
			var radios = document.forms['only_final_grade_selection'].elements['only_final_grade'];
			for (var i = 0; i < radios.length; ++i)
				radios[i].disabled = '';

			if (!subject_info.only_final_grade)
				show_evaluations();
			else
				show_only_final_grades();
			
			var button = document.getElementById('edit_button');
			button.innerHTML = "<img src='"+theme.icons_16.no_edit+"'/> Cancel";
			button.onclick = function() { location.reload(); };
			button = document.getElementById('save_button');
			button.style.visibility = 'visible';
			button.style.position = 'static';

			require(["upload.js","popup_window.js"],function() {
				button = document.getElementById("import_grades_button");
				button.style.visibility = 'visible';
				button.style.position = 'static';
			});			
		}
		function save() {
			var locker = lock_screen(null, "Saving subject information");
			service.json("transcripts","save_subject_grading_info",subject_info,function(res){
				if (!res) { unlock_screen(locker); return; }
				if (subject_info.only_final_grade) {
					set_lock_screen_content(locker, "Saving students' grades");
					var students = [];
					for (var i = 0; i < students_grades.length; ++i) {
						var s = {
							people:students_grades[i].people,
							final_grade:students_grades[i].final_grade
						};
						students.push(s);
					}
					service.json("transcripts","save_students_final_grade",{subject_id:subject_info.id,students:students},function(res){
						if (!res) { unlock_screen(locker); return; }
						set_lock_screen_content(locker, "Grades successfully saved<br/>Reloading data...");
						location.reload();
					});
				} else {
					set_lock_screen_content(locker, "Saving evaluations");
					service.json("transcripts","save_subject_evaluations",{subject_id:subject_info.id, types:types},function(res){
						if (!res) { unlock_screen(locker); return; }
						// update ids in types, evaluations and students_grades
						for (var i = 0; i < types.length; ++i) {
							if (types[i].id < 0) {
								for (var j = 0; j < res.types.length; ++j)
									if (res.types[j].input_id == types[i].id) {
										types[i].id = res.types[j].output_id;
										for (var k = 0; k < students_grades.length; ++k) {
											for (var l = 0; l < students_grades[k].types_grades.length; ++l) {
												if (students_grades[k].types_grades[l].type_id == res.types[j].input_id) {
													students_grades[k].types_grades[l].type_id = res.types[j].output_id;
													break;
												}
											}
										}
										break;
									}
							}
							for (var j = 0; j < types[i].evaluations.length; ++j) {
								if (types[i].evaluations[j].id < 0) {
									for (var k = 0; k < res.evaluations.length; ++k) {
										if (res.evaluations[k].input_id == types[i].evaluations[j].id) {
											types[i].evaluations[j].id = res.evaluations[k].output_id;
											for (var l = 0; l < students_grades.length; ++l) {
												for (var m = 0; m < students_grades[l].eval_grades.length; ++m) {
													if (students_grades[l].eval_grades[m].eval_id == res.evaluations[k].input_id) {
														students_grades[l].eval_grades[m].eval_id = res.evaluations[k].output_id;
														break;
													}
												}
											}
											break;
										}
									}
								}
							}
						}
						// save students grades
						set_lock_screen_content(locker, "Saving students' grades");
						var students = [];
						for (var i = 0; i < students_grades.length; ++i) {
							var s = {
								people:students_grades[i].people,
								grades: []
							};
							for (var j = 0; j < students_grades[i].eval_grades.length; ++j)
								s.grades.push({evaluation:students_grades[i].eval_grades[j].eval_id, grade: students_grades[i].eval_grades[j].grade});
							students.push(s);
						}
						service.json("transcripts","save_students_evaluations_grades",{subject_id:subject_info.id,students:students},function(res){
							if (!res) { unlock_screen(locker); return; }
							set_lock_screen_content(locker, "Grades successfully saved<br/>Reloading data...");
							location.reload();
						});
					});
				} 
			});
		}

		function grade_type_changed() {
			var grade_type = document.forms['only_final_grade_selection'].elements['only_final_grade'].value;
			if (grade_type == 'true') {
				subject_info.only_final_grade = true;
				show_only_final_grades();
			} else {
				// remove all final grades
				for (var i = 0; i < students_grades.length; ++i)
					students_grades[i].final_grade = null; 
				subject_info.only_final_grade = false;
				show_evaluations();
			}
		}
		
		function update_grade_color(element, grade, passing, max) {
			if (grade == null)
				element.style.backgroundColor = "#C0C0C0";
			else if (grade < passing)
				element.style.backgroundColor = "#FF4040";
			else if (grade < passing+(max-passing)/5) // until than 20% above passing grade
				element.style.backgroundColor = "#FFA040";
			else
				element.style.backgroundColor = "#40FF40";
		}

		function show_only_final_grades() {
			if (edit_mode) {
				var e = document.getElementById('new_evaluation_type_button');
				e.style.visibility = 'hidden';
				e.style.position = 'absolute';
				e = document.getElementById('new_evaluation_button');
				e.style.visibility = 'hidden';
				e.style.position = 'absolute';
			}
			custom_data_list.resetColumns();
			custom_data_list.addColumn("final_grade","Final Grade",function(td,index) {
				var grade = students_grades[index].final_grade;
				<?php PNApplication::$instance->widgets->create_typed_field($this, "students_grades[index].field_final_grade", "StudentSubjectGrade", "grade", "edit_mode", "grade");?>
				td.appendChild(students_grades[index].field_final_grade.getHTMLElement());
				td.style.textAlign = 'right';
				students_grades[index].td_final_grade = td;
				update_grade_color(td, grade, subject_info.passing_grade, subject_info.max_grade);				
				students_grades[index].field_final_grade.onchange.add_listener(function(){
					students_grades[index].final_grade = students_grades[index].field_final_grade.getCurrentData();
					update_grade_color(td, students_grades[index].final_grade, subject_info.passing_grade, subject_info.max_grade);
				});
			},null,function(th){
				th.className = "grades_total_header";
			});
		}
		function show_evaluations() {
			if (edit_mode) {
				var e = document.getElementById('new_evaluation_type_button');
				e.style.visibility = 'visible';
				e.style.position = 'static';
				e = document.getElementById('new_evaluation_button');
				e.style.visibility = 'visible';
				e.style.position = 'static';
			}
			custom_data_list.resetColumns();
			custom_data_list.addColumn("final_grade","Final<br/>Grade",function(td,index) {
				var grade = students_grades[index].final_grade;
				<?php PNApplication::$instance->widgets->create_typed_field($this, "students_grades[index].field_final_grade", "StudentSubjectGrade", "grade", "false", "grade");?>
				td.appendChild(students_grades[index].field_final_grade.getHTMLElement());
				td.style.textAlign = 'right';
				td.style.fontWeight = "bold";
				update_grade_color(td, grade, subject_info.passing_grade, subject_info.max_grade);
				students_grades[index].td_final_grade = td;
			},null,function(th){
				th.className = "grades_total_header";
			});
			for (var i = 0; i < types.length; ++i) {
				var type = types[i];
				add_type_column(type);
				for (var j = 0; j < type.evaluations.length; ++j)
					add_eval_column(type, type.evaluations[j]);
			}
			calculate_all_grades();
		}
		var col_id_counter = 0;
		function add_type_column(type) {
			if (!type.col_id)
				type.col_id = ++col_id_counter;

			var name_node = document.createTextNode(type.name);
			var coef_node = document.createTextNode(type.weight);
			
			var div = document.createElement("DIV");
			div.appendChild(name_node);
			if (edit_mode) {
				var menu_icon = document.createElement("IMG");
				menu_icon.className = "button";
				menu_icon.style.padding = "0px";
				menu_icon.src = theme.icons_10.arrow_down_context_menu;
				menu_icon.style.verticalAlign = "bottom";
				menu_icon.onclick = function() {
					require("context_menu.js", function() {
						var menu = new context_menu();
						menu.addIconItem(theme.icons_16.edit, "Edit", function() {
							edit_evaluation_type(type, function() {
								name_node.nodeValue = type.name;
								coef_node.nodeValue = type.weight;
								calculate_all_grades();
							});
						});
						menu.addIconItem(theme.icons_16.remove, "Remove", function() {
							confirm_dialog("Are you sure to remove the evaluation type '"+type.name+"', and all its content (evaluations and grades) ?",function(yes) {
								if (yes) {
									alert('Remove not yet done');
									//types.remove(type);
									//custom_data_list.removeColumn(type.col_id); // TODO does not work
								}
							});
						});
						menu.addSeparator();
						menu.addIconItem(theme.icons_16.add, "Create a new evaluation", function() {
							new_evaluation(type);
						});
						menu.showBelowElement(menu_icon);
					});
				};
				div.appendChild(menu_icon);
			}
			div.appendChild(document.createElement("BR"));
			var span = document.createElement("SPAN");
			span.style.fontWeight = "normal";
			span.appendChild(document.createTextNode("Coefficient "));
			span.appendChild(coef_node);
			div.appendChild(span);

			custom_data_list.addColumn("col_"+type.col_id, div, function(td,index){
			},"final_grade",function(th){
				th.className = "grades_category_header";
			});
			custom_data_list.addSubColumn("col_"+type.col_id, "col_"+type.col_id+"_total", "Total<br/>(%)", function(td,index) {
				var tg = null;
				for (var i = 0; i < students_grades[index].types_grades.length; ++i)
					if (students_grades[index].types_grades[i].type_id == type.id) { tg = students_grades[index].types_grades[i]; break; }
				if (tg == null) {
					tg = {type_id:type.id,grade:null};
					students_grades[index].types_grades.push(tg);
				}
				<?php PNApplication::$instance->widgets->create_typed_field($this, "tg.field", "StudentSubjectEvaluationTypeGrade", "grade", "false", "tg.grade");?>
				td.appendChild(tg.field.getHTMLElement());
				td.style.fontWeight = "bold";
				update_grade_color(td, tg.grade, subject_info.passing_grade, subject_info.max_grade);
				tg.td = td;
				td.style.textAlign = 'right';
			},null,function(th){
				th.className = "grades_total_header";
			});
		}
		function add_eval_column(type, eval) {
			if (!eval.col_id) eval.col_id = ++col_id_counter;

			var name_node = document.createTextNode(eval.name);
			var weight_node = document.createTextNode(eval.weight);
			var max_grade_node = document.createTextNode(eval.max_grade);
			
			var div = document.createElement("DIV");
			div.appendChild(name_node);
			
			if (edit_mode) {
				var menu_icon = document.createElement("IMG");
				menu_icon.className = "button";
				menu_icon.style.padding = "0px";
				menu_icon.src = theme.icons_10.arrow_down_context_menu;
				menu_icon.style.verticalAlign = "bottom";
				menu_icon.onclick = function() {
					require("context_menu.js", function() {
						var menu = new context_menu();
						menu.addIconItem(theme.icons_16.edit, "Edit", function() {
							edit_evaluation(eval, function() {
								name_node.nodeValue = eval.name;
								weight_node.nodeValue = eval.weight;
								max_grade_node.nodeValue = eval.max_grade;
								calculate_all_grades();
							});
						});
						menu.addIconItem(theme.icons_16.remove, "Remove", function() {
							confirm_dialog("Are you sure to remove the evaluation type '"+type.name+"', and all its content (evaluations and grades) ?",function(yes) {
								if (yes) {
									// TODO
									alert('Remove not yet done');
								}
							});
						});
						menu.showBelowElement(menu_icon);
					});
				};
				div.appendChild(menu_icon);
			}

			var sub_div = document.createElement("DIV");
			sub_div.style.fontWeight = "normal";
			div.appendChild(sub_div);
			sub_div.appendChild(document.createTextNode("Coef "));
			sub_div.appendChild(weight_node);
			sub_div.appendChild(document.createElement("BR"));
			sub_div.appendChild(document.createTextNode("Max "));
			sub_div.appendChild(max_grade_node);
			
			custom_data_list.addSubColumn("col_"+type.col_id, "col_"+eval.col_id, div, function(td,index) {
				var eg = null;
				for (var i = 0; i < students_grades[index].eval_grades.length; ++i)
					if (students_grades[index].eval_grades[i].eval_id == eval.id) { eg = students_grades[index].eval_grades[i]; break; }
				if (eg == null) {
					eg = {eval_id:eval.id,grade:null};
					students_grades[index].eval_grades.push(eg);
				}
				<?php PNApplication::$instance->widgets->create_typed_field($this, "eg.field", "StudentSubjectEvaluationGrade", "grade", "edit_mode", "eg.grade");?>
				td.appendChild(eg.field.getHTMLElement());
				td.style.textAlign = 'right';
				update_grade_color(td, eg.grade, subject_info.passing_grade * eval.max_grade / subject_info.max_grade, eval.max_grade);
				eg.field.onchange.add_listener(function(f){
					eg.grade = f.getCurrentData();
					update_grade_color(td, eg.grade, subject_info.passing_grade*eval.max_grade/subject_info.max_grade, eval.max_grade);
					calculate_grades(students_grades[index]);
				});
				calculate_grades(students_grades[index]);
			}, "col_"+type.col_id+"_total",function(th){
				th.className = "grades_sub_category_header";
			});
		}

		function calculate_all_grades() {
			for (var i = 0; i < students_grades.length; ++i)
				calculate_grades(students_grades[i]);
		}
		function calculate_grades(student) {
			var final_total = 0;
			var final_weights = 0;
			for (var i = 0; i < types.length; ++i) {
				var tg = null;
				for (var j = 0; j < student.types_grades.length; ++j)
					if (student.types_grades[j].type_id == types[i].id) { tg = student.types_grades[j]; break; }
				if (tg == null) {
					tg = {type_id:types[i].id,grade:null};
					student.types_grades.push(tg);
				}
				var total = 0;
				var weights = 0;
				for (var j = 0; j < types[i].evaluations.length; ++j) {
					var eg = null;
					for (var k = 0; k < student.eval_grades.length; ++k)
						if (student.eval_grades[k].eval_id == types[i].evaluations[j].id) { eg = student.eval_grades[k]; break; }
					if (eg == null) {
						eg = {eval_id:types[i].evaluations[j].id, grade:null};
						student.eval_grades.push(eg);
					}
					if (eg.grade == null) { total = null; break; }
					total += eg.grade * 100 / types[i].evaluations[j].max_grade * types[i].evaluations[j].weight;
					weights += types[i].evaluations[j].weight;
				}
				if (weights == 0) total = null;
				if (total == null) {
					final_total = null;
					tg.grade = null;
				} else {
					tg.grade = total * subject_info.max_grade / 100 / weights;
					if (tg.field)
						tg.field.setData(tg.grade);
					if (tg.td)
						update_grade_color(tg.td, tg.grade, subject_info.passing_grade, subject_info.max_grade);
					if (final_total != null) {
						final_total += tg.grade * types[i].weight;
						final_weights += types[i].weight;
					}
				}
			}
			if (final_weights == 0) final_total = null;
			if (final_total != null) {
				final_total /= final_weights;
				student.final_grade = final_total;
				if (student.field_final_grade)
					student.field_final_grade.setData(student.final_grade);
			} else {
				student.final_grade = null;
				if (student.field_final_grade)
					student.field_final_grade.setData(null);
			}
			if (student.td_final_grade)
				update_grade_color(student.td_final_grade, student.final_grade, subject_info.passing_grade, subject_info.max_grade);
		}

		var type_id_counter = -1, eval_id_counter = -1;
		function edit_evaluation_type(type, onok) {
			require("popup_window.js",function() {
				var table = document.createElement("TABLE");
				var tr_error = document.createElement("TR"); table.appendChild(tr_error);
				var td_error = document.createElement("TD"); tr_error.appendChild(td_error);
				td_error.colSpan = 2;
				tr_error.style.visibility = "hidden";
				tr_error.style.position = "absolute";
				var tr, td;
				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.innerHTML = "Name";
				tr.appendChild(td = document.createElement("TD"));
				var input_name = document.createElement("INPUT"); td.appendChild(input_name);
				input_name.maxLength = 100;
				input_name.value = type.name;
				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.innerHTML = "Coefficient";
				tr.appendChild(td = document.createElement("TD"));
				var input_weight = document.createElement("INPUT"); td.appendChild(input_weight);
				input_weight.maxLength = 100;
				input_weight.value = type.weight;
				var popup = new popup_window("Evaluation Type", null, table, false);
				popup.addOkCancelButtons(function() {
					var error = null;
					var name = input_name.value;
					name = name.trim();
					if (name.length == 0) error = "Please enter a name";
					else {
						for (var i = 0; i < types.length; ++i) {
							if (types[i].name.toLowerCase() == name.toLowerCase() && types[i].id != type.id) {
								error = "A type of evaluation already exists with this name";
								break;
							}
						}
					}
					var weight = input_weight.value.trim();
					if (!error) {
						if (weight.length == 0) error = "Please enter a coefficient";
						else {
							weight = parseInt(weight);
							if (isNaN(weight) || weight <= 0) error = "Invalid coefficient";
						}
					}
					if (!error) {
						type.name = name;
						type.weight = weight;
						popup.close();
						onok();
						return;
					}
					tr_error.style.visibility = "visible";
					tr_error.style.position = "static";
					td_error.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+error;
					td_error.style.color = 'red';
				});
				popup.show();
			});
		}
		function new_evaluation_type() {
			var type = {id:type_id_counter--,name:"",weight:"",evaluations:[]};
			edit_evaluation_type(type, function() {
				types.push(type);
				add_type_column(type);
			});
		}
		function edit_evaluation(eval, onok) {
			require("popup_window.js",function() {
				var table = document.createElement("TABLE");
				var tr_error = document.createElement("TR"); table.appendChild(tr_error);
				var td_error = document.createElement("TD"); tr_error.appendChild(td_error);
				td_error.colSpan = 2;
				tr_error.style.visibility = "hidden";
				tr_error.style.position = "absolute";
				var tr, td;
				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.innerHTML = "Name";
				tr.appendChild(td = document.createElement("TD"));
				var input_name = document.createElement("INPUT"); td.appendChild(input_name);
				input_name.maxLength = 100;
				input_name.value = eval.name;

				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.innerHTML = "Coefficient";
				tr.appendChild(td = document.createElement("TD"));
				var input_weight = document.createElement("INPUT"); td.appendChild(input_weight);
				input_weight.maxLength = 100;
				input_weight.value = eval.weight;

				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.innerHTML = "Maximum Grade";
				tr.appendChild(td = document.createElement("TD"));
				var input_max_grade = document.createElement("INPUT"); td.appendChild(input_max_grade);
				input_max_grade.maxLength = 100;
				input_max_grade.value = eval.max_grade;

				var popup = new popup_window("Evaluation", null, table, false);
				popup.addOkCancelButtons(function() {
					var error = null;
					var name = input_name.value;
					name = name.trim();
					if (name.length == 0) error = "Please enter a name";
					var weight = input_weight.value.trim();
					if (!error) {
						if (weight.length == 0) error = "Please enter a coefficient";
						else {
							weight = parseInt(weight);
							if (isNaN(weight) || weight <= 0) error = "Invalid coefficient";
						}
					}
					var max_grade = input_max_grade.value.trim();
					if (!error) {
						if (max_grade.length == 0) error = "Please enter a maximum grade";
						else {
							max_grade = parseInt(max_grade);
							if (isNaN(max_grade) || max_grade <= 0) error = "Invalid maximum grade";
						}
					}
					if (!error) {
						eval.name = name;
						eval.weight = weight;
						eval.max_grade = max_grade;
						popup.close();
						onok();
						return;
					}
					tr_error.style.visibility = "visible";
					tr_error.style.position = "static";
					td_error.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+error;
					td_error.style.color = 'red';
				});
				popup.show();
			});
		}
		function new_evaluation(type) {
			var eval = {id:eval_id_counter--,name:"",weight:1,max_grade:100};
			edit_evaluation(eval, function() {
				type.evaluations.push(eval);
				add_eval_column(type,eval);			
			});
		}

		function import_grades() {
			require("people_objects.js");
			var excel_frame = null;
			var peoples = null;
			var javascript_ready = false;
			var ready = function() {
				if (!excel_frame) return;
				if (!peoples) return;
				if (!javascript_ready) return;
				var excel_container = excel_frame.document.getElementById('excel_container');
				excel_frame.document.body.removeAllChildren();
				var container = excel_frame.document.createElement("DIV");
				container.style.width = "100%";
				container.style.height = "100%";
				excel_frame.document.body.appendChild(container);
				var wiz = new wizard_simple(container);
				require("splitter_vertical.js");
				match_people_in_excel(peoples, excel_frame, excel_container, wiz, function(matched_peoples, sheet_index) {
					wiz.resetContent();
					wiz.resetButtons();
					wiz.setTitle("/static/transcripts/grades_32.gif", "Select a column in the Excel file, and import the grades from this column");
					var split = document.createElement("DIV");
					var right = document.createElement("DIV");
					split.appendChild(excel_container);
					split.appendChild(right);
					wiz.setContent(split);
					require("splitter_vertical.js",function() {
						new splitter_vertical(split, 0.5);
					});
					right.style.overflow = "auto";
					// keep only the sheet containing the names, and so the grades
					for (var i = 0; i < sheet_index; ++i) excel_frame.excel.removeSheet(0);
					while (excel_frame.excel.sheets.length > 1) excel_frame.excel.removeSheet(1);
					var sheet = excel_frame.excel.sheets[0];
					// keep only the rows containing the names
					var first_row = sheet.rows.length;
					var last_row = -1;
					for (var i = 0; i < matched_peoples.length; ++i) {
						if (matched_peoples[i].row < first_row) first_row = matched_peoples[i].row; 
						if (matched_peoples[i].row > last_row) last_row = matched_peoples[i].row; 
					}
					// put the grades that can be imported
					var table = document.createElement("TABLE"); right.appendChild(table);
					table.className = 'all_borders';
					var tbody = document.createElement("TBODY"); table.appendChild(tbody);
					var tr, td;
					var import_grades = function(col) {
						var sel = sheet.getSelection();
						if (sel == null) return;
						for (var i = 0; i < matched_peoples.length; ++i) {
							tr = tbody.childNodes[2+i];
							td = tr.childNodes[col];
							var cell = sheet.getCell(sel.start_col,matched_peoples[i].row);
							if (cell != null)
								td.innerHTML = cell.getValue();
						}
					};
					tbody.appendChild(tr = document.createElement("TR"));
					tr.appendChild(td = document.createElement("TH"));
					td.style.verticalAlign = "bottom";
					td.rowSpan = 2;
					td.innerHTML = "First Name";
					tr.appendChild(td = document.createElement("TH"));
					td.style.verticalAlign = "bottom";
					td.rowSpan = 2;
					td.innerHTML = "Last Name";
					if (subject_info.only_final_grade) {
						tr.appendChild(td = document.createElement("TH"));
						td.style.verticalAlign = "bottom";
						td.innerHTML = "Final Grade";
						tbody.appendChild(tr = document.createElement("TR"));
						tr.appendChild(td = document.createElement("TD"));
						td.style.textAlign = "center";
						var button = document.createElement("IMG");
						button.src = theme.icons_16._import;
						button.className = "button";
						button.onclick = function() {
							import_grades(2);
						};
						td.appendChild(button);
					} else {
						// TODO
					}
					for (var i = 0; i < matched_peoples.length; ++i) {
						tbody.appendChild(tr = document.createElement("TR"));
						tr.appendChild(td = document.createElement("TD"));
						td.appendChild(document.createTextNode(matched_peoples[i].first_name));
						tr.appendChild(td = document.createElement("TD"));
						td.appendChild(document.createTextNode(matched_peoples[i].last_name));
						if (subject_info.only_final_grade) {
							tr.appendChild(td = document.createElement("TD"));
						} else {
							// TODO
						}
					}
					layout.invalidate(split);
				});
			};
			require(["match_people_in_excel.js","wizard_simple.js"], function() { javascript_ready = true; ready(); });
			upload_temp_file_now(function (temp_id) {
				var popup = new popup_window("Import Grades", "/static/data_import/import_excel_16.png", "");
				popup.setContentFrame("/dynamic/data_import/page/excel_upload?id="+temp_id);
				popup.showPercent(90,90);
				popup.freeze("Opening Excel file...");
				var check = function() {
					var w = getIFrameWindow(popup.content);
					if (!w.excel || !w.excel.tabs) {
						if (w.page_errors) {
							popup.unfreeze();
							return;
						}
						setTimeout(check, 100);
						return;
					}
					popup.unfreeze();
					excel_frame = w;
					ready();
				};
				setTimeout(check, 100);
				require("people_objects.js",function() {
					var list = [];
					for (var i = 0; i < students_grades.length; ++i) {
						var people_id = students_grades[i].people;
						var row_index = custom_data_list.getDataIndex("People", people_id);
						var first_name = custom_data_list.getData(row_index, "Personal Information", "First Name");
						var last_name = custom_data_list.getData(row_index, "Personal Information", "Last Name");
						var middle_name = custom_data_list.getData(row_index, "Personal Information", "Middle Name");
						var people = new People(people_id, first_name, last_name, middle_name);
						list.push(people);
					}
					peoples = list;
					ready();
				});
			});
		}
		</script>
		<?php 
	}
	
}
?>