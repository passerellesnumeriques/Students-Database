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
		
		$this->require_javascript("input_utils.js");
		$this->require_javascript("date_select.js");
		$this->require_javascript("typed_field.js");
		$this->require_javascript("field_integer.js");
		theme::css($this, "wizard.css");
		$batch = null;
		if (isset($_GET["id"])) {
			$batch = PNApplication::$instance->curriculum->getBatch($_GET["id"]);
			$periods = PNApplication::$instance->curriculum->getAcademicPeriods($_GET["id"]);
			$periods_ids = array();
			foreach ($periods as $period) array_push($periods_ids, $period["id"]);
			$periods_specializations = array();
			if (count($periods_ids) > 0) $periods_specializations = PNApplication::$instance->curriculum->getAcademicPeriodsSpecializations($periods_ids);
		}
		require_once("component/curriculum/CurriculumJSON.inc");
		$this->add_javascript("/static/curriculum/curriculum_objects.js");
		require_once("component/data_model/page/utils.inc");
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
					<td colspan=2 align=right>Integration</td>
					<td colspan=4 id='integration'></td>
				</tr>
				<tr id='after_periods'>
					<td colspan=2 align=right>Graduation</td>
					<td colspan=4 id='graduation'></td>
				</tr>
			</table>
		</td>
	</tr>
	<?php if (!isset($_GET["popup"])) {?>
	<tr>
		<td class="wizard_buttons">
			<div class='button' onclick='addPeriod();'>
				<img src='<?php echo theme::make_icon("/static/calendar/calendar_16.png",theme::$icons_10["add"]);?>'/>
				Add Period
			</div>
			<div class='button' onclick='save();'>
				<img src='<?php echo theme::$icons_16["save"];?>'/>
				Save
			</div>
		</td>
	</tr>
	<?php } ?>
</table>
<script type='text/javascript'>
<?php if (isset($_GET["popup"])) {?>
var popup = window.parent.get_popup_window_from_element(window.frameElement);
popup.addIconTextButton(theme.build_icon("/static/calendar/calendar_16.png",theme.icons_10.add), "Add Period", "add_period", function() { addPeriod(); });
popup.addIconTextButton("/static/curriculum/curriculum_16.png", "Edit Specializations", "spe", editSpecializations);
popup.addIconTextButton(theme.icons_16.save, "Save", 'save', save);
popup.addCancelButton();
<?php } ?>

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
var specializations = <?php echo CurriculumJSON::SpecializationsJSON();?>;
var spe_period_start = null;
var selected_specializations = [];

var batch_id = <?php if (isset($_GET["id"])) echo $_GET["id"]; else echo "null"; ?>;
var lock_spe = <?php echo $lock_spe <> null ? $lock_spe : "null"; ?>;
var lock_batch = <?php echo $lock_batch <> null ? $lock_batch : "null"; ?>;

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
	// process end date
	for (var i = 0; i < periods.length; ++i) {
		var start = periods[i].start.getDate(); 
		if (start == null) continue; // no start, we cannot compute
		var weeks = periods[i].weeks.getCurrentData();
		var weeks_break = periods[i].weeks_break.getCurrentData();
		var end = periods[i].end.getDate();
		if (end == null && weeks == null) continue; // no info yet
		if (weeks_break == null) {
			weeks_break = 0;
			periods[i].weeks_break.setData(0);
		}
		if (end == null) {
			// process end date from weeks
			if (!weeks) {
				weeks = 1;
				periods[i].weeks.setData(1);
			}
			end = new Date();
			end.setTime(start.getTime()+(weeks+weeks_break)*7*24*60*60*1000-24*60*60*1000);
			periods[i].end.setDate(end);
		} else {
			// process weeks from end date
			var nb_days = (end.getTime()-start.getTime())/(24*60*60*1000);
			var nb_weeks = nb_days/7;
			var nb = Math.floor(nb_weeks);
			if (nb_weeks-nb>0.1) nb++;
			nb -= weeks_break;
			if (nb > 0)
				periods[i].weeks.setData(nb);
			else {
				periods[i].weeks.setData(1);
				end = new Date();
				end.setTime(start.getTime()+(1+weeks_break)*7*24*60*60*1000-24*60*60*1000);
				periods[i].end.setDate(end);
			}
		}
	}
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
var period_counter = -1;
function addPeriod(id, name, start_date, end_date, weeks, weeks_break) {
	var period = new Object();
	period.id = id ? id : period_counter--;
	period.specializations = [];
	period.tr = document.createElement("TR");
	var td;
	// name
	period.tr.appendChild(td = document.createElement("TD"));
	td.appendChild(period.name = document.createElement("INPUT"));
	period.name.type = "text";
	period.name.onchange = update;
	if (id) period.name.value = name;
	inputDefaultText(period.name, "Period Name");
	inputAutoresize(period.name, 5);
	period.name.onresize = update;
	if (id) window.top.datamodel.inputCell(period.name, "AcademicPeriod", "name", id);
	// from
	period.tr.appendChild(td = document.createElement("TD"));
	td.appendChild(document.createTextNode(" from "));
	period.tr.appendChild(td = document.createElement("TD"));
	period.start = new date_select(td, id ? start_date : null, new Date(2004,0,1), new Date(new Date().getFullYear()+100,11,31),false,true);
	period.start.onchange = function() { update(); }
	period.start.select_day.style.width = "40px";
	period.start.select_month.style.width = "90px";
	period.start.select_year.style.width = "55px";
	if (id) window.top.datamodel.dateSelectCell(period.start, "AcademicPeriod", "start_date", id);
	// weeks
	period.tr.appendChild(td = document.createElement("TD"));
	td.appendChild(document.createTextNode(" + "));
	period.tr.appendChild(td = document.createElement("TD"));
	period.weeks = new field_integer(weeks, true, {min:1,max:200,can_be_null:true});
	period.weeks.onchange.add_listener(function() { 
		if (in_update) return; 
		if (period.weeks.getCurrentData() != null) {
			in_update = true;
			period.end.setDate(null);
			in_update = false;
			update();
		}
	});
	td.appendChild(period.weeks.getHTMLElement());
	if (id) window.top.datamodel.inputCell(period.weeks.input, "AcademicPeriod", "weeks", id);
	period.tr.appendChild(td = document.createElement("TD"));
	td.appendChild(document.createTextNode(" weeks + "));
	period.tr.appendChild(td = document.createElement("TD"));
	period.weeks_break = new field_integer(weeks, true, {min:0,max:200,can_be_null:true});
	period.weeks_break.onchange.add_listener(function() { 
		if (in_update) return;
		if (period.weeks_break.getCurrentData() != null) {
			in_update = true;
			period.end.setDate(null);
			in_update = false;
			update();
		}
	});
	td.appendChild(period.weeks_break.getHTMLElement());
	if (id) window.top.datamodel.inputCell(period.weeks_break.input, "AcademicPeriod", "weeks_break", id);
	period.tr.appendChild(td = document.createElement("TD"));
	td.appendChild(document.createTextNode(" weeks of break = "));
	// to
	period.tr.appendChild(td = document.createElement("TD"));
	td.appendChild(document.createTextNode(" ends on "));
	period.tr.appendChild(td = document.createElement("TD"));
	period.end = new date_select(td, id ? end_date : null, new Date(2004,0,1), new Date(new Date().getFullYear()+100,11,31),false,true);
	period.end.select_day.style.width = "40px";
	period.end.select_month.style.width = "90px";
	period.end.select_year.style.width = "55px";
	period.end.onchange = function() { 
		if (in_update) return;
		if (period.end.getDate() != null) {
			in_update = true;
			period.weeks.setData(null);
			in_update = false;
			update();
		}
	};
	if (id) window.top.datamodel.dateSelectCell(period.end, "AcademicPeriod", "end_date", id);
	
	// remove
	period.tr.appendChild(td = document.createElement("TD"));
	period.remove = document.createElement("IMG");
	period.remove.className = "button_verysoft";
	period.remove.style.verticalAlign = "bottom";
	period.remove.src = theme.icons_16.remove;
	period.remove.onclick = function() {
		if (this.style.visibility == 'hidden') return;
		confirm_dialog("Are you sure you want to remove this period ?", function(yes) {
			if (!yes) return;
			periods.splice(periods.length-1,1);
			if (periods.length > 0) periods[periods.length-1].remove.style.visibility = 'visible';
			if (period.id == spe_period_start) {
				spe_period_start = null;
				selected_specializations = [];
				period.tr_spe.parentNode.removeChild(period.tr_spe);
			}
			period.tr.parentNode.removeChild(period.tr);
		});
	};
	td.appendChild(period.remove);
	// hide previous remove
	if (periods.length > 0) periods[periods.length-1].remove.style.visibility = 'hidden';
	
	var next = document.getElementById('after_periods');
	next.parentNode.insertBefore(period.tr, next);
	periods.push(period);
	update();
	layout.invalidate(document.body);
}

function refreshSpecializations() {
	// remove rows of specializations
	for (var i = 0; i < periods.length; ++i) {
		if (periods[i].tr_spe) {
			periods[i].tr_spe.parentNode.removeChild(periods[i].tr_spe);
			periods[i].tr_spe = null;
		}
	}
	// add row for specializations
	if (spe_period_start != null) {
		var i = -1;
		for (var j = 0; j < periods.length; ++j) if (periods[j].id == spe_period_start) { i = j; break; }
		periods[i].tr_spe = document.createElement("TR");
		var td = document.createElement("TD");
		td.colSpan = 5;
		td.style.textAlign = "center";
		td.style.fontWeight = "bold";
		td.style.fontStyle = "italic";
		td.innerHTML = "Split into specializations: ";
		for (var j = 0; j < selected_specializations.length; ++j) {
			if (j > 0) td.appendChild(document.createTextNode(", "));
			var spe = null;
			for (var k = 0; k < specializations.length; ++k) if (specializations[k].id == selected_specializations[j]) { spe = specializations[k]; break; }
			td.appendChild(document.createTextNode(spe.name));
		}
		periods[i].tr_spe.appendChild(td);
		periods[i].tr.parentNode.insertBefore(periods[i].tr_spe, periods[i].tr);
	}
}

function editSpecializations() {
	require("popup_window.js",function() {
		var content = document.createElement("DIV");
		content.style.padding = "5px";
		content.appendChild(document.createTextNode("Split into specializations starting from "));
		var from = document.createElement("SELECT");
		content.appendChild(from);
		content.appendChild(document.createTextNode(" :"));
		content.appendChild(document.createElement("BR"));
		var checkboxes = [];
		var add_spe = function(spe) {
			var div = document.createElement("DIV"); content.appendChild(div);
			var cb = document.createElement("INPUT");
			checkboxes.push(cb);
			cb.type = "checkbox";
			cb.spe = spe;
			for (var j = 0; j < selected_specializations.length; ++j)
				if (selected_specializations[j] == spe.id)
					cb.checked = 'checked';
			div.appendChild(cb);
			var span = document.createElement("SPAN");
			var cell;
			<?php datamodel_cell_inline($this, "cell", "span", true, "Specialization", "name", "spe.id", null, "spe.name"); ?>			
			div.appendChild(span);
			var button = document.createElement("IMG");
			button.className = "button_verysoft";
			button.style.marginLeft = "5px";
			button.style.padding = "0px";
			button.style.verticalAlign = "bottom";
			button.src = theme.icons_16.remove;
			button.spe = spe;
			button.onclick = function() {
				var id = this.spe.id;
				window.top.datamodel.confirm_remove("Specialization", id, function() {
					for (var i = 0; i < specializations.length; ++i)
						if (specializations[i].id == id) {
							specializations.splice(i,1);
							break;
						}
					content.removeChild(div);
					checkboxes.remove(cb);
				});
			};
			div.appendChild(button);
		};
		for (var i = 0; i < specializations.length; ++i) {
			add_spe(specializations[i]);
		}
		for (var i = 0; i < periods.length; ++i) {
			var o = document.createElement("OPTION");
			o.value = i;
			o.text = periods[i].name.value;
			from.add(o);
		}
		for (var i = 0; i < periods.length; ++i)
			if (periods[i].id == spe_period_start) { from.selectedIndex = i; break; }
		
		var popup = new popup_window("Specializations", "/static/curriculum/curriculum_16.png", content);
		popup.addIconTextButton(theme.build_icon("/static/curriculum/curriculum_16.png",theme.icons_10.add), "Create new specialization", 'create', function() {
			input_dialog(
				theme.build_icon("/static/curriculum/curriculum_16.png",theme.icons_10.add),
				"Create new specialization",
				"Name of the new specialization:",
				"",
				100,
				function(name) {
					name = name.trim();
					if (name.length == 0) return "Please enter a name";
					name = name.toLowerCase();
					for (var i = 0; i < specializations.length; ++i)
						if (specializations[i].name.toLowerCase() == name)
							return "A specialization already exists with this name";
					return null;
				},function(name,p) {
					if (!name) return;
					var ls = lock_screen(null, "Creation of the new specialization...");
					name = name.trim();
					service.json("data_model","save_entity",{table:"Specialization",field_name:name},function(res) {
						unlock_screen(ls);
						if (res && res.key) {
							var new_spe = new Specialization(res.key, name);
							specializations.push(new_spe);
							add_spe(new_spe);
						}
					});
				}
			);
		});
		popup.addOkCancelButtons(function() {
			var list = [];
			for (var i = 0; i < checkboxes.length; ++i)
				if (checkboxes[i].checked) list.push(checkboxes[i].spe.id);
			if (list.length == 0) {
				spe_period_start = null;
			} else {
				spe_period_start = periods[from.selectedIndex].id;
			}
			selected_specializations = list;
			refreshSpecializations();
			layout.invalidate(document.body);
			popup.close();
		});
		popup.show();
	});
}

function save() {
	var ls = lock_screen(null, "Saving Batch...");
	var data = new Object();
	if (batch_id) data.id = batch_id;
	data.name = batch_name.getValue();
	data.start_date = dateToSQL(integration.getDate());
	data.end_date = dateToSQL(graduation.getDate());
	data.lock = lock_batch;
	data.periods = [];
	for (var i = 0; i < periods.length; ++i) {
		var p = new Object();
		p.id = periods[i].id;
		p.name = periods[i].name.getValue();
		p.start_date = dateToSQL(periods[i].start.getDate());
		p.end_date = dateToSQL(periods[i].end.getDate());
		p.weeks = periods[i].weeks.getCurrentData();
		p.weeks_break = periods[i].weeks_break.getCurrentData();
		if (p.name.length == 0) { unlock_screen(ls); alert("Please specify a name for the period number "+(i+1)); return; }
		if (p.start_date == null) { unlock_screen(ls); alert("Please specify a starting date for period "+p.name); return; }
		if (p.end_date == null || p.weeks == null) { unlock_screen(ls); alert("Please specify an ending date for period "+p.name); return; }
		if (p.weeks_break == null) p.weeks_break = 0;
		data.periods.push(p);
	}
	data.periods_specializations = [];
	if (spe_period_start) {
		var found = false;
		for (var i = 0; i < periods.length; ++i) {
			if (!found && periods[i].id == spe_period_start) found = true;
			if (!found) continue;
			for (var j = 0; j < selected_specializations.length; ++j)
				data.periods_specializations.push({period_id:periods[i].id,specialization_id:selected_specializations[j]});
		}
	}
	service.json("curriculum", "save_batch", data, function(res) {
		unlock_screen(ls);
		if (!res) return;
		if (batch_name.cellSaved) batch_name.cellSaved();
		if (integration.cellSaved) integration.cellSaved();
		if (graduation.cellSaved) graduation.cellSaved();
		for (var i = 0; i < periods.length; ++i) {
			if (periods[i].name.cellSaved) periods[i].name.cellSaved();
			if (periods[i].start.cellSaved) periods[i].start.cellSaved();
			if (periods[i].end.cellSaved) periods[i].end.cellSaved();
			if (periods[i].weeks.cellSaved) periods[i].weeks.cellSaved();
			if (periods[i].weeks_break.cellSaved) periods[i].weeks_break.cellSaved();
		}
		batch_id = res.id;
		for (var i = 0; i < res.periods_ids.length; ++i)
			for (var j = 0; j < periods.length; ++j)
				if (periods[j].id == res.periods_ids[i].given_id) {
					periods[j].id = res.periods_ids[i].new_id;
					break;
				}
		<?php if (isset($_GET["onsave"])) echo "window.parent.".$_GET["onsave"]."(res.id);";?>
	});
}

<?php
if ($batch <> null) {
	echo "batch_name.value = ".json_encode($batch["name"])."; batch_name.onblur();\n";
	echo "integration.selectDate(parseSQLDate(".json_encode($batch["start_date"])."));\n";
	echo "graduation.selectDate(parseSQLDate(".json_encode($batch["end_date"])."));\n";
	foreach ($periods as $period)
		echo "addPeriod(".$period["id"].",".json_encode($period["name"]).",parseSQLDate(".json_encode($period["start_date"])."),parseSQLDate(".json_encode($period["end_date"])."),".$period["weeks"].",".$period["weeks_break"].");\n";
	if (count($periods_specializations) > 0) {
		for ($i = 0; $i < count($periods); $i++) {
			$found = false;
			foreach ($periods_specializations as $ps) 
				if ($ps["period"] == $periods[$i]["id"]) {
					echo "spe_period_start = ".$ps["period"].";\n";
					echo "selected_specializations = [";
					$first = true;
					foreach ($periods_specializations as $p) {
						if ($p["period"] <> $ps["period"]) continue;
						if ($first) $first = false; else echo ",";
						echo $p["specialization"];
					}
					echo "];\n";
					$found = true;
					break;
				}
			if ($found) break;
		}
	}
	echo "refreshSpecializations();\n";
} 
?>

if (batch_id) {
	window.top.datamodel.inputCell(batch_name, "StudentBatch", "name", batch_id);
	window.top.datamodel.dateSelectCell(integration, "StudentBatch", "start_date", batch_id);
	window.top.datamodel.dateSelectCell(graduation, "StudentBatch", "end_date", batch_id);
}

</script>
<?php 
	}
	
}
?>