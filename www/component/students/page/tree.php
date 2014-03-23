<?php 
class page_tree extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		$this->require_javascript("vertical_layout.js");
		$this->require_javascript("splitter_vertical.js");
		$this->require_javascript("header_bar.js");
		$this->require_javascript("frame_header.js");
		$this->require_javascript("tree.js");
		$this->require_javascript("tree_functionalities.js");
		$this->require_javascript("curriculum_objects.js");
		theme::css($this, "header_bar.css");
		theme::css($this, "frame_header.css");
		theme::css($this, "tree.css");
		$can_edit = PNApplication::$instance->user_management->has_right("manage_batches");
		
		require_once("component/curriculum/CurriculumJSON.inc");
		require_once("component/data_model/page/utils.inc");
?>
<div style='width:100%;height:100%' id='container'>
	<div id='left'>
		<div id='tree_header' icon='/static/curriculum/batch_16.png' title='Batches &amp; Classes'>
			<?php if ($can_edit) { ?>
			<div class='button_verysoft' onclick='create_new_batch();'>
				<img src='<?php echo theme::make_icon("/static/curriculum/batch_16.png", theme::$icons_10["add"]);?>'/>
				New Batch
			</div>
			<?php } ?>
		</div>
		<div id='tree' style='overflow-y:auto;overflow-x:auto;background-color:white;width:100%;height:100%' layout='fill'></div>
	</div>
	<div id='right'>
	</div>
</div>
<script type='text/javascript'>
new vertical_layout('left');
new vertical_layout('right');
new splitter_vertical('container',0.25);
new header_bar('tree_header','toolbar');
var page_header = new frame_header('right', 'students_page', 28, 'white', 'bottom');

// List of specializations
var specializations = <?php echo CurriculumJSON::SpecializationsJSON(); ?>;
// Batches
var batches = <?php echo CurriculumJSON::BatchesJSON(); ?>;

// Tree nodes
function TreeNode() {
}
TreeNode.prototype = {
	parent: null,
	tag: "",
	_onselect: function() {
		updateMenu();
		// refresh frame
		var frame = document.getElementById('students_page');
		var url = new URL(getIFrameWindow(frame).location.href);
		var found = false;
		var menu_items = page_header.getMenuItems();
		for (var i = 0; i < menu_items.length; ++i) {
			var u = new URL(menu_items[i].link.real_link);
			if (u.path == url.path) {
				getIFrameWindow(frame).location.href = menu_items[i].link.real_link;
				found = true;
				break;
			}
		}
		if (!found) // go to first item (students list)
			getIFrameWindow(frame).location.href = menu_items[0].link.real_link;
		setHeaderInfo(this.getPathControls(), this.getInfoControls());
	},
	findTag: function(tag) {
		if (this.tag == tag) return this;
		for (var i = 0; i < this.item.children.length; ++i) {
			var n = this.item.children[i].node.findTag(tag);
			if (n) return n;
		}
		return null;
	},
	remove: function() {
		this.parent.item.removeItem(this.item);
	},
	getPathControls: function() {
		return [];
	},
	getInfoControls: function() {
		return [];
	}
};
function extendsTreeNode(cl) { cl.prototype = new TreeNode; cl.prototype.constructor = cl; }

function RootNode(tr) {
	this.item = tr;
}
extendsTreeNode(RootNode);

function AllStudentsNode(root) {
	this.parent = root;
	this.tag = "all_students";
	var t=this;
	this.getPathControls = function() {
		var div = document.createElement("DIV");
		div.innerHTML = "All Students";
		return [div];
	};
	this.getInfoControls = function() {
		var div = document.createElement("DIV");
		var nb_batches = 0;
		for (var i = 0; i < t.item.children.length; ++i)
			nb_batches += t.item.children[i].children.length;
		div.innerHTML = nb_batches+" batch(es)";
		return [div];
	};
	this.item = createTreeItemSingleCell(null, "All Students", true, function() {
		setMenuParams("list", {});
		setMenuParams("updates", {sections:"[{name:'students'}]"}); 
		setMenuParams("curriculum", {});
		setMenuParams("grades", {});
		setMenuParams("discipline", {});
		setMenuParams("health", {});
		t._onselect();
	},null,null);
	this.item.node = this;
	this.item.cells[0].addStyle({fontWeight:"bold"});
	this.parent.item.addItem(this.item);
}
extendsTreeNode(AllStudentsNode);

function CurrentStudentsNode(all) {
	this.parent = all;
	this.tag = "current_students";
	var t=this;
	this.getPathControls = function() {
		var div = document.createElement("DIV");
		div.innerHTML = "Current Students";
		return [div];
	};
	this.getInfoControls = function() {
		var div = document.createElement("DIV");
		div.innerHTML = t.item.children.length+" batch(es)";
		return [div];
	};
	this.item = createTreeItemSingleCell(null, "Current Students", true, function() {
		var batches = "";
		var tags = "";
		for (var i = 0; i < t.item.children.length; ++i) {
			if (batches.length > 0) { batches += ","; tags += ","; }
			batches += t.item.children[i].node.batch.id;
			tags += "'batch"+t.item.children[i].node.batch.id+"'";
		}
		setMenuParams("list", {batches:batches});
		setMenuParams("updates", {sections:"[{name:'students',tags:["+tags+"]}]"}); 
		setMenuParams("curriculum", {});
		setMenuParams("grades", {});
		setMenuParams("discipline", {});
		setMenuParams("health", {});
		t._onselect();
	},null,null);
	this.item.node = this;
	this.item.cells[0].addStyle({fontWeight:"bold"});
	this.parent.item.addItem(this.item);
}
extendsTreeNode(CurrentStudentsNode);

function AlumniNode(all) {
	this.parent = all;
	this.tag = "alumni";
	var t=this;
	this.getPathControls = function() {
		var div = document.createElement("DIV");
		div.innerHTML = "Alumni";
		return [div];
	};
	this.getInfoControls = function() {
		var div = document.createElement("DIV");
		div.innerHTML = t.item.children.length+" batch(es)";
		return [div];
	};
	this.item = createTreeItemSingleCell(null, "Alumni", true, function() {
		var batches = "";
		var tags = [];
		for (var i = 0; i < t.item.children.length; ++i) {
			if (batches.length > 0) { batches += ","; tags += ","; }
			batches += t.item.children[i].node.batch.id;
			tags += "'batch"+t.item.children[i].node.batch.id+"'";
		}
		setMenuParams("list", {batches:batches});
		setMenuParams("updates", {sections:"[{name:'students',tags:["+tags+"]}]"}); 
		setMenuParams("curriculum", {});
		setMenuParams("grades", {});
		setMenuParams("discipline", {});
		setMenuParams("health", {});
		t._onselect();
	},null,null);
	this.item.node = this;
	this.item.cells[0].addStyle({fontWeight:"bold"});
	this.parent.item.addItem(this.item);
}
extendsTreeNode(AlumniNode);

function BatchNode(current, alumni, batch) {
	var is_alumni = parseSQLDate(batch.end_date).getTime() < new Date().getTime();
	this.parent = is_alumni ? alumni : current;
	this.tag = "batch"+batch.id;
	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode("Batch "));
	var batch_name = document.createTextNode(batch.name);
	window.top.datamodel.addCellChangeListener(window, 'StudentBatch', 'name', batch.id, function(value){
		batch_name.nodeValue = value;
		batch.name = value;
	});
	span.appendChild(batch_name);
	this.getPathControls = function() {
		var c = this.parent.getPathControls();
		var div = document.createElement("DIV");
		div.appendChild(document.createTextNode("Batch "));
		<?php datamodel_cell_inline($this, "this.cell_name", "div", $can_edit, "StudentBatch", "name", "batch.id", null, "batch.name"); ?>
		c.push(div);
		return c;
	};
	this.getInfoControls = function () {
		var span_integration = document.createElement("SPAN");
		var b = document.createElement("B"); b.appendChild(document.createTextNode("Integration")); span_integration.appendChild(b); span_integration.appendChild(document.createTextNode(": "));
		var span_graduation = document.createElement("SPAN");
		var b = document.createElement("B"); b.appendChild(document.createTextNode("Graduation")); span_graduation.appendChild(b); span_graduation.appendChild(document.createTextNode(": "));
		<?php 
		datamodel_cell_inline($this, "this.cell_start", "span_integration", false, "StudentBatch", "start_date", "batch.id", null, "batch.start_date");
		datamodel_cell_inline($this, "this.cell_end", "span_graduation", false, "StudentBatch", "end_date", "batch.id", null, "batch.end_date");
		?>
		var div = document.createElement("DIV");
		div.appendChild(span_integration);
		div.appendChild(document.createTextNode(" "));
		div.appendChild(span_graduation);
		return [div];
	};
	var t=this;
	var context_menu_builder = <?php if ($can_edit) {
	?>function(menu) {
		menu.addIconItem(theme.icons_16.edit, "Edit periods and specializations", function() { edit_batch(batch); });
		menu.addIconItem(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.remove,"right_bottom"), "Remove Batch", function() { remove_batch(batch); });
	};<?php	} else { echo "null;"; } ?>
	this.item = createTreeItemSingleCell("/static/curriculum/batch_16.png", span, !is_alumni, function() {
		setMenuParams("list", {batches:batch.id});
		setMenuParams("updates", {sections:"[{name:'students',tags:['batch"+batch.id+"']}]"}); 
		setMenuParams("curriculum", {batch:batch.id});
		setMenuParams("grades", {});
		setMenuParams("discipline", {});
		setMenuParams("health", {});
		t._onselect();
	},context_menu_builder,null);
	<?php if ($can_edit) { ?>
	service.customOutput("students","todo_list",{batch_id:batch.id},function(html) {
		if (html.length == 0) return;
		t.item.cells[0].addActionIcon(theme.icons_10.warning, "Some actions need to be done on this batch", function(ev) {
			require("context_menu.js",function() {
				var menu = new context_menu();
				var list = document.createElement("UL");
				list.className = "warning";
				list.innerHTML = html;
				list.onclick = function() { menu.close(); }
				menu.addItem(list, true);
				menu.showBelowElement(t.item.cells[0].element);
			});
			stopEventPropagation(ev);
			return false;
		});
		t.item.makeVisible();
	});
	<?php } ?>
	this.item.node = this;
	var i = 0;
	var start = parseSQLDate(batch.start_date).getTime();
	for (; i < this.parent.item.children.length; ++i)
		if (parseSQLDate(this.parent.item.children[i].node.batch.start_date).getTime() > start) break;
	this.parent.item.insertItem(this.item, i);
	this.batch = batch;
}
extendsTreeNode(BatchNode);

function AcademicPeriodNode(batch_node, period) {
	this.parent = batch_node;
	this.tag = "period"+period.id;
	var now = new Date().getTime();
	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode("Period "));
	var period_name = document.createTextNode(period.name);
	window.top.datamodel.addCellChangeListener(window, 'AcademicPeriod', 'name', period.id, function(value){
		period_name.nodeValue = value;
		period.name = value;
	});
	span.appendChild(period_name);
	this.getPathControls = function() {
		var c = this.parent.getPathControls();
		var div = document.createElement("DIV");
		div.appendChild(document.createTextNode("Period "));
		<?php datamodel_cell_inline($this, "t.cell_name", "div", $can_edit, "AcademicPeriod", "name", "period.id", null, "period.name"); ?>
		c.push(div);
		return c;
	};
	this.getInfoControls = function () {
		var span_start = document.createElement("SPAN");
		var b = document.createElement("B"); b.appendChild(document.createTextNode("Start")); span_start.appendChild(b); span_start.appendChild(document.createTextNode(": "));
		var span_end = document.createElement("SPAN");
		var b = document.createElement("B"); b.appendChild(document.createTextNode("End")); span_end.appendChild(b); span_end.appendChild(document.createTextNode(": "));
		<?php
		datamodel_cell_inline($this, "t.cell_start", "span_start", false, "AcademicPeriod", "start_date", "period.id", null, "period.start_date");
		datamodel_cell_inline($this, "t.cell_end", "span_end", false, "AcademicPeriod", "end_date", "period.id", null, "period.end_date");
		?>
		var div = document.createElement("DIV");
		div.appendChild(span_start);
		div.appendChild(document.createTextNode(" "));
		div.appendChild(span_end);
		return [div];
	};
	var t=this;
	this.item = createTreeItemSingleCell(theme.build_icon("/static/curriculum/hat.png", "/static/curriculum/calendar_10.gif", "right_bottom"), span, parseSQLDate(period.end_date).getTime() > now && parseSQLDate(period.start_date).getTime() < now, function() {
		setMenuParams("list", {period:period.id});
		setMenuParams("updates", {sections:"[{name:'students',tags:['period"+period.id+"']}]"}); 
		setMenuParams("curriculum", {period:period.id});
		setMenuParams("grades", {period:period.id});
		setMenuParams("discipline", {});
		setMenuParams("health", {});
		t._onselect();
	},null,null);
	<?php if ($can_edit) { ?>
	service.customOutput("students","todo_list",{period_id:period.id},function(html) {
		if (html.length == 0) return;
		t.item.cells[0].addActionIcon(theme.icons_10.warning, "Some actions need to be done on this period", function(ev) {
			require("context_menu.js",function() {
				var menu = new context_menu();
				var list = document.createElement("UL");
				list.className = "warning";
				list.innerHTML = html;
				list.onclick = function() { menu.close(); }
				menu.addItem(list, true);
				menu.showBelowElement(t.item.cells[0].element);
			});
			stopEventPropagation(ev);
			return false;
		});
		t.item.makeVisible();
	});
	<?php } ?>
	this.item.node = this;
	this.item.cells[0].addStyle({color: parseSQLDate(period.end_date).getTime() < now ? "#4040A0" : parseSQLDate(period.start_date).getTime() > now ? "#A04040" : "#40A040"});
	var i = 0;
	var start = parseSQLDate(period.start_date).getTime();
	for (; i < this.parent.item.children.length; ++i)
		if (parseSQLDate(this.parent.item.children[i].node.period.start_date).getTime() > start) break;
	this.parent.item.insertItem(this.item, i);
	this.period = period;
	<?php if ($can_edit) {?>
	this.item.cells[0].addContextMenu(function(menu) {
		menu.addIconItem(theme.icons_16.edit, "Edit periods and specializations", function() { edit_batch(batch_node.batch); });
		if (period.available_specializations.length == 0)
			menu.addIconItem(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add,"right_bottom"), "New Class", function() { new_class(t,null); });
	});
	<?php } ?>
}
extendsTreeNode(AcademicPeriodNode);

function SpecializationNode(period_node, spe_id) {
	this.parent = period_node;
	this.tag = "period"+period_node.period.id+"_specialization"+spe_id;
	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode("Specialization "));
	var spe = null;
	for (var i = 0; i < specializations.length; ++i)
		if (specializations[i].id == spe_id) { spe = specializations[i]; break; }
	var spe_name = document.createTextNode(spe.name);
	window.top.datamodel.addCellChangeListener(window, 'Specialization', 'name', spe_id, function(value){
		spe_name.nodeValue = value;
		spe.name = value;
	});
	span.appendChild(spe_name);
	this.getPathControls = function() {
		var c = this.parent.getPathControls();
		var div = document.createElement("DIV");
		div.appendChild(document.createTextNode("Specialization "));
		<?php datamodel_cell_inline($this, "cell", "div", $can_edit, "Specialization", "name", "spe_id", null, "spe.name"); ?>
		c.push(div);
		return c;
	};
	var t=this;
	this.item = createTreeItemSingleCell("/static/curriculum/curriculum_16.png", span, true, function() {
		setMenuParams("list", {period:period_node.period.id,spe:spe_id});
		setMenuParams("updates", {sections:"[{name:'students',tags:['period"+period_node.period.id+"']}]"}); // TODO better 
		setMenuParams("curriculum", {period:period_node.period.id});
		setMenuParams("grades", {period:period_node.period.id,specialization:spe_id});
		setMenuParams("discipline", {});
		setMenuParams("health", {});
		t._onselect();
	},null,null);
	this.item.node = this;
	this.parent.item.addItem(this.item);
	this.spe = spe;
	<?php if ($can_edit) {?>
	this.item.cells[0].addContextMenu(function(menu) {
		menu.addIconItem(theme.icons_16.edit, "Edit periods and specializations", function() { edit_batch(period_node.parent.batch); });
		menu.addIconItem(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add,"right_bottom"), "New Class", function() { new_class(period_node,spe); });
	});
	<?php } ?>
}
extendsTreeNode(SpecializationNode);

function ClassNode(parent, cl) {
	this.parent = parent;
	this.tag = "class"+cl.id;
	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode("Class "));
	var cl_name = document.createTextNode(cl.name);
	window.top.datamodel.addCellChangeListener(window, 'AcademicClass', 'name', cl.id, function(value){
		cl_name.nodeValue = value;
		cl.name = value;
	});
	span.appendChild(cl_name);
	this.getPathControls = function() {
		var c = this.parent.getPathControls();
		var div = document.createElement("DIV");
		div.appendChild(document.createTextNode("Class "));
		<?php datamodel_cell_inline($this, "cell", "div", $can_edit, "AcademicClass", "name", "cl.id", null, "cl.name"); ?>
		c.push(div);
		return c;
	};
	var t=this;
	this.item = createTreeItemSingleCell("/static/curriculum/batch_16.png", span, true, function() {
		var period, spe;
		if (cl.spe_id) {
			spe = parent;
			period = spe.parent;
		} else {
			period = parent;
			spe = null;
		}
		var o = {}; o["class"] = cl.id;
		setMenuParams("list", o);
		setMenuParams("updates", {sections:"[{name:'students',tags:['class"+cl.id+"']}]"}); // TODO better 
		setMenuParams("curriculum", {period:period.period.id});
		setMenuParams("grades", o);
		setMenuParams("discipline", {});
		setMenuParams("health", {});
		t._onselect();
	},null,<?php if ($can_edit) {?>[{icon:theme.icons_10.remove,tooltip:"Remove Class",action:function() { remove_class(t); }}]<?php } else { ?>null<?php } ?>);
	this.item.node = this;
	this.parent.item.addItem(this.item);
	this.cl = cl;
}
extendsTreeNode(ClassNode);

var tr;
var root, all, current, alumni;
function build_tree() {
	tr = new tree('tree');
	tr.addColumn(new TreeColumn(""));
	all = new AllStudentsNode(root = new RootNode(tr));
	current = new CurrentStudentsNode(all);
	alumni = new AlumniNode(all);
	for (var batch_i = 0; batch_i < batches.length; ++batch_i) {
		var batch = batches[batch_i];
		tree_build_batch(batch);
	}
}
function tree_build_batch(batch) {
	var batch_node = new BatchNode(current, alumni, batch);
	for (var period_i = 0; period_i < batch.periods.length; ++period_i) {
		var period = batch.periods[period_i];
		var period_node = new AcademicPeriodNode(batch_node,period);
		for (var spe_i = 0; spe_i < period.available_specializations.length; ++spe_i) {
			var spe_id = period.available_specializations[spe_i];
			var spe_node = new SpecializationNode(period_node, spe_id);
			for (var cl_i = 0; cl_i < period.classes.length; ++cl_i) {
				var cl = period.classes[cl_i];
				if (cl.spe_id != spe_id) continue;
				new ClassNode(spe_node, cl);
			}
		}
		for (var cl_i = 0; cl_i < period.classes.length; ++cl_i) {
			var cl = period.classes[cl_i];
			if (cl.spe_id) continue;
			new ClassNode(period_node, cl);
		}
	}
}


//Initilization of the page
var url = new URL(location.href);
var page = typeof url.params.page != 'undefined' ? url.params.page : "list";

// Menu
page_header.addMenu("list", "/static/curriculum/batch_16.png", "List", "/dynamic/students/page/list", "List of students");
page_header.addMenu("updates", "/static/news/news.png", "Updates", "/dynamic/news/page/news", "What's happening ? What other users did recently ?");
page_header.addMenu("curriculum", "/static/curriculum/curriculum_16.png", "Curriculum", "/dynamic/curriculum/page/curriculum", "List of subjects for each academic period");
page_header.addMenu("grades", "/static/transcripts/grades.gif", "Grades", "/dynamic/transcripts/page/students_grades", "Grades of students for an academic period");
page_header.addMenu("discipline", "/static/discipline/discipline.png", "Discipline", "/dynamic/discipline/page/home", "Follow-up violations, abscences, lateness...");
page_header.addMenu("health", "/static/health/health.png", "Health", "/dynamic/health/page/home", "Follow-up health situation and medical information");

// put fake links in the menu, to allow open in a new window
{
	var menu_items = page_header.getMenuItems();
	for (var i = 0; i < menu_items.length; ++i) {
		menu_items[i].link.real_link = menu_items[i].link.href;
		menu_items[i].start_url = menu_items[i].link.href;
		menu_items[i].link.href = "/dynamic/students/page/tree?page="+menu_items[i].id;
		menu_items[i].link.onclick = function(ev) {
			page_header.frame.src = this.real_link;
			stopEventPropagation(ev);
			return false;
		};
	}
}

function updateMenu() {
	var selected_node = tr.getSelectedItem();
	if (selected_node) selected_node = selected_node.node;
	var menu_items = page_header.getMenuItems();
	for (var i = 0; i < menu_items.length; ++i)
		menu_items[i].link.href = "/dynamic/students/page/tree?page="+menu_items[i].id+(selected_node ? "#"+selected_node.tag : "");
}
function setMenuParams(id, params) {
	var menu_items = page_header.getMenuItems();
	for (var i = 0; i < menu_items.length; ++i) {
		if (menu_items[i].id != id) continue;
		var u = new URL(menu_items[i].link.real_link);
		u.params = params;
		menu_items[i].link.real_link = u.toString();
		break;
	}
}
function selectPage(id) {
	var menu_items = page_header.getMenuItems();
	var item = menu_items[0];
	for (var i = 0; i < menu_items.length; ++i)
		if (menu_items[i].id == id) item = menu_items[i];
	item.link.onclick(createEvent('click'));
}

function setHeaderInfo(paths, controls) {
	if (!window.header_info) return;
	window.header_info.innerHTML = "";
	var container;
	container = document.createElement("DIV");
	container.className = "path_container";
	for (var i = 0; i < paths.length; ++i) {
		if (i > 0) container.appendChild(document.createTextNode(" > "));
		paths[i].className = "path_item";
		container.appendChild(paths[i]);
	}
	window.header_info.appendChild(container);
	var sep = document.createElement("DIV");
	sep.className = "separator";
	window.header_info.appendChild(sep);
	container = document.createElement("DIV");
	container.className = "info_container";
	for (var i = 0; i < controls.length; ++i) {
		controls[i].className = "info_item";
		container.appendChild(controls[i]);
	}
	window.header_info.appendChild(container);
	layout.invalidate(window.header_info);
}

//Initilization of the page and menu
if (window.parent.resetAllMenus) {
	window.parent.add_stylesheet("/static/students/style.css");
	window.parent.resetAllMenus();
	window.parent.addMenuItem("/static/curriculum/batch_16.png", "Batches & Classes", null, "/dynamic/students/page/tree");
	window.header_info = document.createElement("DIV");
	window.header_info.className = "students_header_info";
	window.parent.addMenuControl(window.header_info);
	require("autocomplete.js",function() {
		var container = document.createElement("DIV");
		container.style.display = "inline-block";
		container.style.marginBottom = "5px";
		container.style.verticalAlign = "bottom";
		var ac = new autocomplete(container, 3, 'Search a student', function(name, handler) {
			service.json("students","search_student_by_name", {name:name}, function(res) {
				if (!res) { handler([]); return; }
				var items = [];
				for (var i = 0; i < res.length; ++i) {
					var item = new autocomplete_item(res[i].people_id, res[i].first_name+' '+res[i].last_name, res[i].first_name+' '+res[i].last_name+" (Batch "+res[i].batch_name+")");
					items.push(item); 
				}
				handler(items);
			});
		}, function(item) {
			document.getElementById('students_page').src = "/dynamic/people/page/profile?people="+item.value;
		}, 250);
		setBorderRadius(ac.input,8,8,8,8,8,8,8,8);
		setBoxShadow(ac.input,-1,2,2,0,'#D8D8F0',true);
		ac.input.style.background = "#ffffff url('"+theme.icons_16.search+"') no-repeat 3px 1px";
		ac.input.style.padding = "2px 4px 2px 23px";
		ac.input.style.margin = "2px";
		window.parent.addRightControl(container);
	});
} 
build_tree();
var node = root.findTag(url.hash);
if (node) node.item.select();
selectPage(page);

</script>
<?php
	}
	
}
?>