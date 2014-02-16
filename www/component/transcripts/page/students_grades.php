<?php 
class page_students_grades extends Page {
	
	public function get_required_rights() { return array("consult_students_grades"); }
	
	public function execute() {
		$period_id = @$_GET["period"];
		if ($period_id == null) {
			$class_id = @$_GET["class"];
			if ($class_id == null) {
				echo "<img src='".theme::$icons_16["info"]."'/> ";
				echo "Please select a period or a class to display the grades of the students";
				return;
			}
			$class = SQLQuery::create()->select("AcademicClass")->whereValue("AcademicClass", "id", $class_id)->executeSingleRow();
			$period_id = $class["period"];
			$spe_id = $class["specialization"];
		} else {
			$class = null;
			$spe_id = @$_GET["specialization"];
		}
		$period = SQLQuery::create()->select("AcademicPeriod")->where("id",$period_id)->executeSingleRow();
		$spe = $spe_id <> null ? SQLQuery::create()->select("Specialization")->where("id",$spe_id)->executeSingleRow() : null;
		$q = SQLQuery::create()
			->select("CurriculumSubject")
			->whereValue("CurriculumSubject", "period", $period_id)
			->join("CurriculumSubject","CurriculumSubjectGrading",array("id"=>"subject"))
			->field("CurriculumSubject","id","id")
			->field("CurriculumSubject","category","category")
			->field("CurriculumSubject","code","code")
			->field("CurriculumSubject","name","name")
			->field("CurriculumSubjectGrading","weight","weight")
			->field("CurriculumSubjectGrading","passing_grade","passing_grade")
			->field("CurriculumSubjectGrading","max_grade","max_grade")
			;
		if ($spe_id <> null)
			$q->whereValue("CurriculumSubject", "specialization", $spe_id);
		else
			$q->whereNull("CurriculumSubject", "specialization");
		$subjects = $q->execute();
		if (count($subjects) == 0) {
			echo "<img src='".theme::$icons_16["info"]."' style='vertical-align:bottom'/> There is no subject for this period. Please edit the <a href='/dynamic/curriculum/page/curriculum?period=".$period_id."'>curriculum</a> first and add subjects.";
			return;
		}
		$categories = array();
		foreach ($subjects as $subject) {
			if (!isset($categories[$subject["category"]]))
				$categories[$subject["category"]] = SQLQuery::create()->select("CurriculumSubjectCategory")->where("id", $subject["category"])->executeSingleRow();
			if (!isset($categories[$subject["category"]]["subjects"]))
				$categories[$subject["category"]]["subjects"] = array();
			array_push($categories[$subject["category"]]["subjects"], $subject);
		}
		
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
		if ($spe_id <> null)
			array_push($filters, array(
				"category"=>"Student",
				"name"=>"Specialization",
				"data"=>array("value"=>$spe_id)
			));
		if ($class <> null)
			array_push($filters, array(
				"category"=>"Student",
				"name"=>"Class",
				"data"=>array("value"=>$class["id"])
			));
		$data = custom_data_list($this, "StudentClass", null, $available_fields, $filters);
		$people_id_alias = $data["query"]->getFieldAlias("People", "id");
		$students_ids = array();
		foreach ($data["data"] as $row)
			array_push($students_ids, $row[$people_id_alias]);
		
		if (count($students_ids) == 0) {
			echo "<div style='background-color:#ffffa0;border-bottom:1px solid #e0e0ff;padding:5px;font-family:Verdana'><img src='".theme::$icons_16["info"]."' style='vertical-align:bottom'/> There is no student for this period. Please <a href='/dynamic/training_education/page/list?period=".$period_id."'>add students</a> first.</div>";
			$students_grades = array();
		} else
			$students_grades = SQLQuery::create()->select("StudentSubjectGrade")->whereIn("StudentSubjectGrade","people", $students_ids)->execute();
		
		$this->add_javascript("/static/widgets/header_bar.js");
		$this->onload("new header_bar('grades_page_header', 'small');");
		$this->add_stylesheet("/static/transcripts/grades.css");
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
		</style>
		<div id='grades_page_header' icon='/static/transcripts/grades.gif' title="Grades for Period <?php echo $period["name"]; if ($spe <> null) echo ", Specialization ".$spe["name"]; if ($class<>null) echo ", Class ".$class["name"];?>">
			<div class='button' onclick='select_students_columns(this);'><img src='/static/data_model/table_column.png'/>Select students information to display</div>
			<div class='button' onclick="alert('Not yet done');"><img src='<?php echo theme::$icons_16["config"];?>'/>Configure Transcripts</div>
			<div class='button' onclick="alert('Not yet done');"><img src='/static/transcripts/grades.gif'/>See/Print Transcripts</div>
		</div>
		<div id='grades_container' layout='fill' style='overflow:auto'>
			<div id='data_list_container'>
			</div>
		</div>
		<script type='text/javascript'>
		var categories = [<?php
		$first_cat = true;
		foreach ($categories as $cat) {
			if ($first_cat) $first_cat = false; else echo ",";
			echo "{";
			echo "id:".$cat["id"];
			echo ",name:".json_encode($cat["name"]);
			echo ",subjects:[";
			$first_subject = true;
			foreach ($cat["subjects"] as $subject) {
				if ($first_subject) $first_subject = false; else echo ",";
				echo "{";
				echo "id:".$subject["id"];
				echo ",name:".json_encode($subject["name"]);
				echo ",code:".json_encode($subject["code"]);
				echo ",weight:".json_encode($subject["weight"]);
				echo ",max_grade:".json_encode($subject["max_grade"]);
				echo ",passing_grade:".json_encode($subject["passing_grade"]);
				echo "}";
			}
			echo "]";
			echo "}";
		} 
		?>];
		var students = [<?php
		$first_student = true;
		foreach ($students_ids as $student_id) {
			if ($first_student) $first_student = false; else echo ",";
			echo "{";
			echo "people:".$student_id;
			echo ",grades:[";
			$first = true;
			foreach ($subjects as $subject) {
				if ($first) $first = false; else echo ",";
				echo "{";
				echo "subject:".$subject["id"];
				echo ",grade:";
				$grade = null;
				foreach ($students_grades as $sg) {
					if ($sg["people"] <> $student_id) continue;
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
		function calculate_average() {
			for (var i = 0; i < students.length; ++i) {
				var student = students[i];
				var total = 0;
				var weights = 0;
				for (var j = 0; j < categories.length; ++j) {
					for (var k = 0; k < categories[j].subjects.length; ++k) {
						var subject = categories[j].subjects[k];
						if (subject.weight == null) {
							total = -1;
							break;
						}
						var found = false;
						for (var l = 0; l < student.grades.length; ++l) {
							if (student.grades[l].subject == subject.id) {
								if (student.grades[l].grade != null) {
									found = true;
									total += parseFloat(student.grades[l].grade)*100/subject.max_grade * parseInt(subject.weight);
									weights += parseInt(subject.weight);
								}
								break;
							}
						}
						if (!found) {
							total = -1;
							break;
						}
					}
					if (total == -1) break;
				}
				if (total == -1 || weights == 0) {
					student.average = null;
				} else {
					student.average = total/weights;
				}
			}
		}
		function calculate_rank() {
			if (students.length == 0) return;
			var list = [];
			for (var i = 0; i < students.length; ++i) list.push(students[i]);
			list.sort(function(a,b){
				if (a.average == null)
					return b.average == null ? 0 : -1;
				if (b.average == null) return 1;
				if (a.average < b.average) return 1;
				if (a.average > b.average) return -1;
				return 0;
			});
			var last_grade = null;
			var last_rank = 0;
			var last_rank_nb = 1;
			for (var i = 0; i < list.length; ++i) {
				if (list[i].average == null) {
					list[i].rank = null;
				} else if (list[i].average == last_grade) {
					list[i].rank = last_rank;
					last_rank_nb++;
				} else {
					list[i].rank = last_rank + last_rank_nb;
					last_rank = list[i].rank;
					last_rank_nb = 1;
					last_grade = list[i].average;
				}
			}
		}
		calculate_average();
		calculate_rank();

		function update_grade_color(element, grade, passing, max) {
			if (typeof grade == 'string') grade = parseFloat(grade);
			if (typeof passing == 'string') passing = parseFloat(passing);
			if (typeof max == 'string') max = parseFloat(max);
			if (grade == null)
				element.style.backgroundColor = "#C0C0C0";
			else if (grade < passing)
				element.style.backgroundColor = "#FF4040";
			else if (grade < passing+(max-passing)/5) // until 20% above passing grade
				element.style.backgroundColor = "#FFA040";
			else
				element.style.backgroundColor = "#40FF40";
		}

		function customize_student_header(th) {
			th.className = "grades_student_info_header";
		}
		function customize_total_header(th) {
			th.className = "grades_total_header";
		}
		function customize_category_header(th) {
			th.className = "grades_category_header";
		}
		function customize_subject_name_header(th) {
			th.className = "grades_sub_category_header";
		}
		function customize_subject_weight_header(th) {
			th.className = "grades_sub_category_header";
		}
		
		function init_table() {
			custom_data_list.init('data_list_container');
			custom_data_list.select_field('Personal Information', 'First Name', true, customize_student_header);
			custom_data_list.select_field('Personal Information', 'Last Name', true, customize_student_header);
			<?php if ($class == null) {?>
			custom_data_list.select_field('Student', 'Class', true, customize_student_header);
			<?php } ?>
			custom_data_list.addColumn('total_average', "Average", function(td,index) {
				if (students[index].average) td.innerHTML = students[index].average.toFixed(2);
				td.style.textAlign = 'center';
				td.style.fontWeight = 'bold';
			},null, customize_total_header);
			custom_data_list.addColumn('total_rank', "Rank", function(td,index) {
				if (students[index].average) td.innerHTML = students[index].rank;
				td.style.textAlign = 'center';
			},null, customize_total_header);
			
			for (var cat_i = 0; cat_i < categories.length; ++cat_i) {
				var cat = categories[cat_i];
				if (cat.subjects.length == 0) continue;
				custom_data_list.addColumn('cat_'+cat.id, cat.name, function(td,index){}, 'total_average', customize_category_header);
				for (var subject_i = 0; subject_i < cat.subjects.length; ++subject_i) {
					var subject = cat.subjects[subject_i];
					var link = document.createElement("A");
					link.appendChild(document.createTextNode(subject.code));
					link.href = "/dynamic/transcripts/page/subject_grades?subject="+subject.id<?php if ($class <> null) echo "+'&class=".$class_id."'";?>;
					link.style.color = "black";
					link.title = subject.name;
					custom_data_list.addSubColumn('cat_'+cat.id, 'subject_'+subject.id, link, function(td,index) {
					}, null, customize_subject_name_header);
					var span = document.createElement("SPAN");
					if (subject.weight) {
						span.appendChild(document.createTextNode("Coef."));
						span.appendChild(document.createElement("BR"));
						span.appendChild(document.createTextNode(subject.weight));
					}
					custom_data_list.addSubColumn('subject_'+subject.id, 'subject_'+subject.id+"_weight", span, function(td,index) {
						var student = students[index];
						var grade = null;
						for (var i = 0; i < student.grades.length; ++i)
							if (student.grades[i].subject == subject.id) {
								grade = student.grades[i].grade;
								break;
							}
						var field;
						<?php PNApplication::$instance->widgets->create_typed_field($this, "field", "StudentSubjectGrade", "grade", "false", "grade");?>
						td.appendChild(field.getHTMLElement());
						update_grade_color(td, grade, subject.passing_grade, subject.max_grade);
						td.style.textAlign = 'center';
					}, null, customize_subject_weight_header);
				}
			}
		}
		init_table();

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
		
		</script>
		<?php 
	}
	
}
?>