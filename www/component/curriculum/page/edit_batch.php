<?php 
class page_edit_batch extends Page {
	
	public function getRequiredRights() { return array("edit_curriculum"); }
	
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
		
		$conf = PNApplication::$instance->getDomainDescriptor();
		$conf = $conf["curriculum"];
		
		require_once("component/curriculum/CurriculumJSON.inc");
		$this->addJavascript("/static/curriculum/curriculum_objects.js");
		$this->requireJavascript("input_utils.js");
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
					<li>Add/Remove academic periods (quarters or semesters)</li>
					<li>Split the batch into specializations from a period if necessary</li>
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
popup.addIconTextButton(theme.build_icon("/static/calendar/calendar_16.png",theme.icons_10.add), "Add Period", "add_period", function() { addPeriod(); });
popup.addIconTextButton("/static/curriculum/curriculum_16.png", "Edit Specializations", "spe", editSpecializations);
popup.addFrameSaveButton(save);

function getDateStringFromSQL(sql_date) {
	if (sql_date == null) return "no date";
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

var lock_batch = <?php echo $lock_batch <> null ? $lock_batch : "null"; ?>;

var batch_id, batch_name;
var integration_date, graduation_date;
var periods = [];
var specializations = <?php echo CurriculumJSON::SpecializationsJSON();?>;
var spe_period_start = null;
var selected_specializations = [];
var new_period_id_counter = -1;
<?php
if ($batch <> null) {
	echo "batch_id=".$batch["id"].";";
	echo "batch_name=".json_encode($batch["name"]).";";
	echo "integration_date=".json_encode($batch["start_date"]).";";
	foreach ($batch_periods as $bp)
		echo "periods.push({id:".$bp["id"].",name:".json_encode($bp["name"]).",academic_period:".$bp["academic_period"]."});";
	echo "graduation_date=".json_encode($batch["end_date"]).";";
	if (count($periods_specializations) > 0) {
		for ($i = 0; $i < count($batch_periods); $i++) {
			$found = false;
			foreach ($periods_specializations as $ps) 
				if ($ps["period"] == $batch_periods[$i]["id"]) {
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
} else {
	echo "batch_id=-1;";
	echo "batch_name='';";
	echo "integration_date=dateToSQL(new Date());";
	for ($i = 0; $i < $conf["periods_number"]; $i++) {
		echo "periods.push({id:new_period_id_counter--,name:".json_encode($conf["period_name"]." ".($i+1)).",academic_period:0});";
	}
	echo "graduation_date = null;";
	echo "pnapplication.dataUnsaved('Batch');";
}
?>

var input_batch_name = document.getElementById('batch_name');
input_batch_name.value = batch_name;
inputDefaultText(input_batch_name, "Batch Name");
inputAutoresize(input_batch_name, 10);
if (batch_id > 0)
	window.top.datamodel.inputCell(input_batch_name, "StudentBatch", "name", batch_id);
listenEvent(input_batch_name, 'change', function() { 
	batch_name = input_batch_name.value;
	pnapplication.dataUnsaved('Batch');
});

var td_integration = document.getElementById('integration');
td_integration.appendChild(document.createTextNode(getDateStringFromSQL(integration_date)));
td_integration.onmouseover = function() { this.style.textDecoration = "underline"; };
td_integration.onmouseout = function() { this.style.textDecoration = "none"; };
td_integration.style.cursor = "pointer";
td_integration.onclick = function() {
	window.top.require(["context_menu.js","date_picker.js"],function() {
		var menu = new window.top.context_menu();
		var min = new Date(2000, 0, 1);
		var max = graduation_date ? parseSQLDate(graduation_date) : null;
		var picker = new window.top.date_picker(parseSQLDate(integration_date), min, max);
		picker.onchange = function(picker, date) {
			integration_date = dateToSQL(date);
			td_integration.removeAllChildren();
			td_integration.appendChild(document.createTextNode(getDateStringFromSQL(integration_date)));
			updatePeriodRow(periods[0]);
			pnapplication.dataUnsaved('Batch');
		};
		menu.addItem(picker.element, true);
		menu.element.style.border = "none";
		menu.showBelowElement(td_integration);
	});
};

var td_graduation = document.getElementById('graduation');
td_graduation.appendChild(document.createTextNode(getDateStringFromSQL(graduation_date)));
td_graduation.onmouseover = function() { this.style.textDecoration = "underline"; };
td_graduation.onmouseout = function() { this.style.textDecoration = "none"; };
td_graduation.style.cursor = "pointer";
td_graduation.onclick = function() {
	window.top.require(["context_menu.js","date_picker.js"],function() {
		var menu = new window.top.context_menu();
		var min = parseSQLDate(integration_date);
		var max = null;
		var picker = new window.top.date_picker(parseSQLDate(graduation_date), min, max);
		picker.onchange = function(picker, date) {
			setGraduationDate(date);
		};
		menu.addItem(picker.element, true);
		menu.element.style.border = "none";
		menu.showBelowElement(td_graduation);
	});
};

function setGraduationDate(date) {
	graduation_date = dateToSQL(date);
	td_graduation.removeAllChildren();
	td_graduation.appendChild(document.createTextNode(getDateStringFromSQL(graduation_date)));
	updatePeriodRow(periods[0]);
	pnapplication.dataUnsaved('Batch');
}

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
	period.td_period.removeAllChildren();
	var index = periods.indexOf(period);
	var min;
	if (index == 0) min = parseSQLDate(integration_date);
	else if (!periods[index-1].academic_period) min = null;
	else {
		var p = getAcademicPeriod(periods[index-1].academic_period);
		min = parseSQLDate(p.end);
	}
	var max = graduation_date ? parseSQLDate(graduation_date) : null;
	if (min) {
		var list = [];
		var selected = -1;
		var has_after = false;
		for (var i = 0; i < academic_periods.length; ++i) {
			if (parseSQLDate(academic_periods[i].start).getTime() < min.getTime()) continue;
			if (max && parseSQLDate(academic_periods[i].end).getTime() > max.getTime()) { has_after = true; continue; }
			if (period.academic_period == academic_periods[i].id) selected = list.length;
			list.push(academic_periods[i]);
		}
		if (list.length == 0) {
			if (has_after) {
				period.td_period.appendChild(document.createTextNode("No more time before graduation"));
			} else {
				var link = document.createElement("A");
				link.href = "#";
				link.appendChild(document.createTextNode("Create Academic Year "+min.getFullYear()));
				link.style.color = "#808080";
				link.style.fontStyle = "italic";
				link.style.whiteSpace = "nowrap";
				link.style.marginLeft = "5px";
				link.onclick = function() {
					var p = new window.top.popup_window("New Academic Year",null,"");
					var frame = p.setContentFrame("/dynamic/curriculum/page/edit_academic_year?year="+min.getFullYear()+"&onsave=saved");
					frame.saved = function() {
						refreshAcademicCalendar();
						p.close();
					};
					p.show();
					return false;
				};
				period.td_period.appendChild(link);
			}
			period.academic_period = 0;
		} else {
			var select = document.createElement("SELECT");
			for (var i = 0; i < list.length; ++i) {
				var o = document.createElement("OPTION");
				o.value = list[i].id;
				var year = null;
				for (var j = 0; j < academic_years.length; ++j)
					if (academic_years[j].id == list[i].year_id) { year = academic_years[j]; break; }
				o.text = "Academic Year "+year.name+", Period "+list[i].name;
				select.add(o);
			}
			if (selected >= 0) select.selectedIndex = selected;
			else {
				period.academic_period = list[0].id;
				pnapplication.dataUnsaved('Batch');
			}
			period.td_period.appendChild(select);
			select.onchange = function() {
				pnapplication.dataUnsaved('Batch');
				period.academic_period = select.options[select.selectedIndex].value;
				if (index < periods.length-1)
					updatePeriodRow(periods[index+1]);
			};
			if (getAcademicYear(list[0].year_id).year != min.getFullYear()) {
				var link = document.createElement("A");
				link.href = "#";
				link.appendChild(document.createTextNode("Create Academic Year "+min.getFullYear()));
				link.style.color = "#808080";
				link.style.fontStyle = "italic";
				link.style.whiteSpace = "nowrap";
				link.style.marginLeft = "5px";
				link.onclick = function() {
					var p = new window.top.popup_window("New Academic Year",null,"");
					var frame = p.setContentFrame("/dynamic/curriculum/page/edit_academic_year?year="+min.getFullYear()+"&onsave=saved");
					frame.saved = function() {
						refreshAcademicCalendar();
						p.close();
					};
					p.show();
					return false;
				};
				period.td_period.appendChild(link);
			}
		}
	} else
		period.academic_period = 0;
	if (index < periods.length-1)
		updatePeriodRow(periods[index+1]);
	else {
		if (graduation_date == null && period.academic_period > 0) {
			var p = getAcademicPeriod(period.academic_period);
			setGraduationDate(parseSQLDate(p.end));
		}
	}
}

function createPeriodRow(period) {
	period.input_name = document.createElement("INPUT");
	period.input_name.type = "text";
	period.input_name.value = period.name;
	inputDefaultText(period.input_name, "Period Name");
	inputAutoresize(period.input_name, 10);
	listenEvent(period.input_name, 'change', function() {
		period.name = period.input_name.value;
	});
	if (period.id > 0) window.top.datamodel.inputCell(period.input_name, "BatchPeriod", "name", period.id);

	var tr, td;
	var last_row = document.getElementById('after_periods');
	last_row.parentNode.insertBefore(tr = document.createElement("TR"), last_row);
	period.tr = tr;
	tr.appendChild(td = document.createElement("TD"));
	td.appendChild(period.input_name);
	tr.appendChild(td = document.createElement("TD"));
	period.td_period = td;
	td.style.whiteSpace = "nowrap";
	tr.appendChild(td = document.createElement("TD"));
	period.remove_button = document.createElement("BUTTON");
	period.remove_button.className = "flat";
	period.remove_button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
	period.remove_button.title = "Remove this period";
	td.appendChild(period.remove_button);
	period.remove_button.onclick = function() {
		pnapplication.dataUnsaved('Batch');
		periods.remove(period);
		period.tr.parentNode.removeChild(period.tr);
		if (periods.length > 0) {
			periods[periods.length-1].remove_button.disabled = "";
			periods[periods.length-1].remove_button.style.visibility = "visible";
			updatePeriodRow(periods[periods.length-1]);
		}
		if (period.id == spe_period_start) {
			spe_period_start = null;
			selected_specializations = [];
			refreshSpecializations();
		}
		layout.invalidate(last_row.parentNode);
	};
	// disable remove of previous periods
	for (var i = 0; i < periods.length; ++i) {
		if (periods[i] == period) break;
		periods[i].remove_button.disabled = "disabled";
		periods[i].remove_button.style.visibility = "hidden";
	}
}

var spe_row = null;
function refreshSpecializations() {
	if (spe_row) {
		spe_row.parentNode.removeChild(spe_row);
		spe_row = null;
	}
	if (!spe_period_start) return;
	var period = null;
	for (var i = 0; i < periods.length; ++i) if (periods[i].id == spe_period_start) { period = periods[i]; break; }
	spe_row = document.createElement("TR");
	var td = document.createElement("TH");
	td.colSpan = 3;
	var s = "Split into specializations: ";
	for (var i = 0; i < selected_specializations.length; ++i) {
		if (i>0) s += ", ";
		for (var j = 0; j < specializations.length; ++j)
			if (specializations[j].id == selected_specializations[i]) {
				s += specializations[j].name;
				break;
			}
	}
	td.innerHTML = s;
	spe_row.appendChild(td);
	period.tr.parentNode.insertBefore(spe_row, period.tr);
}

for (var i = 0; i < periods.length; ++i)
	createPeriodRow(periods[i]);
updatePeriodRow(periods[0]);
refreshSpecializations();

function addPeriod() {
	var period = {id:new_period_id_counter--,name:<?php echo json_encode($conf["period_name"]);?>+" "+(periods.length+1),academic_period:0};
	periods.push(period);
	createPeriodRow(period);
	updatePeriodRow(period);
	pnapplication.dataUnsaved('Batch');
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
			var button = document.createElement("BUTTON");
			button.className = "flat";
			button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
			button.style.marginLeft = "5px";
			button.style.padding = "0px";
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
			o.text = periods[i].name;
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
				if (spe_period_start != null) pnapplication.dataUnsaved('Batch');
				spe_period_start = null;
			} else {
				if (spe_period_start != periods[from.selectedIndex].id) pnapplication.dataUnsaved('Batch');
				spe_period_start = periods[from.selectedIndex].id;
			}
			if (!arrayEquals(selected_specializations, list)) pnapplication.dataUnsaved('Batch');
			selected_specializations = list;
			refreshSpecializations();
			layout.invalidate(document.body);
			popup.close();
		});
		popup.show();
	});
}

function save() {
	if (batch_name.trim().length == 0) {
		alert("Please enter a name for this batch");
		return;
	}
	var ls = lock_screen(null, "Saving Batch...");
	var data = new Object();
	if (batch_id) data.id = batch_id;
	data.name = batch_name.trim();
	data.start_date = integration_date;
	data.end_date = graduation_date;
	data.lock = lock_batch;
	data.periods = [];
	for (var i = 0; i < periods.length; ++i) {
		var p = new Object();
		p.id = periods[i].id;
		p.name = periods[i].name;
		p.academic_period = periods[i].academic_period;
		if (p.name.length == 0) { unlock_screen(ls); alert("Please specify a name for the period number "+(i+1)); return; }
		if (p.academic_period == 0) { unlock_screen(ls); alert("Please specify an academic year and period for period "+p.name); return; }
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
		pnapplication.dataSaved('Batch');
		if (input_batch_name.cellSaved) input_batch_name.cellSaved();
		for (var i = 0; i < periods.length; ++i) {
			if (periods[i].input_name.cellSaved) periods[i].input_name.cellSaved();
		}
		batch_id = res.id;
		for (var i = 0; i < res.periods_ids.length; ++i) {
			if (spe_period_start == res.periods_ids[i].given_id)
				spe_period_start = res.periods_ids[i].new_id;
			for (var j = 0; j < periods.length; ++j)
				if (periods[j].id == res.periods_ids[i].given_id) {
					periods[j].id = res.periods_ids[i].new_id;
					break;
				}
		}
		<?php if (isset($_GET["onsave"])) echo "window.parent.".$_GET["onsave"]."(res.id);";?>
	});
}
</script>
<?php 
	}
	
}
?>