<?php 
class page_academic_calendar extends Page {
	
	public function getRequiredRights() { return array("consult_curriculum"); }
	
	public function execute() {
		$this->requireJavascript("tree.js");
		require_once("component/curriculum/CurriculumJSON.inc");
		$can_edit = PNApplication::$instance->user_management->has_right("edit_curriculum");
?>
<div id='top_container' class="page_container" style="width:100%;height:100%;display:flex;flex-direction:column;">
	<div class="page_title" style="flex:none;">
		<img src='/static/calendar/calendar_32.png' style="vertical-align:top"/>
		Academic Calendar: Years and Periods
	</div>
	<div id='tree_container' style='background-color:white;flex:1 1 auto;overflow:auto;'></div>
	<?php if ($can_edit) { ?>
	<div class="page_footer" style="flex:none;">
		<button class='action green' onclick='new_year();'>New Academic Year</button>
	</div>
	<?php } ?>
</div>

<script type='text/javascript'>
var tr = new tree('tree_container');
tr.addColumn(new TreeColumn(""));
tr.addColumn(new TreeColumn(""));
tr.addColumn(new TreeColumn(""));
tr.addColumn(new TreeColumn(""));
tr.addColumn(new TreeColumn(""));

var past = createTreeItemSingleCell(null, "Past Years", false);
tr.addItem(past);
var current_and_future = createTreeItemSingleCell(null, "Current and future years", true);
tr.addItem(current_and_future);

function build_years(years) {
	for (var i = 0; i < years.length; ++i)
		build_year(years[i]);
}
function build_year(year) {
	var now = new Date();
	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode("Academic Year "));
	var text = document.createTextNode(year.name);
	span.appendChild(text);
	window.top.datamodel.registerCellText(window, "AcademicYear", "name", year.id, text);
	var parent = year.year < now.getFullYear() ? past : current_and_future;
	var item = createTreeItemSingleCell(null, span, true);
	parent.addItem(item);
	<?php if ($can_edit) { ?>
	span.style.cursor = "pointer";
	span.onmouseover = function() { this.style.textDecoration = "underline"; };
	span.onmouseout = function() { this.style.textDecoration = "none"; };
	span.title = "Edit or Remove Academic Year "+year.name;
	span.onclick = function() {
		window.top.require("popup_window.js",function() {
			var popup = new window.top.popup_window("Academic Year",null,"");
			var frame = popup.setContentFrame("/dynamic/curriculum/page/edit_academic_year?id="+year.id+"&onsave=saved");
			frame.saved = function() {
				popup.close();
				location.reload();
			};
			popup.show();
		});
	};
	<?php } ?>
	item.academic_year = year;
	for (var i = 0; i < year.periods.length; ++i)
		build_period(item, year.periods[i]);
}
function build_period(parent, period) {
	var cells = [];

	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode("Period "));
	var text = document.createTextNode(period.name);
	span.appendChild(text);
	window.top.datamodel.registerCellText(window, "AcademicPeriod", "name", period.id, text);
	cells.push(new TreeCell(span));

	span = document.createElement("SPAN");
	span.innerHTML = "&nbsp;from ";
	text = document.createTextNode(period.start);
	span.appendChild(text);
	window.top.datamodel.registerCellText(window, "AcademicPeriod", "start", period.id, text);
	cells.push(new TreeCell(span));
	
	span = document.createElement("SPAN");
	span.innerHTML = "&nbsp;to ";
	text = document.createTextNode(period.end);
	span.appendChild(text);
	window.top.datamodel.registerCellText(window, "AcademicPeriod", "end", period.id, text);
	cells.push(new TreeCell(span));
	
	span = document.createElement("SPAN");
	span.innerHTML = "&nbsp;(";
	text = document.createTextNode(period.weeks);
	span.appendChild(text);
	window.top.datamodel.registerCellText(window, "AcademicPeriod", "weeks", period.id, text);
	span.appendChild(document.createTextNode(" weeks"));
	cells.push(new TreeCell(span));
	
	span = document.createElement("SPAN");
	span.innerHTML = "&nbsp;+ ";
	text = document.createTextNode(period.weeks_break);
	span.appendChild(text);
	window.top.datamodel.registerCellText(window, "AcademicPeriod", "weeks_break", period.id, text);
	span.appendChild(document.createTextNode(" weeks of break)"));
	cells.push(new TreeCell(span));
	
	var item = new TreeItem(cells);
	parent.addItem(item);
	<?php if ($can_edit) { ?>
	item.tr.style.cursor = "pointer";
	item.tr.onmouseover = function() { this.style.textDecoration = "underline"; };
	item.tr.onmouseout = function() { this.style.textDecoration = "none"; };
	item.tr.title = "Edit or Remove Academic Year "+parent.academic_year.name;
	item.tr.onclick = function() {
		window.top.require("popup_window.js",function() {
			var popup = new window.top.popup_window("Academic Year",null,"");
			var frame = popup.setContentFrame("/dynamic/curriculum/page/edit_academic_year?id="+period.year_id+"&onsave=saved");
			frame.saved = function() {
				popup.close();
				location.reload();
			};
			popup.show();
		});
	};
	<?php } ?>
}
build_years(<?php echo CurriculumJSON::AcademicCalendarJSON();?>);

function new_year() {
	var year = new Date().getFullYear();
	if (current_and_future.children.length > 0)
		year = current_and_future.children[current_and_future.children.length-1].academic_year.year+1;
	window.top.require("popup_window.js",function() {
		var popup = new window.top.popup_window("New Academic Year",null,"");
		var frame = popup.setContentFrame("/dynamic/curriculum/page/edit_academic_year?year="+year+"&onsave=saved");
		frame.saved = function() {
			popup.close();
			location.reload();
		};
		popup.show();
	});
}
</script>
<?php 
	}
	
}
?>