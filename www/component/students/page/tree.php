<?php 
class page_tree extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		$this->require_javascript("vertical_layout.js");
		$this->require_javascript("splitter_vertical.js");
		$this->require_javascript("page_header.js");
		$this->require_javascript("tree.js");
		$this->require_javascript("tree_functionalities.js");
		theme::css($this, "information_bar.css");
		$can_edit = PNApplication::$instance->user_management->has_right("manage_batches");
		
		require_once("component/data_model/page/utils.inc");
		require_once("component/data_model/page/table_datadisplay_edit.inc");
		table_datadisplay_edit($this, "StudentBatch", null, null, "create_new_batch_table");
		table_datadisplay_edit($this, "AcademicPeriod", null, null, "create_academic_period_table");
		
?>
<div style='width:100%;height:100%' id='container'>
	<div id='left'>
		<div id='tree' style='overflow-y:auto;overflow-x:auto;background-color:white;width:100%;height:100%'></div>
	</div>
	<div id='right'>
		<div id='page_header' class='information_bar'>
			<div id='page_header_title' class='information_bar_title'></div>
			<div class='information_bar_separator'></div>
			<div id='page_header_content'></div>
		</div>
		<iframe id='students_page' name='students_page' layout='fill' style='border:0px' frameBorder=0>
		</iframe>
	</div>
</div>
<script type='text/javascript'>
new vertical_layout('right');
new splitter_vertical('container',0.25);

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
		updateMenu();
		// refresh frame
		var frame = document.getElementById('students_page');
		var url = new URL(getIFrameWindow(frame).location.href);
		for (var i = 0; i < menu_items.length; ++i) {
			if (menu_items[i].page == url.path) {
				getIFrameWindow(frame).location.href = menu_items[i].page+"?"+menu_items[i].params;
				break;
			}
		}
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
		document.getElementById('page_header_title').innerHTML = "<img src='/static/curriculum/batch_32.png'/> All Students";
		var nb_batches = 0;
		for (var i = 0; i < this.children.length; ++i)
			nb_batches += this.children[i].children.length;
		document.getElementById('page_header_content').innerHTML = nb_batches+" batch(s)";
		setMenuParams("list", "");
		setMenuParams("pictures", "");
		setMenuParams("updates", "sections="+encodeURIComponent("[{name:'students'}]")); 
		setMenuParams("curriculum", "");
		setMenuParams("grades", "");
		setMenuParams("discipline", "");
		setMenuParams("health", "");
	};
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
		document.getElementById('page_header_title').innerHTML = "<img src='/static/curriculum/batch_32.png'/> Current Students";
		document.getElementById('page_header_content').innerHTML = this.children.length+" batch(s)";
		var batches = "";
		var tags = "";
		for (var i = 0; i < this.children.length; ++i) {
			if (batches.length > 0) { batches += ","; tags += ","; }
			batches += this.children[i].id;
			tags += "'batch"+this.children[i].id+"'";
		}
		setMenuParams("list", "batches="+encodeURIComponent(batches));
		setMenuParams("pictures", "batches="+encodeURIComponent(batches));
		setMenuParams("updates", "sections="+encodeURIComponent("[{name:'students',tags:["+tags+"]}]")); 
		setMenuParams("curriculum", "");
		setMenuParams("grades", "");
		setMenuParams("discipline", "");
		setMenuParams("health", "");
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
		document.getElementById('page_header_title').innerHTML = "<img src='/static/curriculum/batch_32.png'/> Alumni";
		document.getElementById('page_header_content').innerHTML = this.children.length+" batch(s)";
		var batches = "";
		var tags = [];
		for (var i = 0; i < this.children.length; ++i) {
			if (batches.length > 0) { batches += ","; tags += ","; }
			batches += this.children[i].id;
			tags += "'batch"+this.children[i].id+"'";
		}
		setMenuParams("list", "batches="+encodeURIComponent(batches));
		setMenuParams("pictures", "batches="+encodeURIComponent(batches));
		setMenuParams("updates", "sections="+encodeURIComponent("[{name:'students',tags:["+tags+"]}]")); 
		setMenuParams("curriculum", "");
		setMenuParams("grades", "");
		setMenuParams("discipline", "");
		setMenuParams("health", "");
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
	window.top.datamodel.addCellChangeListener(window, 'StudentBatch', 'name', id, function(value){
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
		<?php datamodel_cell_inline($this, "this.cell_name", "title", $can_edit, "StudentBatch", "name", "id", null, "name"); ?>
		document.getElementById('page_header_title').innerHTML = "<img src='/static/curriculum/batch_32.png'/> ";
		document.getElementById('page_header_title').appendChild(title);

		document.getElementById('page_header_content').innerHTML = "";
		var span = document.createElement("SPAN");
		title = document.createElement("SPAN");
		title.style.fontWeight = "bold";
		title.appendChild(document.createTextNode("Integration"));
		span.appendChild(title);
		span.appendChild(document.createTextNode(": "));
		var span_integration = document.createElement("SPAN");
		span.appendChild(span_integration);
		document.getElementById('page_header_content').appendChild(span);
		document.getElementById('page_header_content').appendChild(document.createElement("BR"));

		span = document.createElement("SPAN");
		title = document.createElement("SPAN");
		title.style.fontWeight = "bold";
		title.appendChild(document.createTextNode("Graduation"));
		span.appendChild(title);
		span.appendChild(document.createTextNode(": "));
		var span_graduation = document.createElement("SPAN");
		span.appendChild(span_graduation);
		document.getElementById('page_header_content').appendChild(span);
		document.getElementById('page_header_content').appendChild(document.createElement("BR"));
		
		<?php if ($can_edit) {?>
			require("editable_cell.js",function(){
				t.cell_start = new editable_cell(span_integration, "StudentBatch", "start_date", id, "field_date", {maximum_cell:"end_date",can_be_empty:false}, dateToSQL(start), null, function(field) {
					t.start = field.getCurrentData();
				});
				t.cell_end = new editable_cell(span_graduation, "StudentBatch", "end_date", id, "field_date", {minimum_cell:"start_date",can_be_empty:false}, dateToSQL(end), null, function(field) {
					t.end = field.getCurrentData();
				});
			});
		<?php } else {
			datamodel_cell_inline($this, "this.cell_start", "span_integration", $can_edit, "StudentBatch", "start_date", "id", null, "dateToSQL(start)");
			datamodel_cell_inline($this, "this.cell_end", "span_graduation", $can_edit, "StudentBatch", "end_date", "id", null, "dateToSQL(end)");
		} ?>
		
		setMenuParams("list", "batches="+id);
		setMenuParams("pictures", "batches="+id);
		setMenuParams("updates", "sections="+encodeURIComponent("[{name:'students',tags:['batch"+id+"']}]")); 
		setMenuParams("curriculum", "batch="+id);
		setMenuParams("grades", "");
		setMenuParams("discipline", "");
		setMenuParams("health", "");
	};
	<?php if ($can_edit) {?>
	this._build_context_menu = function(menu) {
		menu.addIconItem(theme.icons_16.edit, "Edit Batch Information", function() {
			t.select(); 
			t.cell_name.editable_field.edit();
			t.cell_start.editable_field.edit();
			t.cell_end.editable_field.edit();
		});
		menu.addIconItem(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.remove,"right_bottom"), "Remove Batch", function() { remove_batch(t); });
		menu.addSeparator();
		menu.addIconItem(theme.build_icon("/static/curriculum/academic_16.png",theme.icons_10.add,"right_bottom"), "New Academic Period", function() { new_academic_period(t); });
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
	window.top.datamodel.addCellChangeListener(window, 'AcademicPeriod', 'name', id, function(value){
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
		<?php datamodel_cell_inline($this, "t.cell_batch", "title", $can_edit, "StudentBatch", "name", "batch.id", null, "batch.name"); ?>
		title.appendChild(document.createTextNode(" > Period "));
		<?php datamodel_cell_inline($this, "t.cell_name", "title", $can_edit, "AcademicPeriod", "name", "id", null, "name"); ?>
		document.getElementById('page_header_title').innerHTML = "";
		document.getElementById('page_header_title').appendChild(title);

		document.getElementById('page_header_content').innerHTML = "";
		var span = document.createElement("SPAN");
		title = document.createElement("SPAN");
		title.style.fontWeight = "bold";
		title.appendChild(document.createTextNode("Start"));
		span.appendChild(title);
		span.appendChild(document.createTextNode(": "));
		var span_start = document.createElement("SPAN");
		span.appendChild(span_start);
		document.getElementById('page_header_content').appendChild(span);
		document.getElementById('page_header_content').appendChild(document.createElement("BR"));
		
		span = document.createElement("SPAN");
		title = document.createElement("SPAN");
		title.style.fontWeight = "bold";
		title.appendChild(document.createTextNode("End"));
		span.appendChild(title);
		span.appendChild(document.createTextNode(": "));
		var span_end = document.createElement("SPAN");
		span.appendChild(span_end);
		document.getElementById('page_header_content').appendChild(span);
		document.getElementById('page_header_content').appendChild(document.createElement("BR"));
				
		<?php if ($can_edit) {?>
			require("editable_cell.js",function(){
				t.cell_start = new editable_cell(span_start, "AcademicPeriod", "start_date", id, "field_date", {maximum_cell:"end_date",can_be_empty:false}, dateToSQL(start), null, function(field) {
					t.start = field.getCurrentData();
				});
				t.cell_end = new editable_cell(span_end, "AcademicPeriod", "end_date", id, "field_date", {minimum_cell:"start_date",can_be_empty:false}, dateToSQL(end), null, function(field) {
					t.end = field.getCurrentData();
				});
			});
		<?php } else {
			datamodel_cell_inline($this, "t.cell_start", "span_start", $can_edit, "AcademicPeriod", "start_date", "id", null, "dateToSQL(start)");
			datamodel_cell_inline($this, "t.cell_end", "span_end", $can_edit, "AcademicPeriod", "end_date", "id", null, "dateToSQL(end)");
		} ?>
		
		setMenuParams("list", "period="+id);
		setMenuParams("pictures", "period="+id);
		setMenuParams("updates", "sections="+encodeURIComponent("[{name:'students',tags:['period"+id+"']}]")); 
		setMenuParams("curriculum", "period="+id);
		setMenuParams("grades", "period="+id);
		setMenuParams("discipline", "");
		setMenuParams("health", "");
	};
	<?php if ($can_edit) {?>
	this._build_context_menu = function(menu) {
		var t=this;
		menu.addIconItem(theme.icons_16.edit, "Edit Period Information", function() {
			t.select(); 
			t.cell_name.editable_field.edit();
			t.cell_start.editable_field.edit();
			t.cell_end.editable_field.edit();
		});
		menu.addIconItem(theme.build_icon("/static/curriculum/academic_16.png",theme.icons_10.remove,"right_bottom"), "Remove Academic Period", function() { remove_period(t); });
		menu.addSeparator();
		var has_classes = false, has_spe = false;
		for (var i = 0; i < t.children.length; ++i)
			if (t.children[i] instanceof Specialization) has_spe = true;
			else if (t.children[i] instanceof Class) has_classes = true;
		if (has_classes || !has_spe)
			menu.addIconItem(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add,"right_bottom"), "New Class", function() { new_class(t,null); });
		if (has_spe || !has_classes)
			menu.addIconItem(theme.build_icon("/static/curriculum/academic_16.png",theme.icons_10.add,"right_bottom"), "New Specialization", function() { new_specialization(t); });
	};
	<?php } ?>
}
extendsTreeNode(AcademicPeriod);

function Specialization(period, id, name) {
	this.children = new Array();
	this.parent = period;
	period.children.push(this);
	this.tag = "period"+period.id+"_specialization"+id;
	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode("Specialization "));
	var spe_name = document.createTextNode(name);
	var t=this;
	window.top.datamodel.addCellChangeListener(window, 'Specialization', 'name', id, function(value){
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
		document.getElementById('page_header_title').innerHTML = "";
		document.getElementById('page_header_title').appendChild(title);
		document.getElementById('page_header_content').innerHTML = "";
		setMenuParams("list", "period="+period.id+"&spe="+id);
		setMenuParams("pictures", "period="+period.id+"&spe="+id);
		setMenuParams("updates", "sections="+encodeURIComponent("[{name:'students',tags:['period"+period.id+"']}]")); // TODO better 
		setMenuParams("curriculum", "period="+period.id);
		setMenuParams("grades", "period="+period.id+"&specialization="+id);
		setMenuParams("discipline", "");
		setMenuParams("health", "");
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
	window.top.datamodel.addCellChangeListener(window, 'AcademicClass', 'name', id, function(value){
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
		document.getElementById('page_header_title').innerHTML = "";
		document.getElementById('page_header_title').appendChild(title);
		document.getElementById('page_header_content').innerHTML = "";
		setMenuParams("list", "class="+id);
		setMenuParams("pictures", "class="+id);
		setMenuParams("updates", "sections="+encodeURIComponent("[{name:'students',tags:['class"+id+"']}]")); // TODO better 
		setMenuParams("curriculum", "period="+period.id);
		setMenuParams("grades", "class="+id);
		setMenuParams("discipline", "");
		setMenuParams("health", "");
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
		$batches = SQLQuery::create()->select("StudentBatch")->orderBy("StudentBatch","start_date", false)->execute();
		$periods = SQLQuery::create()->select("AcademicPeriod")->orderBy("AcademicPeriod", "start_date", true)->execute();
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


//Initilization of the page
var url = new URL(location.href);
var page = typeof url.params.page != 'undefined' ? url.params.page : "list";

// Menu
var menu_items = [
	{
		id: "list",
		icon: "/static/curriculum/batch_16.png",
		name: "List",
		info_text: "List of students",
		page: "/dynamic/students/page/list",
		params: ""
	},
	{
		id: "pictures",
		icon: "/static/students/pictures_16.png",
		name: "Pictures",
		info_text: "Pictures of students",
		page: "/dynamic/students/page/pictures",
		params: ""
	},
	{
		id: "updates",
		icon: "/static/news/news.png",
		name: "Updates",
		info_text: "What's happening ? What other users did recently ?",
		page: "/dynamic/news/page/news",
		params: ""
	},
	{
		id: "curriculum",
		icon: "/static/curriculum/curriculum_16.png",
		name: "Curriculum",
		info_text: "List of subjects for each academic period",
		page: "/dynamic/curriculum/page/curriculum",
		params: ""
	},
	{
		id: "grades",
		icon: "/static/transcripts/grades.gif",
		name: "Grades",
		info_text: "Grades of students for an academic period",
		page: "/dynamic/transcripts/page/students_grades",
		params: ""
	},
	{
		id: "discipline",
		icon: "/static/discipline/discipline.png",
		name: "Discipline",
		info_text: "Follow-up violations, abscences, lateness...",
		page: "/dynamic/discipline/page/home",
		params: ""
	},
	{
		id: "health",
		icon: "/static/health/health.png",
		name: "Health",
		info_text: "Follow-up health situation and medical information",
		page: "/dynamic/health/page/home",
		params: ""
	}
];
function selectPage(id) {
	var item = menu_items[0];
	for (var i = 0; i < menu_items.length; ++i)
		if (menu_items[i].id == id) { item = menu_items[i]; break; }
	document.getElementById('students_page').src = item.page+"?"+item.params;
}
function updateMenu() {
	for (var i = 0; i < menu_items.length; ++i) {
		if (menu_items[i].menu_link)
			menu_items[i].menu_link.href = "/dynamic/students/page/tree?page="+menu_items[i].id+"#"+selected_node.tag;
	}
}
function setMenuParams(id, params) {
	for (var i = 0; i < menu_items.length; ++i) {
		if (menu_items[i].id != id) continue;
		menu_items[i].params = params;
		break;
	}
}

//Initilization of the page and menu
build_tree();
var node = root.findTag(url.hash);
if (node) node.select();

if (window.parent.addMenuItem)
for (var i = 0; i < menu_items.length; ++i) {
	var item = menu_items[i];
	var m = window.parent.addMenuItem(item.icon, item.name, item.info_text, "/dynamic/students/page/tree?page="+item.id+"#"+selected_node.tag);
	m._menu_item_id = item.id;
	m.onclick = function(ev) {
		selectPage(this._menu_item_id);
		stopEventPropagation(ev);
		return false;
	};
	item.menu_link = m;
}
selectPage(page);

// Put the search student control
if (window.parent.addMenuItem)
require("autocomplete.js",function() {
	var container = document.createElement("DIV");
	container.style.display = "inline-block";
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
		document.getElementById('training_education_page').src = "/dynamic/people/page/profile?people="+item.value;
	}, 250);
	setBorderRadius(ac.input,8,8,8,8,8,8,8,8);
	setBoxShadow(ac.input,-1,2,2,0,'#D8D8F0',true);
	ac.input.style.background = "#ffffff url('"+theme.icons_16.search+"') no-repeat 3px 1px";
	ac.input.style.padding = "2px 4px 2px 23px";
	window.parent.addRightControl(container);
});

</script>
<?php
	}
	
}
?>