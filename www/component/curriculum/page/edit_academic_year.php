<?php 
class page_edit_academic_year extends Page {
	
	public function getRequiredRights() { return array("edit_curriculum"); }
	
	public function execute() {
		$id = @$_GET["id"];
		$conf = PNApplication::$instance->getDomainDescriptor();
		$conf = $conf["curriculum"];
		if ($id <> null) {
			$year = SQLQuery::create()->select("AcademicYear")->whereValue("AcademicYear","id",$id)->executeSingleRow();
			$periods = SQLQuery::create()->select("AcademicPeriod")->whereValue("AcademicPeriod","year",$id)->execute();
		} else {
			$defined_years = SQLQuery::create()->select("AcademicYear")->field("year")->executeSingleField();
			$year = array(
				"id"=>-1,
				"year"=>$_GET["year"],
				"name"=>$_GET["year"]."-".(intval($_GET["year"])+1)
			);
			$periods = array();
			$last_year = $_GET["year"];
			for ($i = 0; $i < count($conf["default_year_periods"]); $i++) {
				$period = array(
					"id"=>-1,
					"year"=>-1,
					"name"=>$conf["period_name"]." ".($i+1),
					"weeks"=>$conf["period_weeks"]
				);
				$c = $conf["default_year_periods"][$i];
				$start = mktime(0,0,0,$c["month"],1,$last_year);
				$start += ($c["week"]-1)*7*24*60*60;
				$date = getdate($start);
				while ($date["wday"] <> 1) {
					$start += 24*60*60;
					$date = getdate($start);
				}
				$end = $start + ($conf["period_weeks"]+$c["weeks_break"])*7*24*60*60;
				$end -= 24*60*60;
				$period["start"] = date("Y-m-d", $start);
				$period["end"] = date("Y-m-d", $end);
				$period["weeks_break"] = $c["weeks_break"];
				$last_year = getdate($end);
				$last_year = $last_year["year"];
				array_push($periods, $period);
			}
		}
		
		$this->requireJavascript("input_utils.js");
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_integer.js");
		require_once("component/curriculum/CurriculumJSON.inc");
?>
<div style='background-color:white'>
	<div style='text-align:center;font-size:12pt;'>
		Academic Year
		<input type='text' style='font-size:12pt;' id='year_name'/>
	</div>
	<table><tbody id='periods_table'>
	<tr>
		<th>Academic Period</th>
		<th>Starts on</th>
		<th>Duration</th>
		<th>Break/Holidays</th>
		<th>Ends on</th>
		<th></th>
	</tr>
	<tr id='last_period_row'>
		<td colspan=5 align=center>
			<a href='#' onclick='addPeriod();return false;' style='color:#808080;font-style:italic;'>Add Period</a>
		</td>
	</tr>
	</tbody></table>
</div>
<script type='text/javascript'>
var popup = window.parent.get_popup_window_from_frame(window);

var year_object = <?php echo json_encode($year);?>;

var year_name = document.getElementById('year_name');
year_name.value = <?php echo json_encode($year["name"]);?>;
inputAutoresize(year_name, 10);
inputDefaultText(year_name, "Name");

function getDateStringFromSQL(sql_date) {
	var date = parseSQLDate(sql_date);
	return getDateString(date);
}
function getDateString(date) {
	var s = getDayShortName(date.getDay() == 0 ? 6 : date.getDay()-1);
	s += " "+date.getDate()+" "+getMonthName(date.getMonth()+1)+" "+date.getFullYear();
	return s;
}

var periods = [];
function addPeriod(period) {
	if (!period) {
		period = {
			id: -1,
			year_id: <?php echo $id <> null ? $id : "-1"; ?>,
			name: <?php echo json_encode($conf["period_name"]);?>+" "+(periods.length+1),
			weeks: <?php echo $conf["period_weeks"]; ?>,
			weeks_break: 0,
			start: periods.length > 0 ? dateToSQL(new Date(parseSQLDate(periods[periods.length-1].end).getTime()+24*60*60*1000)) : year_object.year+"-01-01",
		};
		period.end = dateToSQL(new Date(parseSQLDate(period.start).getTime()+period.weeks*7*24*60*60*1000-24*60*60*1000));
	}
	
	var input_name = document.createElement("INPUT");
	input_name.type = "text";
	input_name.value = period.name;
	inputAutoresize(input_name, 15);
	inputDefaultText(input_name, "Name");

	var span_start = document.createElement("SPAN");
	span_start.appendChild(document.createTextNode(getDateStringFromSQL(period.start)));
	
	var input_weeks = new field_integer(period.weeks, true, {min:1,max:200,can_be_null:false});

	var input_weeks_break = new field_integer(period.weeks_break, true, {min:0,max:200,can_be_null:false});

	var span_end = document.createElement("SPAN");
	span_end.appendChild(document.createTextNode(getDateStringFromSQL(period.end)));
	
	var table = document.getElementById('periods_table');
	var tr, td;
	table.insertBefore(tr = document.createElement("TR"), document.getElementById('last_period_row'));
	tr.appendChild(td = document.createElement("TD"));
	td.appendChild(input_name);
	tr.appendChild(td = document.createElement("TD"));
	td.style.whiteSpace = "nowrap";
	td.appendChild(span_start);
	tr.appendChild(td = document.createElement("TD"));
	td.style.whiteSpace = "nowrap";
	td.appendChild(document.createTextNode(" + "));
	td.appendChild(input_weeks.getHTMLElement());
	td.appendChild(document.createTextNode("weeks"));
	tr.appendChild(td = document.createElement("TD"));
	td.style.whiteSpace = "nowrap";
	td.appendChild(document.createTextNode(" + "));
	td.appendChild(input_weeks_break.getHTMLElement());
	td.appendChild(document.createTextNode("weeks of break"));
	tr.appendChild(td = document.createElement("TD"));
	td.style.whiteSpace = "nowrap";
	td.appendChild(document.createTextNode(" = "));
	td.appendChild(span_end);
	tr.appendChild(td = document.createElement("TD"));
	period.remove_button = document.createElement("BUTTON");
	period.remove_button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
	period.remove_button.title = "Remove this period";
	period.remove_button.className = "flat";
	td.appendChild(period.remove_button);
	period.remove_button.onclick = function() {
		periods.remove(period);
		tr.parentNode.removeChild(tr);
		if (periods.length > 0) {
			periods[periods.length-1].remove_button.disabled = "";
			periods[periods.length-1].remove_button.style.visibility = "visible";
		}
		layout.invalidate(table);
	};
	for (var i = 0; i < periods.length; ++i) {
		periods[i].remove_button.disabled = "disabled";
		periods[i].remove_button.style.visibility = "hidden";
	}
	
	period.span_start = span_start;
	period.update_end = function() {
		var current_end = parseSQLDate(period.end);
		var start = parseSQLDate(period.start);
		current_end = current_end.getTime() - start.getTime();
		current_end /= 7*24*60*60*1000;
		if (Math.floor(current_end) != (period.weeks+period.weeks_break-1)) {
			var end = new Date(start.getTime()+(period.weeks+period.weeks_break)*7*24*60*60*1000-24*60*60*1000);
			period.end = dateToSQL(end);
			span_end.innerHTML = "";
			span_end.appendChild(document.createTextNode(getDateStringFromSQL(period.end)));
			var index = periods.indexOf(period);
			if (index < periods.length-1) {
				var next_start = parseSQLDate(periods[index+1].start);
				if (next_start.getTime() <= end.getTime()) {
					next_start = new Date(end.getTime()+24*60*60*1000);
					periods[index+1].start = dateToSQL(next_start);
					periods[index+1].span_start.innerHTML = "";
					periods[index+1].span_start.appendChild(document.createTextNode(getDateStringFromSQL(periods[index+1].start)));
					periods[index+1].update_end();
				}
			} 
		}
		layout.invalidate(span_end);
	};
	span_start.onmouseover = function() { this.style.textDecoration = "underline"; };
	span_start.onmouseout = function() { this.style.textDecoration = "none"; };
	span_start.style.cursor = "pointer";
	span_start.onclick = function() {
		window.top.require(["context_menu.js","date_picker.js"],function() {
			var menu = new window.top.context_menu();
			var min = new Date(<?php echo $year["year"];?>, 0, 1);
			var max = null;
			var index = periods.indexOf(period);
			if (index > 0) min = parseSQLDate(periods[index-1].end);
			if (index < periods.length-1) max = parseSQLDate(periods[index+1].start);
			if (index == 0 && (max == null || max.getFullYear() > <?php echo $year["year"];?>))
				max = new Date(<?php echo $year["year"];?>, 11, 31);
			var picker = new window.top.date_picker(parseSQLDate(period.start), min, max);
			picker.onchange = function(picker, date) {
				period.start = dateToSQL(date);
				span_start.innerHTML = "";
				span_start.appendChild(document.createTextNode(getDateStringFromSQL(period.start)));
				period.update_end();
			};
			menu.addItem(picker.element, true);
			menu.element.style.border = "none";
			menu.showBelowElement(span_start);
		});
	};
	input_weeks.onchange.add_listener(function() {
		period.weeks = input_weeks.getCurrentData();
		period.update_end();
	});
	input_weeks_break.onchange.add_listener(function() {
		period.weeks_break = input_weeks_break.getCurrentData();
		period.update_end();
	});
	span_end.onmouseover = function() { this.style.textDecoration = "underline"; };
	span_end.onmouseout = function() { this.style.textDecoration = "none"; };
	span_end.style.cursor = "pointer";
	span_end.onclick = function() {
		window.top.require(["context_menu.js","date_picker.js"],function() {
			var menu = new window.top.context_menu();
			var min = parseSQLDate(period.start);
			var max = null;
			var index = periods.indexOf(period);
			if (index < periods.length-1) max = parseSQLDate(periods[index+1].start);
			var picker = new window.top.date_picker(parseSQLDate(period.end), min, max);
			picker.onchange = function(picker, date) {
				period.end = dateToSQL(date);
				span_end.innerHTML = "";
				span_end.appendChild(document.createTextNode(getDateStringFromSQL(period.end)));
				// update number of weeks
				var start = parseSQLDate(period.start);
				var weeks = (date.getTime()-start.getTime())/(7*24*60*60*1000);
				var nb = Math.floor(weeks);
				if (weeks-nb>0.1) nb++;
				nb -= period.weeks_break;
				period.weeks = nb;
				input_weeks.setData(nb);
			};
			menu.addItem(picker.element, true);
			menu.element.style.border = "none";
			menu.showBelowElement(span_end);
		});
	};
		
	periods.push(period);
}

function save() {
	if (periods.length == 0) {
		popup.unfreeze();
		alert("Please enter at least one period for this Academic Year");
		return;
	}
	require("curriculum_objects.js",function() {
		var data = new AcademicYear(year_object.id,year_object.year,year_name.value,[]);
		for (var i = 0; i < periods.length; ++i) {
			var p = new AcademicPeriod(periods[i].year_id, periods[i].id, periods[i].name, periods[i].start, periods[i].end, periods[i].weeks, periods[i].weeks_break);
			data.periods.push(p);
		}
		service.json("curriculum","save_academic_year",data,function(res) {
			popup.unfreeze();
			if (res) {
				<?php if (isset($_GET["onsave"])) echo "window.frameElement.".$_GET["onsave"]."();"?>
			}
		});
	});
}

<?php 
foreach ($periods as $p) {
	echo "addPeriod(".CurriculumJSON::AcademicPeriodJSONFromDB($p).");";
}

if ($id <> null) {
?>
popup.addIconTextButton(theme.icons_16.remove, "Remove Academic Year", "remove", function() {
	window.top.datamodel.confirm_remove("AcademicYear",year_object.id,function() {
		<?php if (isset($_GET["onsave"])) echo "window.frameElement.".$_GET["onsave"]."();"?>
		popup.close();
	});
});
popup.addSaveButton(function() {
	popup.freeze("Saving...");
	save();
});
popup.addCancelButton();
<?php 
} else {
?>
popup.addIconTextButton("/static/calendar/calendar_16.png", "Change starting year...", "change_year", function() {
	var avail_years = [];
	<?php 
	$now = getdate();
	for ($i = 2000; $i < $now["year"]+100; $i++) {
		if (in_array($i, $defined_years)) continue;
		echo "avail_years.push([".$i.",".$i."]);";
	} 
	?>
	select_dialog("/static/calendar/calendar_16.png", "Change starting year", "Starting year", year_object.year, avail_years, function(value) {
		if (value == null) return;
		popup.removeAllButtons();
		location.href = "/dynamic/curriculum/page/edit_academic_year?year="+value+"&onsave=<?php echo $_GET["onsave"];?>";
	});
});
popup.addCreateButton(function() {
	popup.freeze("Creation of the new academic year...");
	save();
});
popup.addCancelButton();
<?php 
}
?>
</script>
<?php 
	}
	
}
?>