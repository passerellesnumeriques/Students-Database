<?php 
class page_tree extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		$this->require_javascript("vertical_layout.js");
		$this->require_javascript("splitter_vertical.js");
		$this->require_javascript("page_header.js");
		$this->require_javascript("tree.js");
		$this->require_javascript("tree_functionalities.js");
		$can_edit = PNApplication::$instance->user_management->has_right("manage_batches");
		
		require_once("component/data_model/page/utils.inc");
		require_once("component/data_model/page/table_datadisplay_edit.inc");
		table_datadisplay_edit($this, "StudentBatch", null, null, "create_new_batch_table");
		table_datadisplay_edit($this, "AcademicPeriod", null, null, "create_academic_period_table");
		
?>
<div style='width:100%;height:100%' id='container'>
	<div id='left'>
		<div id='tree_header' icon='/static/curriculum/batch_16.png' title='Batches & Classes'>
		</div>
		<div id='tree' layout='fill' style='overflow-y:auto;overflow-x:hidden;background-color:white'></div>
	</div>
	<div id='right' layout='fill'>
		<div id='page_header'></div>
		<iframe id='training_education_page' name='training_education_page' layout='fill' style='border:0px' frameBorder=0>
		</iframe>
	</div>
</div>
<script type='text/javascript'>
tree_header = new page_header('tree_header',true);
header = new page_header('page_header',true);
new vertical_layout('left');
new vertical_layout('right');
new splitter_vertical('container',0.25);
require("horizontal_menu.js",function() {
	var doit = function() {
		if (typeof header.menu != 'undefined') { setTimeout(doit, 10); }
		header.menu.valign = "middle";
	};
	doit();
});

// List of specializations
var specializations = [<?php
	$spe = SQLQuery::create()->select("Specialization")->execute();
	$first = true;
	foreach ($spe as $s) {
		if ($first) $first = false; else echo ",";
		echo "{id:".$s["id"].",name:".json_encode($s["name"])."}";
	} 
?>];

// Tree nodes
var selected_node = null;
function TreeNode() {
}
TreeNode.prototype = {
	parent: null,
	children: null,
	tag: "",
	_build_item: function(icon, text, expanded) {
		var t=this;
		this.element =  document.createElement("DIV");
		this.element.style.display = "inline-block";
		this.element.style.border = "1px solid rgba(0,0,0,0)";
		this.element.style.cursor = 'pointer';
		this.element.style.padding = "1px 2px 1px 1px";
		setBorderRadius(this.element, 3, 3, 3, 3, 3, 3, 3, 3);
		this.element.onmouseover = function() { if(!t.selected) this.style.border = "1px solid #F0D080"; };
		this.element.onmouseout = function() { if (!t.selected) this.style.border = "1px solid rgba(0,0,0,0)"; };
		this.element.onclick = function() { t.select(); };
		if (icon) {
			var img = document.createElement("IMG");
			img.src = icon;
			img.style.marginRight = "2px";
			img.style.verticalAlign = "bottom";
			this.element.appendChild(img);
		}
		if (typeof text == 'string') {
			var span = document.createElement("SPAN");
			span.appendChild(document.createTextNode(text));
			this.element.appendChild(span);
		} else
			this.element.appendChild(text);
		<?php if ($can_edit) {?>
			this.element.oncontextmenu = function(ev) {
				require("context_menu.js",function() {
					var menu = new context_menu();
					t._build_context_menu(menu);
					if (menu.getItems().length > 0)
						menu.showBelowElement(t.element);
				});
				stopEventPropagation(ev);
				return false;
			};
			var img = document.createElement("IMG");
			img.src = theme.icons_10.arrow_down_context_menu;
			img.className = "button";
			img.style.padding = "0px";
			img.style.verticalAlign = "bottom";
			img.style.marginLeft = "2px";
			img.onclick = function(ev) { t.element.oncontextmenu(ev); stopEventPropagation(ev); return false; };
			this.element.appendChild(img);
		<?php }?>
		this.item = new TreeItem(this.element, expanded);
		this.parent.item.addItem(this.item);
	},
	selected: false,
	select: function() {
		if (selected_node && selected_node.element) {
			selected_node.element.style.border = "1px solid rgba(0,0,0,0)";
			selected_node.element.style.backgroundColor = '';
			selected_node.element.style.background = '';
		}
		this.selected = true;
		selected_node = this;
		if (this.element) {
			this.element.style.border = "1px solid #F0D080";
			this.element.style.backgroundColor = '#FFF0D0';
			setBackgroundGradient(this.element, "vertical", [{pos:0,color:'#FFF0D0'},{pos:100,color:'#F0D080'}]);
		}
		this._select();
		// refresh frame
		var frame = document.getElementById('training_education_page');
		var url = new URL(getIFrameWindow(frame).location.href);
		var found = false;
		for (var i = 0; i < menu_items.length; ++i) {
			var menu_url = new URL(menu_items[i].href);
			if (menu_url.path == url.path) {
				found = true;
				getIFrameWindow(frame).location.href = menu_url.toString();
				break;
			}
		}
		if (!found) frame.src = menu_items[0].href;
	},
	_select: function(){},
	findTag: function(tag) {
		if (this.tag == tag) return this;
		for (var i = 0; i < this.children.length; ++i) {
			var n = this.children[i].findTag(tag);
			if (n) return n;
		}
		return null;
	},
	remove: function() {
		this.parent.item.removeItem(this.item);
		this.parent.children.remove(this);
	}
};
function extendsTreeNode(cl) { cl.prototype = new TreeNode; cl.prototype.constructor = cl; }

function Root(tr) {
	this.children = new Array();
	this.item = tr;
}
extendsTreeNode(Root);

function AllStudents(root) {
	this.children = new Array();
	this.parent = root;
	root.children.push(this);
	this.tag = "all_students";
	this._build_item(null, "All Students", true);
	this.element.style.fontWeight = "bold";
	this._select = function() {
		header.setTitle("All Students");
		header.resetMenu();
		menuReset();
		menuStudentsList("");
		menuUpdates(null,"Education");
		menuCurriculum("");
		menuGrades("");
		menuDiscipline();
		menuHealth();
	};
	<?php if ($can_edit) {?>
	var create_batch_button = document.createElement("DIV");
	create_batch_button.className = "button";
	create_batch_button.innerHTML = "<img src='"+theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add,"right_bottom")+"'/> New batch";
	tree_header.addMenuItem(create_batch_button);
	create_batch_button.onclick = create_new_batch;
	this._build_context_menu = function(menu) {
		menu.addIconItem(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add,"right_bottom"), "New Batch", create_new_batch);
	};
	<?php } ?>
}
extendsTreeNode(AllStudents);

function CurrentStudents(all) {
	this.children = new Array();
	this.parent = all;
	all.children.push(this);
	this.tag = "current_students";
	this._build_item(null, "Current Students", true);
	this.element.style.fontWeight = "bold";
	this._select = function() {
		header.setTitle("Current Students");
		header.resetMenu();
		menuReset();
		var batches = "";
		var tags = [];
		for (var i = 0; i < this.children.length; ++i) {
			if (batches.length > 0) batches += ",";
			batches += this.children[i].id;
			tags.push("batch"+this.children[i].id);
		}
		menuStudentsList("batches="+encodeURIComponent(batches));
		menuUpdates(tags,"Current Students");
		menuCurriculum("");
		menuGrades("");
		menuDiscipline();
		menuHealth();
	};
	<?php if ($can_edit) {?>
	this._build_context_menu = all._build_context_menu;
	<?php } ?>
}
extendsTreeNode(CurrentStudents);

function Alumni(all) {
	this.children = new Array();
	this.parent = all;
	all.children.push(this);
	this.tag = "alumni";
	this._build_item(null, "Alumni", false);
	this.element.style.fontWeight = "bold";
	this._select = function() {
		header.setTitle("Alumni");
		header.resetMenu();
		menuReset();
		var batches = "";
		var tags = [];
		for (var i = 0; i < this.children.length; ++i) {
			if (batches.length > 0) batches += ",";
			batches += this.children[i].id;
			tags.push("batch"+this.children[i].id);
		}
		menuStudentsList("batches="+encodeURIComponent(batches));
		menuUpdates(tags,"Current Students");
		menuCurriculum("");
		menuGrades("");
		menuDiscipline();
		menuHealth();
	};
	<?php if ($can_edit) {?>
	this._build_context_menu = all._build_context_menu;
	<?php } ?>
}
extendsTreeNode(Alumni);

function Batch(current, alumni, id, name, start, end) {
	this.children = new Array();
	var is_alumni = end.getTime() < new Date().getTime();
	this.parent = is_alumni ? alumni : current;
	this.parent.children.push(this);
	this.tag = "batch"+id;
	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode("Batch "));
	var batch_name = document.createTextNode(name);
	var t=this;
	window.top.datamodel.add_cell_change_listener(window, 'StudentBatch', 'name', id, function(value){
		batch_name.nodeValue = value;
		t.name = value;
	});
	span.appendChild(batch_name);
	this._build_item("/static/curriculum/batch_16.png", span, !is_alumni);
	this.id = id;
	this.name = name;
	this.start = start;
	this.end = end;
	this._select = function() {
		var title = document.createElement("SPAN");
		title.appendChild(document.createTextNode("Batch "));
		var cell;
		<?php datamodel_cell_inline($this, "cell", "title", $can_edit, "StudentBatch", "name", "id", null, "name"); ?>
		header.setTitle(title);
		header.resetMenu();

		var span = document.createElement("SPAN");
		title = document.createElement("SPAN");
		title.style.fontWeight = "bold";
		title.appendChild(document.createTextNode("Integration"));
		span.appendChild(title);
		span.appendChild(document.createTextNode(": "));
		var span_integration = document.createElement("SPAN");
		span.appendChild(span_integration);
		header.addMenuItem(span);

		span = document.createElement("SPAN");
		title = document.createElement("SPAN");
		title.style.fontWeight = "bold";
		title.appendChild(document.createTextNode("Graduation"));
		span.appendChild(title);
		span.appendChild(document.createTextNode(": "));
		var span_graduation = document.createElement("SPAN");
		span.appendChild(span_graduation);
		header.addMenuItem(span);
		
		<?php if ($can_edit) {?>
			require("editable_cell.js",function(){
				new editable_cell(span_integration, "StudentBatch", "start_date", id, "field_date", {maximum_cell:"end_date",can_be_empty:false}, dateToSQL(start), null, function(field) {
					t.start = field.getCurrentData();
				});
				new editable_cell(span_graduation, "StudentBatch", "end_date", id, "field_date", {minimum_cell:"start_date",can_be_empty:false}, dateToSQL(end), null, function(field) {
					t.end = field.getCurrentData();
				});
			});
		<?php } else {
			datamodel_cell_inline($this, "cell", "span_integration", $can_edit, "StudentBatch", "start_date", "id", null, "dateToSQL(start)");
			datamodel_cell_inline($this, "cell", "span_graduation", $can_edit, "StudentBatch", "end_date", "id", null, "dateToSQL(end)");
		} ?>
		
		menuReset();
		menuStudentsList("batches="+id);
		menuUpdates(["batch"+id],"Batch "+name);
		menuCurriculum("batch="+id);
		menuGrades("");
		menuDiscipline();
		menuHealth();
	};
	<?php if ($can_edit) {?>
	this._build_context_menu = function(menu) {
		var t=this;
		menu.addIconItem(theme.build_icon("/static/curriculum/academic_16.png",theme.icons_10.add,"right_bottom"), "New Academic Period", function() { new_academic_period(t); });
		menu.addSeparator();
		menu.addIconItem(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.remove,"right_bottom"), "Remove Batch", function() { remove_batch(t); });
	};
	<?php } ?>
}
extendsTreeNode(Batch);

function AcademicPeriod(batch, id, name, start, end) {
	this.children = new Array();
	this.parent = batch;
	batch.children.push(this);
	this.tag = "period"+id;
	var now = new Date().getTime();
	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode("Period "));
	var period_name = document.createTextNode(name);
	var t=this;
	window.top.datamodel.add_cell_change_listener(window, 'AcademicPeriod', 'name', id, function(value){
		period_name.nodeValue = value;
		t.name = value;
	});
	span.appendChild(period_name);
	this._build_item(theme.build_icon("/static/curriculum/hat.png", "/static/curriculum/calendar_10.gif", "right_bottom"), span, end > now && start < now);
	this.element.style.color = end < now ? "#4040A0" : start > now ? "#A04040" : "#40A040";
	this.id = id;
	this.name = name;
	this.start = start;
	this.end = end;
	this._select = function() {
		var title = document.createElement("SPAN");
		title.appendChild(document.createTextNode("Batch "));
		var cell;
		<?php datamodel_cell_inline($this, "cell", "title", $can_edit, "StudentBatch", "name", "batch.id", null, "batch.name"); ?>
		title.appendChild(document.createTextNode(" > Period "));
		<?php datamodel_cell_inline($this, "cell", "title", $can_edit, "AcademicPeriod", "name", "id", null, "name"); ?>
		header.setTitle(title);
		header.resetMenu();

		var span = document.createElement("SPAN");
		title = document.createElement("SPAN");
		title.style.fontWeight = "bold";
		title.appendChild(document.createTextNode("Start"));
		span.appendChild(title);
		span.appendChild(document.createTextNode(": "));
		var span_start = document.createElement("SPAN");
		span.appendChild(span_start);
		header.addMenuItem(span);

		span = document.createElement("SPAN");
		title = document.createElement("SPAN");
		title.style.fontWeight = "bold";
		title.appendChild(document.createTextNode("End"));
		span.appendChild(title);
		span.appendChild(document.createTextNode(": "));
		var span_end = document.createElement("SPAN");
		span.appendChild(span_end);
		header.addMenuItem(span);
		
		<?php if ($can_edit) {?>
			require("editable_cell.js",function(){
				new editable_cell(span_start, "AcademicPeriod", "start_date", id, "field_date", {maximum_cell:"end_date",can_be_empty:false}, dateToSQL(start), null, function(field) {
					t.start = field.getCurrentData();
				});
				new editable_cell(span_end, "AcademicPeriod", "end_date", id, "field_date", {minimum_cell:"start_date",can_be_empty:false}, dateToSQL(end), null, function(field) {
					t.end = field.getCurrentData();
				});
			});
		<?php } else {
			datamodel_cell_inline($this, "cell", "span_start", $can_edit, "AcademicPeriod", "start_date", "id", null, "dateToSQL(start)");
			datamodel_cell_inline($this, "cell", "span_end", $can_edit, "AcademicPeriod", "end_date", "id", null, "dateToSQL(end)");
		} ?>
		
		menuReset();
		menuStudentsList("period="+id);
		menuUpdates(["period"+id],"Period "+name);
		menuCurriculum("period="+id);
		menuGrades("period="+id);
		menuDiscipline();
		menuHealth();
	};
	<?php if ($can_edit) {?>
	this._build_context_menu = function(menu) {
		var t=this;
		var has_classes = false, has_spe = false;
		for (var i = 0; i < t.children.length; ++i)
			if (t.children[i] instanceof Specialization) has_spe = true;
			else if (t.children[i] instanceof Class) has_classes = true;
		if (has_classes || !has_spe)
			menu.addIconItem(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add,"right_bottom"), "New Class", function() { new_class(t,null); });
		if (has_spe || !has_classes)
			menu.addIconItem(theme.build_icon("/static/curriculum/academic_16.png",theme.icons_10.add,"right_bottom"), "New Specialization", function() { new_specialization(t); });
		menu.addSeparator();
		menu.addIconItem(theme.build_icon("/static/curriculum/academic_16.png",theme.icons_10.remove,"right_bottom"), "Remove Academic Period", function() { remove_period(t); });
	};
	<?php } ?>
}
extendsTreeNode(AcademicPeriod);

function Specialization(period, id, name) {
	this.children = new Array();
	this.parent = period;
	period.children.push(this);
	this.tag = "specialization"+id;
	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode("Sepcialization "));
	var spe_name = document.createTextNode(name);
	var t=this;
	window.top.datamodel.add_cell_change_listener(window, 'Specialization', 'name', id, function(value){
		spe_name.nodeValue = value;
		t.name = value;
	});
	span.appendChild(spe_name);
	this._build_item("/static/curriculum/curriculum_16.png", span, true);
	this.id = id;
	this.name = name;
	this._select = function() {
		var title = document.createElement("SPAN");
		title.appendChild(document.createTextNode("Batch "));
		var cell;
		<?php datamodel_cell_inline($this, "cell", "title", $can_edit, "StudentBatch", "name", "period.parent.id", null, "period.parent.name"); ?>
		title.appendChild(document.createTextNode(" > Period "));
		<?php datamodel_cell_inline($this, "cell", "title", $can_edit, "AcademicPeriod", "name", "period.id", null, "period.name"); ?>
		title.appendChild(document.createTextNode(" > Sepcialization "));
		<?php datamodel_cell_inline($this, "cell", "title", $can_edit, "Specialization", "name", "id", null, "name"); ?>
		header.setTitle(title);
		header.resetMenu();
		menuReset();
		menuStudentsList("period="+period.id+"&spe="+id);
		var tags = [];
		for (var i = 0; i < this.children.length; ++i) {
			if (this.children[i] instanceof Class) tags.push("class"+this.children[i].id);
			else if (this.children[i] instanceof Specialization)
				for (var j = 0; j < this.children[i].children.length; ++j)
					tags.push("class"+this.children[i].children[j].id);
		}
		menuUpdates(tags,"Class "+name);
		menuCurriculum("period="+period.id);
		menuGrades("period="+period.id+"&speclialization="+id);
		menuDiscipline();
		menuHealth();
	};
	<?php if ($can_edit) {?>
	this._build_context_menu = function(menu) {
		var t=this;
		menu.addIconItem(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add,"right_bottom"), "New Class", function() { new_class(period,t); });
		menu.addSeparator();
		menu.addIconItem(theme.build_icon("/static/curriculum/academic_16.png",theme.icons_10.remove,"right_bottom"), "Remove Specialization", function() { remove_specialization(t); });
	};
	<?php } ?>
}
extendsTreeNode(Specialization);

function Class(parent, id, name) {
	this.children = new Array();
	this.parent = parent;
	parent.children.push(this);
	this.tag = "class"+id;
	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode("Class "));
	var cl_name = document.createTextNode(name);
	var t=this;
	window.top.datamodel.add_cell_change_listener(window, 'AcademicClass', 'name', id, function(value){
		cl_name.nodeValue = value;
		t.name = value;
	});
	span.appendChild(cl_name);
	this._build_item("/static/curriculum/batch_16.png", span, true);
	this.id = id;
	this.name = name;
	this._select = function() {
		var batch, period, spe;
		if (parent instanceof Specialization) {
			spe = parent;
			period = spe.parent;
			batch = period.parent;
		} else {
			period = parent;
			batch = period.parent;
			spe = null;
		}			
		var title = document.createElement("SPAN");
		title.appendChild(document.createTextNode("Batch "));
		var cell;
		<?php datamodel_cell_inline($this, "cell", "title", $can_edit, "StudentBatch", "name", "batch.id", null, "batch.name"); ?>
		title.appendChild(document.createTextNode(" > Period "));
		<?php datamodel_cell_inline($this, "cell", "title", $can_edit, "AcademicPeriod", "name", "period.id", null, "period.name"); ?>
		if (spe) {
			title.appendChild(document.createTextNode(" > Sepcialization "));
			<?php datamodel_cell_inline($this, "cell", "title", $can_edit, "Specialization", "name", "spe.id", null, "spe.name"); ?>
		}
		title.appendChild(document.createTextNode(" > Class "));
		<?php datamodel_cell_inline($this, "cell", "title", $can_edit, "AcademicClass", "name", "id", null, "name"); ?>
		header.setTitle(title);
		header.resetMenu();
		menuReset();
		menuStudentsList("class="+id);
		menuUpdates(["class"+id],"Class "+name);
		menuCurriculum("period="+period.id);
		menuGrades("class="+id);
		menuDiscipline();
		menuHealth();
	};
	<?php if ($can_edit) {?>
	this._build_context_menu = function(menu) {
		var t=this;
		menu.addIconItem(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.remove,"right_bottom"), "Remove Class", function() { remove_class(t); });
	};
	<?php } ?>
}
extendsTreeNode(Class);

var root;
function build_tree() {
	var tr = new tree('tree');
	tr.addColumn(new TreeColumn(""));
	var all = new AllStudents(root = new Root(tr));
	var current = new CurrentStudents(all);
	var alumni = new Alumni(all);
	var batch, period, spe, cl;
<?php 
		$batches = SQLQuery::create()->select("StudentBatch")->order_by("StudentBatch","start_date", false)->execute();
		$periods = SQLQuery::create()->select("AcademicPeriod")->order_by("AcademicPeriod", "start_date", true)->execute();
		$spe = SQLQuery::create()->select("AcademicPeriodSpecialization")->join("AcademicPeriodSpecialization","Specialization",array("specialization"=>"id"))->execute();
		$classes = SQLQuery::create()->select("AcademicClass")->execute();
		foreach ($batches as $batch) {
			echo "batch = new Batch(current,alumni,".json_encode($batch["id"]).",".json_encode($batch["name"]).",parseSQLDate(".json_encode($batch["start_date"])."),parseSQLDate(".json_encode($batch["end_date"])."));";
			foreach ($periods as $period) {
				if ($period["batch"] <> $batch["id"]) continue;
				echo "period = new AcademicPeriod(batch,".$period["id"].",".json_encode($period["name"]).",parseSQLDate(".json_encode($period["start_date"])."),parseSQLDate(".json_encode($period["end_date"])."));";
				foreach ($spe as $s) {
					if ($s["period"] <> $period["id"]) continue;
					echo "spe = new Specialization(period,".$s["id"].",".json_encode($s["name"]).");";
					foreach ($classes as $cl) {
						if ($cl["period"] <> $period["id"] || $cl["specialization"] <> $s["id"]) continue;
						echo "cl = new Class(spe,".$cl["id"].",".json_encode($cl["name"]).");";
					}
				}
				foreach ($classes as $cl) {
					if ($cl["period"] <> $period["id"] || $cl["specialization"] <> null) continue;
					echo "cl = new Class(period,".$cl["id"].",".json_encode($cl["name"]).");";
				}
			}
		} 
?>
}

build_tree();

// Menu
var menu_items = [];
function menuReset() {
	menu_items = [];
	window.parent.resetMenu();
}
function menuStudentsList(params) {
	var item = window.parent.addMenuItem("/static/curriculum/batch_16.png", "List", "/dynamic/training_education/page/list?"+params);
	item.target = 'training_education_page';
	menu_items.push(item);
	var item = window.parent.addMenuItem("/static/training_education/pictures_16.png", "Pictures", "/dynamic/training_education/page/pictures?"+params);
	item.target = 'training_education_page';
	menu_items.push(item);
}
function menuUpdates(tags, title) {
	var s;
	if (!tags) s = "null";
	else {
		s = "[";
		for (var i = 0; i < tags.length; ++i) {
			if (s.length > 1) s += ",";
			s += "'"+tags[i]+"'";
		}
		s += "]";
	}
	var item = window.parent.addMenuItem("/static/news/news.png", "Updates", "/dynamic/news/page/news?sections="+encodeURIComponent("[{name:'education',tags:"+s+"}]")+"&title="+encodeURIComponent("Updates for "+title));
	item.target = 'training_education_page';
	menu_items.push(item);
}
function menuCurriculum(params) {
	if (component != 'training') return;
	var item = window.parent.addMenuItem("/static/curriculum/curriculum_16.png", "Curriculum", "/dynamic/curriculum/page/curriculum?"+params);
	item.target = 'training_education_page';
	menu_items.push(item);
}
function menuGrades(params) {
	if (component != 'training') return;
	var item = window.parent.addMenuItem("/static/transcripts/grades.gif", "Grades", "/dynamic/transcripts/page/students_grades?"+params);
	item.target = 'training_education_page';
	menu_items.push(item);
}
function menuDiscipline() {
	if (component != 'education') return;
	var item = window.parent.addMenuItem("/static/discipline/discipline.png", "Discipline", "/dynamic/discipline/page/home");
	item.target = 'training_education_page';
	menu_items.push(item);
}
function menuHealth() {
	if (component != 'education') return;
	var item = window.parent.addMenuItem("/static/health/health.png", "Health", "/dynamic/health/page/home");
	item.target = 'training_education_page';
	menu_items.push(item);
}

// Initilization of the page
var url = new URL(location.href);
var component = url.params['section'];
root.findTag(url.hash).select();
document.getElementById('training_education_page').src = url.params['page'];
</script>
<?php
	}
	
}
?>