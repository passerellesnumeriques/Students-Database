<?php 
class TeacherDataScreen implements datamodel\SimpleDataScreen {
	
	public function getTables() { return array("TeacherDates"); }
	
	public function getIcon() { return "/static/teaching/teacher_16.png"; }
	public function getName() { return "Teacher"; }
	
	public function generate(&$page, $paths, $values, $prefilled_values, $structure_name) {
		$people_types = $values->getColumnValue("People", "types");
		$people_types = PNApplication::$instance->people->parseTypes($people_types);
		if (!in_array("teacher", $people_types)) return;
		
		$page->requireJavascript("typed_field.js");
		$page->requireJavascript("field_date.js");
		if ($structure_name == null) $page->requireJavascript("editable_cell.js");
		if ($structure_name <> null)
			$people_id = -1;
		else 
			$people_id = $values->getColumnValue("People", "id");
		$dates = array();
		$can_edit = true;
		if ($people_id <> -1) {
			$dates = SQLQuery::create()->select("TeacherDates")->whereValue("TeacherDates", "people", $people_id)->orderBy("TeacherDates", "start")->execute();
			$can_edit = PNApplication::$instance->user_management->has_right("edit_curriculum");
			$q = SQLQuery::create()
				->select("TeacherAssignment")
				->whereValue("TeacherAssignment","people",$people_id)
				->join("TeacherAssignment","SubjectTeaching",array("subject_teaching"=>"id"))
				;
			PNApplication::$instance->curriculum->joinSubjects($q, "SubjectTeaching", "subject", true, true, true);
			$assignments = $q
				->field("CurriculumSubject","name","subject_name")
				->field("AcademicPeriod","start","start")
				->field("AcademicPeriod","end","end")
				->field("BatchPeriod","name","period_name")
				->field("StudentBatch","name","batch_name")
				->execute();
		}
?>
<table><tbody id='teacher_dates_table'><tr>
	<td colspan=4>
		<?php if ($can_edit) {?>
		<a href='#' style='color:#808080;font-style:italic;' onclick='add_teacher_dates();return false;'>Add dates</a>
		<?php } ?>
	</td>
</tr></tbody></table>
<?php if ($can_edit && $people_id <> -1 && count($assignments) == 0) {?>
<button class='action red' onclick='removeTeacher();'>Never been a teacher</button>
<?php } ?>
<script type='text/javascript'>
var people_id = <?php echo $people_id;?>;
var can_edit_teacher_dates = <?php echo $people_id == -1 || PNApplication::$instance->user_management->has_right("edit_curriculum") ? "true" : "false"; ?>;
var teacher_dates_id_counter = -1;
var teacher_dates = [<?php
	if ($people_id <> -1) {
		$first = true;
		foreach ($dates as $d) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$d["id"];
			echo ",people:".$people_id;
			echo ",start:".json_encode($d["start"]);
			echo ",end:".json_encode($d["end"]);
			echo "}";
		}
	}
?>];
<?php if ($people_id <> -1) echo "var assignments=".json_encode($assignments).";"; ?>

function add_teacher_dates(dates, before_tr) {
	if (!dates) {
		dates = {id:teacher_dates_id_counter--,people:people_id,start:null,end:null};
		teacher_dates.push(dates);
	}
	var table = document.getElementById('teacher_dates_table');
	if (!before_tr) before_tr = table.childNodes[table.childNodes.length-1];
	var tr = document.createElement("TR"); table.insertBefore(tr, before_tr);
	var td;
	var field_start = null, field_end = null;
	tr.appendChild(td = document.createElement("TD"));
	td.appendChild(document.createTextNode("Started on "));
	tr.appendChild(td = document.createElement("TD"));
	window.top.datamodel.create_cell(window, "TeacherDates",null,"start",dates.id,dates.start,"field_date", {can_be_null:false,minimum:"2004-01-01", maximum_cell:"end"}, can_edit_teacher_dates,td,function(value){dates.start = value;},function(f) {field_start=f;},function(new_date,ec){return checkNewStart(dates,new_date,ec);});
	tr.appendChild(td = document.createElement("TD"));
	td.appendChild(document.createTextNode("Ended on "));
	tr.appendChild(td = document.createElement("TD"));
	window.top.datamodel.create_cell(window, "TeacherDates",null,"end",dates.id,dates.end,"field_date", {can_be_null:true,minimum_cell:"start"}, can_edit_teacher_dates,td,function(value){dates.end = value;},function(f){field_end=f;},function(new_date,ec){return checkNewEnd(dates,new_date,ec);});
	if (people_id != -1 && dates.id < 0) {
		tr.appendChild(td = document.createElement("TD"));
		var save_button = document.createElement("BUTTON");
		save_button.className = "flat";
		save_button.innerHTML = "<img src='"+theme.icons_16.save+"'/> Save";
		td.appendChild(save_button);
		var cancel_button = document.createElement("BUTTON");
		cancel_button.className = "flat";
		cancel_button.innerHTML = "<img src='"+theme.icons_16.remove+"'/> Cancel";
		td.appendChild(cancel_button);
		cancel_button.onclick = function() {
			if (field_start) field_start.unregister_datamodel_cell();
			if (field_end) field_end.unregister_datamodel_cell();
			teacher_dates.remove(dates);
			tr.parentNode.removeChild(tr);
		};
		save_button.onclick = function() {
			var start = field_start.getCurrentData();
			if (!start) { alert("Please specify a starting date"); return; }
			var end = field_end.getCurrentData();
			if (!end)
				for (var i = 0; i < teacher_dates.length; ++i)
					if (teacher_dates[i].id > 0 && !teacher_dates[i].end) { alert("Only one end date can be empty"); return; }
			var a = valueCopy(teacher_dates,0);
			a.remove(dates);
			var d = valueCopy(dates,10);
			d.start = start;
			d.end = end;
			a.push(d);
			if (!validateAssignments(a)) return;
			var locker = lock_screen(null,"Saving new dates...");
			service.json("data_model","save_entity",{table:"TeacherDates",sub_model:null,field_people:people_id,field_start:start,field_end:end},function(res) {
				unlock_screen(locker);
				if (res && res.key) {
					field_start.unregister_datamodel_cell();
					field_end.unregister_datamodel_cell();
					dates.id = res.key;
					dates.start = start;
					dates.end = end;
					var next = tr.nextSibling;
					tr.parentNode.removeChild(tr);
					add_teacher_dates(dates, next);
				}
			});
		};
	} else {
		if (people_id == -1<?php if ($can_edit) echo " || true";?>) {
			tr.appendChild(td = document.createElement("TD"));
			var remove_button = document.createElement("BUTTON");
			remove_button.className = "flat small_icon";
			remove_button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
			td.appendChild(remove_button);
			remove_button.onclick = function() {
				if (people_id == -1) {
					if (teacher_dates.length < 2) { alert("At least one date must remain"); return; }
				} else {
					if (dates.id > 0) {
						var nb = 0;
						for (var i = 0; i < teacher_dates.length; ++i) if (teacher_dates[i].id > 0) nb++;
						if (nb < 2) { alert("At least one date must remain"); return; }
					}
				}
				var removed = function() {
					if (field_start) field_start.unregister_datamodel_cell();
					if (field_end) field_end.unregister_datamodel_cell();
					teacher_dates.remove(dates);
					tr.parentNode.removeChild(tr);
				};
				if (people_id == -1) removed();
				else {
					var a = valueCopy(teacher_dates,0);
					a.remove(dates);
					if (!validateAssignments(a)) return;
					var locker = lock_screen(null,"Removing dates...");
					service.json("data_model","remove_row",{table:"TeacherDates",row_key:dates.id},function(res) {
						unlock_screen(locker);
						if (res) removed();
					});
				}
			};
		}
	}
}

function validateTeacherDates() {
	var no_end_found = false;
	for (var i = 0; i < teacher_dates.length; ++i) {
		if (teacher_dates[i].start == null) return "Please specify a starting date for the teacher";
		if (teacher_dates[i].end == null) {
			if (no_end_found) return "Several dates for the teacher have no end date: only one can be empty, which is the current period";
			no_end_found = true;
		}
	}
}
function validateAssignments(new_dates) {
	var assignments_out = [];
	for (var i = 0; i < assignments.length; i++) {
		var start = parseSQLDate(assignments[i].start);
		var end = parseSQLDate(assignments[i].end);
		var found = false;
		for (var j = 0; j < new_dates.length; ++j) {
			var s = parseSQLDate(new_dates[j].start);
			var e = new_dates[j].end ? parseSQLDate(new_dates[j].end) : null;
			if (e) {
				if (s.getTime() < start.getTime() && e.getTime() > start.getTime()) { found = true; break; }
				if (s.getTime() >= start.getTime() && s.getTime() <= end.getTime()) { found = true; break; }
			} else {
				if (s.getTime() < end.getTime()) { found = true; break; }
			}
		}
		if (!found) assignments_out.push(assignments[i]);
	}
	if (assignments_out.length == 0) return true;
	var msg = "Invalid dates: this teacher is assigned to the following subjects:\n";
	for (var i = 0; i < assignments_out.length; ++i) {
		var a = assignments_out[i];
		msg += " - "+a.subject_name+" for Batch "+a.batch_name+", "+a.period_name+"\n";
	} 
	msg += "You must either unassign those subjects, or specify dates including those periods";
	alert(msg);
	return false;
}
function checkNewStart(dates, new_start, ec) {
	var a = valueCopy(teacher_dates,0);
	a.remove(dates);
	var d = valueCopy(dates, 10);
	d.start = new_start;
	a.push(d);
	if (validateAssignments(a)) return new_start;
	dates.start = ec.editable_field.field.getOriginalData();
	ec.editable_field.field.setData(dates.start);
	return dates.start;
}
function checkNewEnd(dates, new_end, ec) {
	var a = valueCopy(teacher_dates,0);
	a.remove(dates);
	var d = valueCopy(dates, 10);
	d.end = new_end;
	a.push(d);
	if (validateAssignments(a)) return new_end;
	dates.end = ec.editable_field.field.getOriginalData();
	ec.editable_field.field.setData(dates.end);
	return dates.end;
}
function removeTeacher() {
	popup_frame(null,"Remove Teacher","/dynamic/people/page/remove_people_type?people="+people_id+"&type=teacher&ontyperemoved=type_removed&onpeopleremoved=people_removed",null,null,null,function(frame,pop){
		frame.people_removed = function() {
			require("popup_window.js",function() {
				get_popup_window_from_frame(window).close();
			});
		};
		frame.type_removed = function() { location.reload(); };
	});
}

if (people_id == -1) add_teacher_dates();
else for (var i = 0; i < teacher_dates.length; ++i) add_teacher_dates(teacher_dates[i]);
<?php
if ($structure_name <> null) {
	echo "$structure_name.push({path:".json_encode($paths[0]->getString()).",dates:teacher_dates,getValue:function(){return this.dates;},validate:function(){return validateTeacherDates();}});";
} 
?>
</script>
<?php 
	}
	
	public function generateEndOfSection(&$page, $paths, $values, $prefilled_values, $structure_name) {}
	
	public function createData($paths, $multiple) {
		$path = $paths[0];
		$dates = $path->value;
		$people_id = $path->columns["people"];
		$rows = array();
		foreach ($dates as $d) {
			array_push($rows, array("people"=>$people_id,"start"=>$d["start"],"end"=>$d["end"]));
		}
		SQLQuery::create()->insertMultiple("TeacherDates", $rows);
	}
	
	
}

$model->addDataScreen(new TeacherDataScreen());
?>