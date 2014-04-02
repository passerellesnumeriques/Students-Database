<?php 
class page_academic_calendar extends Page {
	
	public function get_required_rights() { return array("consult_curriculum"); }
	
	public function execute() {
		$this->require_javascript("vertical_layout.js");
		$this->onload("new vertical_layout('top_container');");
		$this->require_javascript("header_bar.js");
		$this->onload("new header_bar('header','toolbar');");
		$this->require_javascript("tree.js");
		require_once("component/curriculum/CurriculumJSON.inc");
?>
<div id='top_container'>
	<div id='header'>
		<button class='button_verysoft' onclick='new_year();'>New Academic Year</button>
	</div>
	<div id='tree_container' style='background-color:white;' layout='fill'></div>
</div>

<script type='text/javascript'>
var tr = new tree('tree_container');
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
	var parent = year.year < now.getFullYear() ? past : current_and_future;
	var item = createTreeItemSingleCell(null, "Academic Year "+year.name, true);
	parent.addItem(item);
	item.academic_year = year;
	for (var i = 0; i < year.periods.length; ++i)
		build_period(item, year.periods[i]);
}
function build_period(parent, period) {
	// TODO
}
build_years(<?php echo CurriculumJSON::AcademicCalendarJSON();?>);

function new_year() {
	var year = new Date().getFullYear();
	if (current_and_future.children.length > 0)
		year = current_and_future.children[current_and_future.children.length-1].academic_year.year;
	window.top.require("popup_window.js",function() {
		var popup = new window.top.popup_window("New Academic Year",null,"");
		popup.setContentFrame("/dynamic/curriculum/page/edit_academic_year?year="+year);
		popup.show();
	});
}
</script>
<?php 
	}
	
}
?>