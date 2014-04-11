<?php 
class page_edit_batch extends Page {
	
	public function get_required_rights() { return array("edit_curriculum"); }
	
	public function execute() {
		// lock specializations
		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		$lock_spe = DataBaseLock::lockTable("Specialization", $locked_by);
		if ($lock_spe == null) {
			echo "<div class='error'>Data are already edited by ".$locked_by.".</div>";
			return;
		}
		DataBaseLock::generateScript($lock_spe);
		if (isset($_GET["id"])) {
			$lock_batch = DataBaseLock::lockRow("StudentBatch", $_GET["id"], $locked_by);
			if ($lock_batch == null) {
				echo "<div class='error'>Data are already edited by ".$locked_by.".</div>";
				return;
			}
			DataBaseLock::generateScript($lock_batch);
		} else
			$lock_batch = null;
		
		$batch = null;
		if (isset($_GET["id"])) {
			$batch = PNApplication::$instance->curriculum->getBatch($_GET["id"]);
			$batch_periods = PNApplication::$instance->curriculum->getBatchPeriods($_GET["id"]);
			$periods_ids = array();
			foreach ($batch_periods as $period) array_push($periods_ids, $period["id"]);
			$periods_specializations = array();
			if (count($periods_ids) > 0) $periods_specializations = PNApplication::$instance->curriculum->getBatchPeriodsSpecializations($periods_ids);
		}
		
		$academic_years = PNApplication::$instance->curriculum->getAcademicYears();
		$academic_periods = PNApplication::$instance->curriculum->getAcademicPeriods();
		
		$conf = PNApplication::$instance->get_domain_descriptor();
		$conf = $conf["curriculum"];
		
		require_once("component/curriculum/CurriculumJSON.inc");
		$this->add_javascript("/static/curriculum/curriculum_objects.js");
		$this->require_javascript("input_utils.js");
?>
<table style='background-color:white;border-spacing:0px;margin:0px;border-collapse:collapse;'>
	<tr>
		<td class='help_header' style='height:75px;'>
			<table><tr>
				<td valign=middle><img src='<?php echo theme::$icons_32["help"];?>'/></td>
				<td>
				<ul>
					<li>Enter a name for this batch</li>
					<li>Specify integration and graduation dates for the batch</li>
					<li>Add academic periods (quarters or semesters) and specify their starting and ending dates</li>
					<li>Split the batch into specializations for some periods (if necessary)</li>
				</ul>
				</td>
			</tr></table>
		</td>
	</tr>
	<tr id='tr_batch_name'>
		<td align=center>
			<div style='font-size:14pt;margin:25px 10px 10px 10px;display:inline-block'>
				Batch
				<input id='batch_name' type='text' style='font-size:14pt'/>
			</div>
		</td>
	</tr>
	<tr>
		<td align=center>
			<table style='border-spacing:3px;margin-bottom:10px'>
				<tr>
					<td align=right style="font-weight:bold">Integration</td>
					<td id='integration'></td>
				</tr>
				<tr id='after_periods'>
					<td align=right style="font-weight:bold">Graduation</td>
					<td id='graduation'></td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<script type='text/javascript'>
var popup = window.parent.get_popup_window_from_element(window.frameElement);
popup.addCancelButton();

function getDateStringFromSQL(sql_date) {
	var date = parseSQLDate(sql_date);
	return getDateString(date);
}
function getDateString(date) {
	var s = getDayShortName(date.getDay() == 0 ? 6 : date.getDay()-1);
	s += " "+date.getDate()+" "+getMonthName(date.getMonth()+1)+" "+date.getFullYear();
	return s;
}

var academic_years = [<?php
$first = true;
foreach ($academic_years as $year) {
	if ($first) $first = false; else echo ",";
	echo "{id:".$year["id"].",year:".$year["year"].",name:".json_encode($year["name"])."}";
} 
?>];
var academic_periods = [<?php 
$first = true;
foreach ($academic_periods as $period) {
	if ($first) $first = false; else echo ",";
	echo "{id:".$period["id"].",year_id:".$period["year"].",name:".json_encode($period["name"]).",start:".json_encode($period["start"]).",end:".json_encode($period["end"])."}";
} 
?>];
academic_periods.sort(function(p1,p2){
	var d1 = parseSQLDate(p1.start);
	var d2 = parseSQLDate(p2.start);
	if (d1.getTime() < d2.getTime()) return -1;
	if (d1.getTime() > d2.getTime()) return 1;
	return 0;
});

function getAcademicYear(id) {
	for (var i = 0; i < academic_years.length; ++i)
		if (academic_years[i].id == id) 
			return academic_years[i];
	return null;
}
function getAcademicPeriod(id) {
	for (var i = 0; i < academic_periods.length; ++i)
		if (academic_periods[i].id == id)
			return academic_periods[i];
	return null;
}

var batch_id, batch_name;
var integration_date, graduation_date;
var periods = [];
<?php
if ($batch <> null) {
	echo "batch_id=".$batch["id"].";";
	echo "batch_name=".json_encode($batch["name"]).";";
	echo "integration_date=".json_encode($batch["start_date"]).";";
	foreach ($batch_periods as $bp)
		echo "periods.push({id:".$bp["id"].",name:".json_encode($bp["name"]).",academic_period:".$bp["academic_period"]."});";
	echo "graduation_date=".json_encode($batch["end_date"]).";";
} else {
	echo "batch_id=-1;";
	echo "batch_name='';";
	echo "integration_date=dateToSQL(new Date());";
	for ($i = 0; $i < $conf["periods_number"]; $i++) {
		echo "periods.push({id:-1,name:".json_encode($conf["period_name"]." ".($i+1)).",academic_period:0});";
	}
	echo "graduation_date = null;";
}
?>

var input_batch_name = document.getElementById('batch_name');
input_batch_name.value = batch_name;
inputDefaultText(input_batch_name, "Batch Name");
inputAutoresize(input_batch_name, 10);
if (batch_id > 0)
	window.top.datamodel.inputCell(batch_name, "StudentBatch", "name", batch_id);
listenEvent(input_batch_name, 'change', function() { batch_name = input_batch_name.value; });

var td_integration = document.getElementById('integration');
td_integration.appendChild(document.createTextNode(getDateStringFromSQL(integration_date)));
td_integration.onmouseover = function() { this.style.textDecoration = "underline"; };
td_integration.onmouseout = function() { this.style.textDecoration = "none"; };
td_integration.style.cursor = "pointer";
td_integration.onclick = function() {
	// TODO
};

function refreshAcademicCalendar() {
	popup.freeze();
	service.json("curriculum","get_academic_calendar",{},function(years){
		academic_years = years;
		academic_periods = [];
		for (var i = 0; i < years.length; ++i)
			for (var j = 0; j < years[i].periods.length; ++j)
				academic_periods.push(years[i].periods[j]);
		updatePeriodRow(periods[0]);
		popup.unfreeze();
	});
}

function updatePeriodRow(period) {
	period.td_period.innerHTML = "";
	var index = periods.indexOf(period);
	var min;
	if (index == 0) min = parseSQLDate(integration_date);
	else if (!periods[index-1].academic_period) min = null;
	else {
		var p = getAcademicPeriod(periods[index-1].academic_period);
		min = parseSQLDate(p.end);
	}
	if (min) {
		var list = [];
		var selected = -1;
		for (var i = 0; i < academic_periods.length; ++i) {
			if (parseSQLDate(academic_periods[i].start).getTime() < min.getTime()) continue;
			if (period.academic_period == academic_periods[i].id) selected = list.length;
			list.push(academic_periods[i]);
		}
		if (list.length == 0) {
			var link = document.createElement("A");
			link.href = "#";
			link.appendChild(document.createTextNode("Create Academic Year "+min.getFullYear()));
			link.style.color = "#808080";
			link.style.fontStyle = "italic";
			link.style.marginLeft = "5px";
			link.onclick = function() {
				var p = new window.top.popup_window("New Academic Year",null,"");
				var frame = p.setContentFrame("/dynamic/curriculum/page/edit_academic_year?year="+min.getFullYear()+"&onsave=saved");
				frame.saved = function() {
					refreshAcademicCalendar();
				};
				p.show();
				return false;
			};
			period.td_period.appendChild(link);
		} else {
			var select = document.createElement("SELECT");
			for (var i = 0; i < list.length; ++i) {
				var o = document.createElement("OPTION");
				o.value = list[i].id;
				var year = null;
				for (var j = 0; j < academic_years.length; ++j)
					if (academic_years[j].id == list[i].year) { year = academic_years[j]; break; }
				o.text = "Academic Year "+year.name+", Period "+list[i].name;
				select.add(o);
			}
			if (selected >= 0) select.selectedIndex = selected;
			else period.academic_period = list[0].id;
			period.td_period.appendChild(select);
			if (getAcademicYear(list[0].year).year != min.getFullYear()) {
				var link = document.createElement("A");
				link.href = "#";
				link.appendChild(document.createTextNode("Create Academic Year "+min.getFullYear()));
				link.style.color = "#808080";
				link.style.fontStyle = "italic";
				link.style.marginLeft = "5px";
				link.onclick = function() {
					var p = new window.top.popup_window("New Academic Year",null,"");
					var frame = p.setContentFrame("/dynamic/curriculum/page/edit_academic_year?year="+min.getFullYear()+"&onsave=saved");
					frame.saved = function() {
						refreshAcademicCalendar();
					};
					p.show();
					return false;
				};
				period.td_period.appendChild(link);
			}
		}
	}
	if (index < periods.length-1)
		updatePeriodRow(periods[index+1]);
}

function createPeriodRow(period) {
	var input_name = document.createElement("INPUT");
	input_name.type = "text";
	input_name.value = period.name;
	inputDefaultText(input_name, "Period Name");
	inputAutoresize(input_name, 15);
	listenEvent(input_name, 'change', function() {
		period.name = input_name.value;
	});
	if (period.id > 0) window.top.datamodel.inputCell(input_name, "BatchPeriod", "name", period.id);

	var tr, td;
	var last_row = document.getElementById('after_periods');
	last_row.parentNode.insertBefore(tr = document.createElement("TR"), last_row);
	tr.appendChild(td = document.createElement("TD"));
	td.appendChild(input_name);
	tr.appendChild(td = document.createElement("TD"));
	period.td_period = td;
}
for (var i = 0; i < periods.length; ++i)
	createPeriodRow(periods[i]);
updatePeriodRow(periods[0]);

</script>
<?php 
	}
	
}
?>