<?php
$category = new datamodel\DataDisplayCategory("Student", "/static/students/student_16.png");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("StudentBatch", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("name", "Batch Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("start_date", "Integration Date", true));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("end_date", "Graduation Date", true));
$model->addTableDataDisplay($display);

class TeacherDataScreen implements datamodel\SimpleDataScreen {
	
	public function getTables() { return array("TeacherDates"); }
	
	public function getIcon() { return "/static/curriculum/teacher_16.png"; }
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
		if ($people_id <> -1) $dates = SQLQuery::create()->select("TeacherDates")->whereValue("TeacherDates", "people", $people_id)->orderBy("TeacherDates", "start")->execute();
?>
<table><tbody id='teacher_dates_table'><tr>
		<td colspan=4>
			<a href='#' style='color:#808080;font-style:italic;' onclick='add_teacher_dates();return false;'>Add dates</a>
		</td>
	</tr></tbody></table>
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

function add_teacher_dates(dates) {
	if (!dates) {
		dates = {id:teacher_dates_id_counter--,people:people_id,start:null,end:null};
		teacher_dates.push(dates);
	}
	var table = document.getElementById('teacher_dates_table');
	var tr = document.createElement("TR"); table.insertBefore(tr, table.childNodes[table.childNodes.length-1]);
	var td;
	tr.appendChild(td = document.createElement("TD"));
	td.appendChild(document.createTextNode("Started on "));
	tr.appendChild(td = document.createElement("TD"));
	window.top.datamodel.create_cell("TeacherDates",null,"start",dates.id,dates.start,"field_date", {can_be_empty:false,minimum:"2004-01-01", maximum_cell:"end"}, can_edit_teacher_dates,td,function(value){dates.start = value;});
	tr.appendChild(td = document.createElement("TD"));
	td.appendChild(document.createTextNode("Ended on "));
	tr.appendChild(td = document.createElement("TD"));
	window.top.datamodel.create_cell("TeacherDates",null,"end",dates.id,dates.end,"field_date", {can_be_empty:true,minimum_cell:"start"}, can_edit_teacher_dates,td,function(value){dates.end = value;});
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