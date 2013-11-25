<?php 
class page_subject_grades extends Page {
	
	public function get_required_rights() { return array("consult_students_grades"); }
	
	public function execute() {
		// get subject
		$subject = SQLQuery::create()
			->select("CurriculumSubject")
			->where_value("CurriculumSubject", "id", $_GET["subject"])
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
			->field("StudentClass", "people", "people")
			->field("People", "first_name", "first_name")
			->field("People", "last_name", "last_name")
			->execute();
		
		$this->add_javascript("/static/widgets/page_header.js");
		$this->add_javascript("/static/widgets/vertical_layout.js");
		?>
		<div id='page_container' style='width:100%;height:100%'>
			<div id='subject_grades_header'>
				<div class='button' onclick="location.href='/dynamic/transcripts/page/students_grades?<?php if ($class <> null) echo "class=".$class_id; else echo "period=".$period["id"];?>';">
					<img src='<?php echo theme::$icons_16["left"];?>'/>
					Back to general grades
				</div>
			</div>
			<div id='header2' style='background-color:#C0C0FF;border-bottom:1px solid #8080F0;'>
				TODO: infos...
				<div class='button'>New type of evaluation</div>
				<div class='button'>New evaluation</div>
			</div>
			<div id='grades_container' layout='fill' style='overflow:auto'>
			<table id='grades_table' style='border-collapse:collapse;border-spacing:0px;'>
			</table>
			</div>
		</div>
		
		<script type='text/javascript'>
		var students = [<?php
		$first_student = true;
		foreach ($students as $student) {
			if ($first_student) $first_student = false; else echo ",";
			echo "{";
			echo "people:".$student["people"];
			echo ",first_name:".json_encode($student["first_name"]);
			echo ",last_name:".json_encode($student["last_name"]);
			echo ",grades:[";
			echo "]";
			echo "}";
		} 
		?>];
		var fields = [
			{name:"first_name",display:"First Name",show:true}
			,{name:"last_name",display:"Last Name",show:true}
		];
		
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
		
		function create_table() {
			var table = document.getElementById('grades_table');
			while (table.childNodes.length > 0) table.removeChild(table.childNodes[0]);
			var tr, td;
			table.appendChild(tr = document.createElement("TR"));
			for (var i = 0; i < fields.length; ++i) {
				var field = fields[i];
				if (!field.show) continue;
				tr.appendChild(td = document.createElement("TH"));
				td.rowSpan = 4;
				td.style.verticalAlign = "bottom";
				td.style.border = "1px solid black";
				td.appendChild(document.createTextNode(field.display));
			}
			tr.appendChild(td = document.createElement("TH"));
			td.appendChild(document.createTextNode("Type"));
			td.style.textAlign = "right";
			td.style.fontStyle = "italic";
			td.style.backgroundColor = "#C0C0C0";
			td.style.border = "1px solid black";
			for (var type_i = 0; type_i < types.length; ++type_i) {
				var type = types[type_i];
				if (!type.show) continue;
				var evaluations = [];
				for (var i = 0; i < type.evaluations.length; ++i) if (type.evaluations[i].show) evaluations.push(type.evaluations[i]);
				tr.appendChild(td = document.createElement("TH"));
				td.colSpan = evaluations.length == 0 ? 1 : evaluations.length;
				td.style.border = "1px solid black";
				td.appendChild(document.createTextNode(type.name));
			}
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TH"));
			td.appendChild(document.createTextNode("Weight"));
			td.style.textAlign = "right";
			td.style.fontStyle = "italic";
			td.style.backgroundColor = "#C0C0C0";
			td.style.border = "1px solid black";
			for (var type_i = 0; type_i < types.length; ++type_i) {
				var type = types[type_i];
				if (!type.show) continue;
				var evaluations = [];
				for (var i = 0; i < type.evaluations.length; ++i) if (type.evaluations[i].show) evaluations.push(type.evaluations[i]);
				tr.appendChild(td = document.createElement("TH"));
				td.colSpan = evaluations.length == 0 ? 1 : evaluations.length;
				td.style.border = "1px solid black";
				td.appendChild(document.createTextNode(type.weight));
			}
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TH"));
			td.appendChild(document.createTextNode("Evaluation"));
			td.style.textAlign = "right";
			td.style.fontStyle = "italic";
			td.style.backgroundColor = "#C0C0C0";
			td.style.border = "1px solid black";
			for (var type_i = 0; type_i < types.length; ++type_i) {
				var type = types[type_i];
				if (!type.show) continue;
				var evaluations = [];
				for (var i = 0; i < type.evaluations.length; ++i) if (type.evaluations[i].show) evaluations.push(type.evaluations[i]);
				if (evaluations.length == 0) {
					tr.appendChild(td = document.createElement("TH"));
					continue;
				}
				for (var eval_i = 0; eval_i < evaluations.length; ++eval_i) {
					var eval = evaluations[eval_i];
					tr.appendChild(td = document.createElement("TH"));
					td.appendChild(document.createTextNode(eval.name));
					td.style.border = "1px solid black";
				}
			}
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TH"));
			td.appendChild(document.createTextNode("Weight"));
			td.style.textAlign = "right";
			td.style.fontStyle = "italic";
			td.style.backgroundColor = "#C0C0C0";
			td.style.border = "1px solid black";
			for (var type_i = 0; type_i < types.length; ++type_i) {
				var type = types[type_i];
				if (!type.show) continue;
				var evaluations = [];
				for (var i = 0; i < type.evaluations.length; ++i) if (type.evaluations[i].show) evaluations.push(type.evaluations[i]);
				if (evaluations.length == 0) {
					tr.appendChild(td = document.createElement("TH"));
					continue;
				}
				for (var eval_i = 0; eval_i < evaluations.length; ++eval_i) {
					var eval = evaluations[eval_i];
					tr.appendChild(td = document.createElement("TH"));
					td.appendChild(document.createTextNode(eval.weight));
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
				for (var type_i = 0; type_i < types.length; ++type_i) {
					var type = types[type_i];
					if (!type.show) continue;
					var evaluations = [];
					for (var i = 0; i < type.evaluations.length; ++i) if (type.evaluations[i].show) evaluations.push(type.evaluations[i]);
					if (evaluations.length == 0) {
						tr.appendChild(td = document.createElement("TD"));
						continue;
					}
					for (var eval_i = 0; eval_i < evaluations.length; ++eval_i) {
						var eval = evaluations[eval_i];
						tr.appendChild(td = document.createElement("TD"));
						td.appendChild(document.createTextNode("TODO"));
						td.style.border = "1px solid black";
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