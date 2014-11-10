<?php 
class page_tree_frame extends Page {
	
	public function getRequiredRights() { return array("consult_curriculum"); }
	
	public function execute() {
		if (isset($_GET["node"])) {
			setcookie("curriculum_tree_node",$_GET["node"],time()+60*60*24*30, "/dynamic/students_groups/page/tree_frame");
			echo "<script type='text/javascript'>var u = new URL(location.href);delete u.params['node'];location.href=u.toString();</script>";
			return;
		}
		$this->addInitScript("window.top.require('datamodel.js');");
		$this->requireJavascript("header_bar.js");
		theme::css($this, "header_bar.css");
		
		$this->requireJavascript("tree.js");
		theme::css($this, "tree.css");
		$this->requireJavascript("curriculum_objects.js");
		$this->addJavascript("/static/students_groups/curriculum_tree.js");
		$this->addJavascript("/static/students_groups/curriculum_tree_root.js");
		$this->addJavascript("/static/students_groups/curriculum_tree_all_students.js");
		$this->addJavascript("/static/students_groups/curriculum_tree_current_students.js");
		$this->addJavascript("/static/students_groups/curriculum_tree_alumni.js");
		$this->addJavascript("/static/students_groups/curriculum_tree_batch.js");
		$this->addJavascript("/static/students_groups/curriculum_tree_period.js");
		$this->addJavascript("/static/students_groups/curriculum_tree_specialization.js");
		$this->requireJavascript("students_groups_objects.js");
		$this->addJavascript("/static/students_groups/curriculum_tree_group.js");
		
		require_once("component/curriculum/CurriculumJSON.inc");
		require_once("component/students_groups/StudentsGroupsJSON.inc");
		
		$q = SQLQuery::create()->select("StudentsGroupType");
		StudentsGroupsJSON::GroupTypeSQL($q);
		$groups_types = $q->execute();
		$group_type = isset($_COOKIE["students_groups_type"]) ? $_COOKIE["students_groups_type"] : 1;
		
		$can_edit = PNApplication::$instance->user_management->has_right("manage_batches");
?>
<style type="text/css">
#students_groups_tree_container {
	border-left: 2px solid #808080;
}
#tree_grouping_header {
	background-color: #E0EAFF;
	border-bottom: 1px solid #A0A0A0;
	padding: 2px 2px;
	flex:none;
	display:flex;
	flex-direction:row;
	justify-content:flex-start;
	align-items:center;
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
<div id="students_groups_tree_frame_container" style="width:100%;height:100%;overflow:hidden;display:flex;flex-direction:row">
	<iframe name="students_groups_tree_frame" id="students_groups_tree_frame" style="border:none;flex:1 1 auto;"></iframe>
	<div id="students_groups_tree_container" style="flex:none;display:flex;flex-direction:column;min-width:245px">
		<div id='tree_header' icon='/static/curriculum/batch_16.png' title='Batches &amp; Groups' style="flex:none">
			<?php if ($can_edit) { ?>
			<button id='button_new_batch' class='flat' onclick='createNewBatch();'>
				<img src='<?php echo theme::make_icon("/static/curriculum/batch_16.png", theme::$icons_10["add"]);?>'/>
				<b>New Batch</b>
			</button>
			<?php } ?>
		</div>
		<div id='tree_grouping_header'>
		Grouping: <select id='select_group_type' onchange="changeGroupType(this.value);">
		<?php 
		foreach ($groups_types as $type) {
			echo "<option value='".$type["group_type_id"]."'";
			if ($group_type == $type["group_type_id"]) echo " selected='selected'";
			echo ">".toHTML($type["group_type_name"])."</option>";
		}
		?>
		</select>
		<?php if ($can_edit) { ?>
		<button id='edit_group_type' class='flat small_icon' title='Rename group type' style='margin-left:3px;' onclick="editGroupType();">
			<img src='<?php echo theme::$icons_10["edit"];?>'/>
		</button>
		<button id='remove_group_type' class='flat small_icon' title='Remove group type' style='margin-left:3px;' onclick="removeGroupType();">
			<img src='<?php echo theme::$icons_10["remove"];?>'/>
		</button>
		<button id='add_group_type' class='flat small_icon' title='Create new group type' style='margin-left:3px;' onclick="addGroupType();">
			<img src='<?php echo theme::$icons_10["add"];?>'/>
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
	$help_div_id = PNApplication::$instance->help->startHelp('curriculum_tree', $this, "relative:students_groups_tree_container:left","bottom");
	PNApplication::$instance->help->spanArrow($this, "On the right side, a tree", "#students_groups_tree_container");
	echo " allows you to navigate among batches, periods and groups.<br/>";
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
var groups = <?php echo StudentsGroupsJSON::GroupsTypesStructurePerPeriod();?>;
var group_types = <?php echo StudentsGroupsJSON::GroupsTypesJSON($groups_types);?>;
var group_type_id = <?php echo $group_type;?>;

var frame = document.getElementById('students_groups_tree_frame');
var frame_parameters = "";

function selectPage(url) {
	frame.src = url+"?"+frame_parameters;
}
function nodeSelected(node) {
	setCookie("students_groups_tree_node", node.tag, 30*24*60, new URL(location.href).path);
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

function getSelectedGroupType() {
	for (var i = 0; i < group_types.length; ++i)
		if (group_types[i].id == group_type_id) return group_types[i];
	return null;
}
function changeGroupType(type_id) {
	if (type_id == group_type_id) return;
	setCookie("students_groups_type", type_id, 30*24*60, new URL(location.href).path);
	var item = tr.getSelectedItem();
	if (item.node instanceof CurriculumTreeNode_Group) {
		var p = item.node.parent;
		while (p instanceof CurriculumTreeNode_Group) p = p.parent;
		p.item.select();
		item = p.item;
	}
	group_type_id = type_id;
	window.curriculum_root.refreshGroups();
	item.node._onselect();
	updateGroupTypeButtons();
}
function updateGroupTypeButtons() {
	var g = getSelectedGroupType();
	document.getElementById('edit_group_type').style.display = g.builtin ? "none" : "";
	document.getElementById('remove_group_type').style.display = g.builtin ? "none" : "";
}
updateGroupTypeButtons();
function editGroupType() {
	var gt = getSelectedGroupType();
	input_dialog(theme.icons_16.edit,"Rename Group Type","Group Type Name",gt.name,100,function(name) {
		if (name.trim().length == 0) return "The name cannot be empty";
		for (var i = 0; i < group_types.length; ++i)
			if (group_types[i].id != gt.id && group_types[i].name.isSame(name))
				return "A grouping already exists with this name";
		return null;
	},function(name) {
		if (!name) return;
		name = name.trim();
		if (name == gt.name) return;
		var locker = lock_screen(null,"Renaming group type...");
		service.json("data_model","save_entity",{table:"StudentsGroupType",sub_model:null,key:gt.id,lock:-1,field_name:name},function(res) {
			unlock_screen(locker);
			if (!res) return;
			gt.name = name;
			var select = document.getElementById('select_group_type');
			for (var i = 0; i < select.options.length; ++i)
				if (select.options[i].value == gt.id) {
					select.options[i].text = name;
					break;
				}
			tr.getSelectedItem().node._onselect();
		});
	});
}
function removeGroupType() {
	var locker = lock_screen();
	require("popup_window.js");
	var content = document.createElement("DIV");
	content.style.padding = "10px";
	service.html("data_model","get_remove_confirmation_content",{table:"StudentsGroupType",row_key:group_type_id},content,function() {
		require("popup_window.js",function() {
			var p = new popup_window("Remove Group Type",theme.icons_16.question,content);
			p.addOkCancelButtons(function() {
				p.freeze("Removing Group Type");
				service.json("data_model","remove_row",{table:"StudentsGroupType",row_key:group_type_id},function(res) {
					document.getElementById('select_group_type').value = 1;
					changeGroupType(1);
					p.close();
				});
			});
			unlock_screen(locker);
			p.show();
		});
	});
}
function addGroupType() {
	require("popup_window.js",function() {
		var content = document.createElement("DIV");
		content.style.padding = "10px";
		content.style.lineHeight = "25px";
		content.appendChild(document.createTextNode("Grouping name "));
		var input_name = document.createElement("INPUT");
		input_name.type = "text";
		content.appendChild(input_name);
		content.appendChild(document.createElement("BR"));
		var cb_spe = document.createElement("INPUT");
		cb_spe.type = "checkbox";
		content.appendChild(cb_spe);
		content.appendChild(document.createTextNode(" Depends on students' specialization "));
		var help = document.createElement("IMG");
		help.src = theme.icons_16.help;
		content.appendChild(help);
		tooltip(help, "If yes, students can be assigned only if they are in the same specialization as the group.<br/>Note: this does not affect periods which are not specialized<br/><br/>Example1: The group type 'Class' depends on specializations, because for each period<br/>having specializations, classes are organized by specialization.<br/>In other words, only students following SNA specialization can be in SNA classes.<br/><br/>Example 2: The group type 'Boarding house' is not dependent on specialization, because<br/>students can be mixed in boarding houses whatever specialization they follow.");
		content.appendChild(document.createElement("BR"));
		var cb_sub_groups = document.createElement("INPUT");
		cb_sub_groups.type = "checkbox";
		content.appendChild(cb_sub_groups);
		content.appendChild(document.createTextNode(" A group can contain sub-groups "));
		var help = document.createElement("IMG");
		help.src = theme.icons_16.help;
		content.appendChild(help);
		tooltip(help, "If yes, you can create sub-groups under a group, thus creating a hierarchy of groups");
		content.appendChild(document.createElement("BR"));
		var pop = new popup_window("Create Group Type",theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add),content);
		pop.addOkCancelButtons(function() {
			var name = input_name.value.trim();
			if (name.length == 0) {
				alert("Please enter a name");
				return;
			}
			for (var i = 0; i < group_types.length; ++i)
				if (group_types[i].name.isSame(name)) { alert("A group type already exists with the same name"); return; }
			pop.freeze("Creating new grouping...");
			var gt = {name:name,specialization_dependent:cb_spe.checked,sub_groups:cb_sub_groups.checked};
			service.json("students_groups","new_group_type",gt,function(res) {
				pop.close();
				if (!res) return;
				gt.id = res.id;
				gt.builtin = false;
				group_types.push(gt);
				var select = document.getElementById('select_group_type');
				var o = document.createElement("OPTION");
				o.value = gt.id;
				o.text = name;
				select.add(o);
				select.selectedIndex = select.options.length-1;
				changeGroupType(gt.id);
			});
		});
		pop.show();
	});
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
	var selected_node = getCookie("students_groups_tree_node");
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