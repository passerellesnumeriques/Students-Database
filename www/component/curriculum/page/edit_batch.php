<?php 
class page_edit_batch extends Page {
	
	public function get_required_rights() { return array("edit_curriculum"); }
	
	public function execute() {
		$this->require_javascript("input_utils.js");
		$this->require_javascript("date_select.js");
		theme::css($this, "wizard.css");
		$batch = null;
		if (isset($_GET["id"])) {
			$batch = SQLQuery::create()->select("StudentBatch")->whereValue("StudentBatch", "id", $_GET["id"])->executeSingleRow();
			$periods = SQLQuery::create()->select("AcademicPeriod")->whereValue("AcademicPeriod", "batch", $_GET["id"])->orderBy("AcademicPeriod", "start_date", true)->execute();
		}
?>
<table style='background-color:white;border-spacing:0px;margin:0px;border-collapse:collapse;'>
	<tr>
		<td class='wizard_header' style='height:75px;'>
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
	<tr>
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
					<td colspan=2 align=right>Integration</td>
					<td colspan=3 id='integration'></td>
				</tr>
				<tr id='after_periods'>
					<td colspan=2 align=right>Graduation</td>
					<td colspan=3 id='graduation'></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td class="wizard_buttons">
			<div class='button' onclick='addPeriod();'>
				Add period
			</div>
			<div class='button' onclick=''>
				<img src='<?php echo theme::$icons_16["save"];?>'/>
				Save
			</div>
			<?php if (isset($_GET["popup"])) {?>
			<div class='button' onclick=''>
				<img src='<?php echo theme::$icons_16["cancel"];?>'/>
				Cancel
			</div>
			<?php } ?>
		</td>
	</tr>
</table>
<script type='text/javascript'>
var batch_name = document.getElementById('batch_name');
inputDefaultText(batch_name, "Batch Name");
inputAutoresize(batch_name, 10);

var integration = new date_select(document.getElementById('integration'), null, new Date(2004,0,1), new Date(new Date().getFullYear()+100,11,31), true, true);
var graduation = new date_select(document.getElementById('graduation'), null, new Date(2004,0,1), new Date(new Date().getFullYear()+100,11,31), true, true);
integration.select_day.style.width = "40px";
integration.select_month.style.width = "90px";
integration.select_year.style.width = "55px";
graduation.select_day.style.width = "40px";
graduation.select_month.style.width = "90px";
graduation.select_year.style.width = "55px";

var periods = [];

integration.onchange = function() {
	update();
};
graduation.onchange = function() {
	update();
};

var in_update = false;
function update() {
	if (in_update) return;
	in_update = true;
	// size of period names
	var max_length = 5;
	for (var i = 0; i < periods.length; ++i) if (periods[i].name.value.length > max_length) max_length = periods[i].name.value.length;
	for (var i = 0; i < periods.length; ++i) periods[i].name.setMinimumSize(max_length);
	// dates
	for (var i = 0; i < periods.length; ++i) {
		var min = null, max = null;
		for (var j = i-1; j >= 0; --j) {
			min = periods[j].end.getDate();
			if (min == null) min = periods[j].start.getDate();
			if (min != null) break;
		}
		if (min == null) min = integration.getDate();
		for (var j = i+1; j < periods.length; ++j) {
			max = periods[j].start.getDate();
			if (max == null) max = periods[j].end.getDate();
			if (max != null) break;
		}
		if (max == null) max = graduation.getDate();
		if (min.getTime() != periods[i].start.minimum.getTime() || max.getTime() != periods[i].end.maximum.getTime()) {
			var end = periods[i].end.getDate();
			periods[i].start.setLimits(min, end != null ? end : max);
			var start = periods[i].start.getDate();
			periods[i].end.setLimits(start != null ? start : min, max);
		}
		var start = periods[i].start.getDate();
		if (start == null) start = min;
		if (periods[i].end.minimum.getTime() != start.getTime())
			periods[i].end.setLimits(start, max);
		var end = periods[i].end.getDate();
		if (end == null) end = max;
		if (periods[i].start.maximum.getTime() != end.getTime())
			periods[i].start.setLimits(min, end);
	}
	in_update = false;
}
function addPeriod(id, name, start_date, end_date) {
	var period = new Object();
	period.id = id ? id : -1;
	period.tr = document.createElement("TR");
	var td;
	period.tr.appendChild(td = document.createElement("TD"));
	td.appendChild(period.name = document.createElement("INPUT"));
	period.name.type = "text";
	period.name.onchange = update;
	if (id) period.name.value = name;
	inputDefaultText(period.name, "Period Name");
	inputAutoresize(period.name, 5);
	period.name.onresize = update;
	period.tr.appendChild(td = document.createElement("TD"));
	td.appendChild(document.createTextNode(" from "));
	period.tr.appendChild(td = document.createElement("TD"));
	period.start = new date_select(td, id ? start_date : null, new Date(2004,0,1), new Date(new Date().getFullYear()+100,11,31),false,true);
	period.start.onchange = function() { update(); }
	period.start.select_day.style.width = "40px";
	period.start.select_month.style.width = "90px";
	period.start.select_year.style.width = "55px";
	period.tr.appendChild(td = document.createElement("TD"));
	td.appendChild(document.createTextNode(" to "));
	period.tr.appendChild(td = document.createElement("TD"));
	period.end = new date_select(td, id ? end_date : null, new Date(2004,0,1), new Date(new Date().getFullYear()+100,11,31),false,true);
	period.end.select_day.style.width = "40px";
	period.end.select_month.style.width = "90px";
	period.end.select_year.style.width = "55px";
	period.end.onchange = function() { update(); }
	var next = document.getElementById('after_periods');
	next.parentNode.insertBefore(period.tr, next);
	periods.push(period);
	update();
	fireLayoutEventFor(document.body);
}
<?php
if ($batch <> null) {
	echo "batch_name.value = ".json_encode($batch["name"])."; batch_name.onblur();\n";
	echo "integration.selectDate(parseSQLDate(".json_encode($batch["start_date"])."));\n";
	echo "graduation.selectDate(parseSQLDate(".json_encode($batch["end_date"])."));\n";
	foreach ($periods as $period) {
		echo "addPeriod(".$period["id"].",".json_encode($period["name"]).",parseSQLDate(".json_encode($period["start_date"])."),parseSQLDate(".json_encode($period["end_date"])."));\n";
	}
} 
?>
</script>
<?php 
	}
	
}
?>