<?php 
class page_tree_frame extends Page {
	
	public function getRequiredRights() { return array("consult_curriculum"); }
	
	public function execute() {
		if (isset($_GET["node"])) {
			setcookie("curriculum_tree_node",$_GET["node"],time()+60*60*24*30, "/dynamic/curriculum/page/tree_frame");
			echo "<script type='text/javascript'>var u = new URL(location.href);delete u.params['node'];location.href=u.toString();</script>";
			return;
		}
		$this->addInitScript("window.top.require('datamodel.js');");
		$this->requireJavascript("header_bar.js");
		theme::css($this, "header_bar.css");
		
		$this->requireJavascript("tree.js");
		theme::css($this, "tree.css");
		$this->requireJavascript("curriculum_objects.js");
		$this->addJavascript("/static/curriculum/curriculum_tree.js");
		$this->addJavascript("/static/curriculum/curriculum_tree_root.js");
		$this->addJavascript("/static/curriculum/curriculum_tree_all_students.js");
		$this->addJavascript("/static/curriculum/curriculum_tree_current_students.js");
		$this->addJavascript("/static/curriculum/curriculum_tree_alumni.js");
		$this->addJavascript("/static/curriculum/curriculum_tree_batch.js");
		$this->addJavascript("/static/curriculum/curriculum_tree_period.js");
		$this->addJavascript("/static/curriculum/curriculum_tree_specialization.js");
		$this->addJavascript("/static/curriculum/curriculum_tree_class.js");
		
		require_once("component/curriculum/CurriculumJSON.inc");
		
		$can_edit = PNApplication::$instance->user_management->has_right("manage_batches");
?>
<style type="text/css">
#curriculum_tree_container {
	border-left: 2px solid #808080;
}
#tree_footer {
	background-color: white;
	padding: 5px;
	border-top: 1px solid #A0A0A0;
	box-shadow: 0px 2px 5px #D0D0D0 inset;
}
#tree_footer_title {
	font-weight: bold;
	color: #000080;
	white-space: nowrap;
}
#tree_footer_title img {
	margin-right: 3px;
	vertical-align: bottom;
}
#tree_footer_content {
	font-size: 11px;
}
</style>
<div id="curriculum_tree_frame_container" style="width:100%;height:100%;overflow:hidden;display:flex;flex-direction:row">
	<iframe name="curriculum_tree_frame" id="curriculum_tree_frame" style="border:none;flex:1 1 auto;"></iframe>
	<div id="curriculum_tree_container" style="flex:none;display:flex;flex-direction:column;min-width:245px">
		<div id='tree_header' icon='/static/curriculum/batch_16.png' title='Batches &amp; Classes' style="flex:none">
			<?php if ($can_edit) { ?>
			<button id='button_new_batch' class='flat' onclick='createNewBatch();'>
				<img src='<?php echo theme::make_icon("/static/curriculum/batch_16.png", theme::$icons_10["add"]);?>'/>
				<b>New Batch</b>
			</button>
			<?php } ?>
		</div>
		<div id='tree' style='background-color:white;flex:1 1 auto;overflow-y:auto'></div>
		<div id='tree_footer' style='flex:none'>
			<div id='tree_footer_title'></div>
			<div id='tree_footer_content'></div>
		</div>
	</div>
</div>
<?php 
if (PNApplication::$instance->help->isShown('curriculum_tree')) {
	$help_div_id = PNApplication::$instance->help->startHelp('curriculum_tree', $this, "relative:curriculum_tree_container:left","bottom");
	PNApplication::$instance->help->spanArrow($this, "On the right side, a tree", "#curriculum_tree_container");
	echo " allows you to navigate among batches, periods and classes.<br/>";
	echo "The left side displays information only from what is selected in the tree.<br/>";
	echo "For example, if you select a period within a batch, only information related to this<br/>";
	echo "specific period will be displayed.<br/>";
	if ($can_edit) {
		echo "<br/><div style='text-align:right'>";
		echo "To create a new batch, with its periods, click on ";
		PNApplication::$instance->help->spanArrow($this, "this button", "#button_new_batch");
		echo ".";
		echo "</div>";
		echo "<br/>";
	}
	echo "Information ".($can_edit ? "and actions " : "")."about the selected element are displayed ";
	PNApplication::$instance->help->spanArrow($this, "below the tree", "#tree_footer");
	echo ".<br/>";
	PNApplication::$instance->help->endHelp($help_div_id, "curriculum_tree");
} else
	PNApplication::$instance->help->availableHelp("curriculum_tree");
?>
<script type='text/javascript'>
window.can_edit_batches = <?php echo $can_edit ? "true" : "false"?>;
new header_bar('tree_header','toolbar');
var tr = new tree('tree');
tr.table.style.marginRight = window.top.browser_scroll_bar_size+"px";
tr.addColumn(new TreeColumn(""));

//List of specializations
var specializations = <?php echo CurriculumJSON::SpecializationsJSON(); ?>;

// Academic Calendar
var academic_years = <?php echo CurriculumJSON::AcademicCalendarJSON();?>;
function getAcademicPeriod(period_id) {
	for (var i = 0; i < academic_years.length; ++i)
		for (var j = 0; j < academic_years[i].periods.length; ++j)
			if (academic_years[i].periods[j].id == period_id)
				return academic_years[i].periods[j];
	return null;
}

// Batches
var batches = <?php echo CurriculumJSON::BatchesJSON(); ?>;
var can_edit_batches = <?php echo $can_edit ? "true" : "false";?>;
batches.sort(function(b1,b2) { return parseSQLDate(b1.start_date).getTime() - parseSQLDate(b2.start_date).getTime();});

var frame = document.getElementById('curriculum_tree_frame');
var frame_parameters = "";

function selectPage(url) {
	frame.src = url+"?"+frame_parameters;
}
function nodeSelected(node) {
	setCookie("curriculum_tree_node", node.tag, 30*24*60, new URL(location.href).path);
	var params = node.getURLParameters();
	frame_parameters = "";
	var first = true;
	for (var name in params) {
		if (first) first = false; else frame_parameters += "&";
		frame_parameters += name+"="+encodeURIComponent(params[name]);
	}
	window.onhashchange();
}
function getSelectedNodeTag() {
	var item = tr.getSelectedItem();
	if (item == null) return null;
	if (!item.node) return null;
	return item.node.tag;
}
function selectNodeByTag(tag) {
	var node = window.curriculum_root.findTag(tag);
	if (!node) return;
	node.item.select();
	window.onhashchange();
}

listenEvent(frame,'load',function() {
	var win = getIFrameWindow(frame);
	if (!win || !win.location) return;
	if (win.location.href == "about:blank") return;
	var url = new URL(win.location.href);
	location.hash = "#"+url.path;
});


window.onhashchange = function() {
	var hash = location.hash;
	if (hash.length < 2) hash = "/dynamic/students/page/list"; else hash = hash.substring(1);
	selectPage(hash);
};

window.top.require("datamodel.js", function() {
	window.curriculum_root = new CurriculumTreeNode_Root(tr);
	//Initilization of the page
	var selected_node = getCookie("curriculum_tree_node");
	if (selected_node.length == 0) selected_node = "current_students";
	var node = window.curriculum_root.findTag(selected_node);
	if (!node) node = window.curriculum_root.findTag("current_students");
	node.item.select();
	window.onhashchange();
});
</script>
<?php 
	}
	
}
?>