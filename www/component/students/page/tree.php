<?php 
class page_tree extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		$this->require_javascript("vertical_layout.js");
		$this->require_javascript("splitter_vertical.js");
		$this->require_javascript("header_bar.js");
		$this->require_javascript("tree.js");
		$this->require_javascript("tree_functionalities.js");
		$this->require_javascript("curriculum_objects.js");
		theme::css($this, "tree.css");
		$can_edit = PNApplication::$instance->user_management->has_right("manage_batches");
		
		require_once("component/curriculum/CurriculumJSON.inc");
		
		// TODO remove that
		require_once("component/data_model/page/utils.inc");
		require_once("component/data_model/page/table_datadisplay_edit.inc");
		table_datadisplay_edit($this, "StudentBatch", null, null, "create_new_batch_table");
		table_datadisplay_edit($this, "AcademicPeriod", null, null, "create_academic_period_table");
		
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
		<div id='page_header' class='header_bar_menubar_style'>
		</div>
		<iframe id='students_page' name='students_page' layout='fill' style='border:0px' frameBorder=0>
		</iframe>
	</div>
</div>
<script type='text/javascript'>
new vertical_layout('left');
new vertical_layout('right');
new splitter_vertical('container',0.25);
new header_bar('tree_header','toolbar');

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
		for (var i = 0; i < menu_items.length; ++i) {
			if (menu_items[i].page == url.path) {
				getIFrameWindow(frame).location.href = menu_items[i].page+"?"+menu_items[i].params;
				found = true;
				break;
			}
		}
		if (!found) // go to first item (students list)
			getIFrameWindow(frame).location.href = menu_items[0].page+"?"+menu_items[0].params;
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
	this.item = createTreeItemSingleCell(null, "All Students", true, function() {
		window.parent.resetLeftControls();
		var title = document.createElement("DIV");
		title.innerHTML = "<img src='/static/curriculum/batch_32.png'/> All Students";
		title.className = "pn_application_section_title";
		window.parent.addLeftControl(title);
		window.parent.resetMenu();
		var info = document.createElement("DIV");
		var nb_batches = 0;
		for (var i = 0; i < t.item.children.length; ++i)
			nb_batches += t.item.children[i].children.length;
		info.innerHTML = nb_batches+" batch(es)";
		info.className = "pn_application_menu_simple_text";
		window.parent.addMenuControl(info);
		
		setMenuParams("list", "");
		setMenuParams("pictures", "");
		setMenuParams("updates", "sections="+encodeURIComponent("[{name:'students'}]")); 
		setMenuParams("curriculum", "");
		setMenuParams("grades", "");
		setMenuParams("discipline", "");
		setMenuParams("health", "");
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
	this.item = createTreeItemSingleCell(null, "Current Students", true, function() {
		window.parent.resetLeftControls();
		var title = document.createElement("DIV");
		title.innerHTML = "<img src='/static/curriculum/batch_32.png'/> Current Students";
		title.className = "pn_application_section_title";
		window.parent.addLeftControl(title);
		window.parent.resetMenu();
		var info = document.createElement("DIV");
		info.innerHTML = t.item.children.length+" batch(es)";
		info.className = "pn_application_menu_simple_text";
		window.parent.addMenuControl(info);
		var batches = "";
		var tags = "";
		for (var i = 0; i < t.item.children.length; ++i) {
			if (batches.length > 0) { batches += ","; tags += ","; }
			batches += t.item.children[i].node.batch.id;
			tags += "'batch"+t.item.children[i].node.batch.id+"'";
		}
		setMenuParams("list", "batches="+encodeURIComponent(batches));
		setMenuParams("pictures", "batches="+encodeURIComponent(batches));
		setMenuParams("updates", "sections="+encodeURIComponent("[{name:'students',tags:["+tags+"]}]")); 
		setMenuParams("curriculum", "");
		setMenuParams("grades", "");
		setMenuParams("discipline", "");
		setMenuParams("health", "");
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
	this.item = createTreeItemSingleCell(null, "Alumni", true, function() {
		window.parent.resetLeftControls();
		var title = document.createElement("DIV");
		title.innerHTML = "<img src='/static/curriculum/batch_32.png'/> Alumni";
		title.className = "pn_application_section_title";
		window.parent.addLeftControl(title);
		window.parent.resetMenu();
		var info = document.createElement("DIV");
		info.innerHTML = t.item.children.length+" batch(es)";
		info.className = "pn_application_menu_simple_text";
		window.parent.addMenuControl(info);

		var batches = "";
		var tags = [];
		for (var i = 0; i < t.item.children.length; ++i) {
			if (batches.length > 0) { batches += ","; tags += ","; }
			batches += t.item.children[i].node.batch.id;
			tags += "'batch"+t.item.children[i].node.batch.id+"'";
		}
		setMenuParams("list", "batches="+encodeURIComponent(batches));
		setMenuParams("pictures", "batches="+encodeURIComponent(batches));
		setMenuParams("updates", "sections="+encodeURIComponent("[{name:'students',tags:["+tags+"]}]")); 
		setMenuParams("curriculum", "");
		setMenuParams("grades", "");
		setMenuParams("discipline", "");
		setMenuParams("health", "");
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
	var t=this;
	var context_menu_builder = <?php if ($can_edit) {
	?>function(menu) {
		menu.addIconItem(theme.icons_16.edit, "Edit periods and specializations", function() { edit_batch(batch); });
		menu.addIconItem(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.remove,"right_bottom"), "Remove Batch", function() { remove_batch(batch); });
	};<?php	} else { echo "null;"; } ?>
		this.item = createTreeItemSingleCell("/static/curriculum/batch_16.png", span, !is_alumni, function() {
		window.parent.resetLeftControls();
		var title = document.createElement("DIV");
		title.appendChild(document.createTextNode("Batch "));
		<?php datamodel_cell_inline($this, "this.cell_name", "title", $can_edit, "StudentBatch", "name", "batch.id", null, "batch.name"); ?>
		var title_container = document.createElement("DIV");
		title_container.innerHTML = "<img src='/static/curriculum/batch_32.png'/> ";
		title.style.display = "inline-block";
		title_container.appendChild(title);
		title_container.className = "pn_application_section_title";
		window.parent.addLeftControl(title_container);

		window.parent.resetMenu();
		var span = document.createElement("SPAN");
		title = document.createElement("SPAN");
		title.style.fontWeight = "bold";
		title.appendChild(document.createTextNode("Integration"));
		span.appendChild(title);
		span.appendChild(document.createTextNode(": "));
		var span_integration = document.createElement("SPAN");
		span.appendChild(span_integration);
		span.className = "pn_application_menu_simple_text";
		window.parent.addMenuControl(span);

		span = document.createElement("SPAN");
		title = document.createElement("SPAN");
		title.style.fontWeight = "bold";
		title.appendChild(document.createTextNode("Graduation"));
		span.appendChild(title);
		span.appendChild(document.createTextNode(": "));
		var span_graduation = document.createElement("SPAN");
		span.appendChild(span_graduation);
		span.className = "pn_application_menu_simple_text";
		window.parent.addMenuControl(span);
				
		<?php 
		datamodel_cell_inline($this, "this.cell_start", "span_integration", false, "StudentBatch", "start_date", "batch.id", null, "batch.start_date");
		datamodel_cell_inline($this, "this.cell_end", "span_graduation", false, "StudentBatch", "end_date", "batch.id", null, "batch.end_date");
		?>

		var button = document.createElement("DIV");
		button.className = "button_integrated";
		button.innerHTML = "<img src='"+theme.icons_16.edit+"'/> Edit periods and specializations";
		button.onclick = function() { edit_batch(batch); };
		window.parent.addMenuControl(button);
		
		setMenuParams("list", "batches="+batch.id);
		setMenuParams("pictures", "batches="+batch.id);
		setMenuParams("updates", "sections="+encodeURIComponent("[{name:'students',tags:['batch"+batch.id+"']}]")); 
		setMenuParams("curriculum", "batch="+batch.id);
		setMenuParams("grades", "");
		setMenuParams("discipline", "");
		setMenuParams("health", "");
		t._onselect();
	},context_menu_builder,null);
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
	var t=this;
	this.item = createTreeItemSingleCell(theme.build_icon("/static/curriculum/hat.png", "/static/curriculum/calendar_10.gif", "right_bottom"), span, parseSQLDate(period.end_date).getTime() > now && parseSQLDate(period.start_date).getTime() < now, function() {
		window.parent.resetLeftControls();
		
		var title_container = document.createElement("DIV");
		title_container.innerHTML = "<img src='/static/curriculum/batch_32.png'/> ";
		var title = document.createElement("DIV");
		title.appendChild(document.createTextNode("Batch "));
		var cell = document.createElement("SPAN"); title.appendChild(cell);
		<?php datamodel_cell_inline($this, "t.cell_batch", "cell", $can_edit, "StudentBatch", "name", "batch_node.batch.id", null, "batch_node.batch.name"); ?>
		title.style.display = "inline-block";
		title_container.appendChild(title);
		title_container.className = "pn_application_section_title pn_application_menu_separator";
		title_container.style.display = "inline-block";
		window.parent.addLeftControl(title_container);

		title_container = document.createElement("DIV");
		title_container.innerHTML = "<img src='"+theme.build_icon("/static/calendar/calendar_32.png", "/static/curriculum/hat.png", "right_bottom")+"'/> ";
		title = document.createElement("DIV");
		title.appendChild(document.createTextNode("Period "));
		cell = document.createElement("SPAN"); title.appendChild(cell);
		<?php datamodel_cell_inline($this, "t.cell_name", "cell", $can_edit, "AcademicPeriod", "name", "period.id", null, "period.name"); ?>
		title.style.display = "inline-block";
		title_container.appendChild(title);
		title_container.className = "pn_application_section_title";
		title_container.style.display = "inline-block";
		window.parent.addLeftControl(title_container);
				
		window.parent.resetMenu();
		var span = document.createElement("SPAN");
		title = document.createElement("SPAN");
		title.style.fontWeight = "bold";
		title.appendChild(document.createTextNode("Start"));
		span.appendChild(title);
		span.appendChild(document.createTextNode(": "));
		var span_start = document.createElement("SPAN");
		span.appendChild(span_start);
		span.className = "pn_application_menu_simple_text";
		window.parent.addMenuControl(span);
		
		span = document.createElement("SPAN");
		title = document.createElement("SPAN");
		title.style.fontWeight = "bold";
		title.appendChild(document.createTextNode("End"));
		span.appendChild(title);
		span.appendChild(document.createTextNode(": "));
		var span_end = document.createElement("SPAN");
		span.appendChild(span_end);
		span.className = "pn_application_menu_simple_text";
		window.parent.addMenuControl(span);
						
		<?php
		datamodel_cell_inline($this, "t.cell_start", "span_start", false, "AcademicPeriod", "start_date", "period.id", null, "period.start_date");
		datamodel_cell_inline($this, "t.cell_end", "span_end", false, "AcademicPeriod", "end_date", "period.id", null, "period.end_date");
		?>
		
		var button = document.createElement("DIV");
		button.className = "button_integrated";
		button.innerHTML = "<img src='"+theme.icons_16.edit+"'/> Edit periods and specializations";
		button.onclick = function() { edit_batch(batch_node.batch); };
		window.parent.addMenuControl(button);
				
		setMenuParams("list", "period="+period.id);
		setMenuParams("pictures", "period="+period.id);
		setMenuParams("updates", "sections="+encodeURIComponent("[{name:'students',tags:['period"+period.id+"']}]")); 
		setMenuParams("curriculum", "period="+period.id);
		setMenuParams("grades", "period="+period.id);
		setMenuParams("discipline", "");
		setMenuParams("health", "");
		t._onselect();
	},null,null);
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
	var t=this;
	window.top.datamodel.addCellChangeListener(window, 'Specialization', 'name', spe_id, function(value){
		spe_name.nodeValue = value;
		spe.name = value;
	});
	span.appendChild(spe_name);
	var t=this;
	this.item = createTreeItemSingleCell("/static/curriculum/curriculum_16.png", span, true, function() {
		window.parent.resetLeftControls();

		var title_container = document.createElement("DIV");
		title_container.innerHTML = "<img src='/static/curriculum/batch_32.png'/> ";
		var title = document.createElement("DIV");
		title.appendChild(document.createTextNode("Batch "));
		var cell;
		var cell_container = document.createElement("SPAN"); title.appendChild(cell_container);
		<?php datamodel_cell_inline($this, "cell", "cell_container", $can_edit, "StudentBatch", "name", "period_node.parent.batch.id", null, "period_node.parent.batch.name"); ?>
		title.style.display = "inline-block";
		title_container.appendChild(title);
		title_container.className = "pn_application_section_title pn_application_menu_separator";
		title_container.style.display = "inline-block";
		window.parent.addLeftControl(title_container);

		title_container = document.createElement("DIV");
		title_container.innerHTML = "<img src='"+theme.build_icon("/static/calendar/calendar_32.png", "/static/curriculum/hat.png", "right_bottom")+"'/> ";
		title = document.createElement("DIV");
		title.appendChild(document.createTextNode("Period "));
		cell_container = document.createElement("SPAN"); title.appendChild(cell_container);
		<?php datamodel_cell_inline($this, "cell", "cell_container", $can_edit, "AcademicPeriod", "name", "period_node.period.id", null, "period_node.period.name"); ?>
		title.style.display = "inline-block";
		title_container.appendChild(title);
		title_container.className = "pn_application_section_title pn_application_menu_separator";
		title_container.style.display = "inline-block";
		window.parent.addLeftControl(title_container);
		
		title_container = document.createElement("DIV");
		title_container.innerHTML = "<img src='/static/curriculum/curriculum_32.png'/> ";
		title = document.createElement("DIV");
		title.appendChild(document.createTextNode("Specialization "));
		cell_container = document.createElement("SPAN"); title.appendChild(cell_container);
		<?php datamodel_cell_inline($this, "cell", "cell_container", $can_edit, "Specialization", "name", "spe_id", null, "spe.name"); ?>
		title.style.display = "inline-block";
		title_container.appendChild(title);
		title_container.className = "pn_application_section_title";
		title_container.style.display = "inline-block";
		window.parent.addLeftControl(title_container);
		
		window.parent.resetMenu();

		var button = document.createElement("DIV");
		button.className = "button_integrated";
		button.innerHTML = "<img src='"+theme.icons_16.edit+"'/> Edit periods and specializations";
		button.onclick = function() { edit_batch(period_node.parent.batch); };
		window.parent.addMenuControl(button);
		
		setMenuParams("list", "period="+period_node.period.id+"&spe="+spe_id);
		setMenuParams("pictures", "period="+period_node.period.id+"&spe="+spe_id);
		setMenuParams("updates", "sections="+encodeURIComponent("[{name:'students',tags:['period"+period_node.period.id+"']}]")); // TODO better 
		setMenuParams("curriculum", "period="+period_node.period.id);
		setMenuParams("grades", "period="+period_node.period.id+"&specialization="+spe_id);
		setMenuParams("discipline", "");
		setMenuParams("health", "");
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
	var t=this;
	this.item = createTreeItemSingleCell("/static/curriculum/batch_16.png", span, true, function() {
		var batch, period, spe;
		if (cl.spe_id) {
			spe = parent;
			period = spe.parent;
			batch = period.parent;
		} else {
			period = parent;
			batch = period.parent;
			spe = null;
		}
		window.parent.resetLeftControls();

		var title_container = document.createElement("DIV");
		title_container.innerHTML = "<img src='/static/curriculum/batch_32.png'/> ";
		var title = document.createElement("DIV");
		title.appendChild(document.createTextNode("Batch "));
		var cell;
		var cell_container = document.createElement("SPAN"); title.appendChild(cell_container);
		<?php datamodel_cell_inline($this, "cell", "cell_container", $can_edit, "StudentBatch", "name", "batch.batch.id", null, "batch.batch.name"); ?>
		title.style.display = "inline-block";
		title_container.appendChild(title);
		title_container.className = "pn_application_section_title pn_application_menu_separator";
		title_container.style.display = "inline-block";
		window.parent.addLeftControl(title_container);

		title_container = document.createElement("DIV");
		title_container.innerHTML = "<img src='"+theme.build_icon("/static/calendar/calendar_32.png", "/static/curriculum/hat.png", "right_bottom")+"'/> ";
		title = document.createElement("DIV");
		title.appendChild(document.createTextNode("Period "));
		cell_container = document.createElement("SPAN"); title.appendChild(cell_container);
		<?php datamodel_cell_inline($this, "cell", "cell_container", $can_edit, "AcademicPeriod", "name", "period.period.id", null, "period.period.name"); ?>
		title.style.display = "inline-block";
		title_container.appendChild(title);
		title_container.className = "pn_application_section_title pn_application_menu_separator";
		title_container.style.display = "inline-block";
		window.parent.addLeftControl(title_container);

		if (spe) {
			title_container = document.createElement("DIV");
			title_container.innerHTML = "<img src='/static/curriculum/curriculum_32.png'/> ";
			title = document.createElement("DIV");
			title.appendChild(document.createTextNode("Specialization "));
			cell_container = document.createElement("SPAN"); title.appendChild(cell_container);
			<?php datamodel_cell_inline($this, "cell", "cell_container", $can_edit, "Specialization", "name", "spe.spe.id", null, "spe.spe.name"); ?>
			title.style.display = "inline-block";
			title_container.appendChild(title);
			title_container.className = "pn_application_section_title pn_application_menu_separator";
			title_container.style.display = "inline-block";
			window.parent.addLeftControl(title_container);
		}		
		
		title_container = document.createElement("DIV");
		title_container.innerHTML = "<img src='/static/curriculum/batch_32.png'/> ";
		title = document.createElement("DIV");
		title.appendChild(document.createTextNode("Class "));
		cell_container = document.createElement("SPAN"); title.appendChild(cell_container);
		<?php datamodel_cell_inline($this, "cell", "cell_container", $can_edit, "AcademicClass", "name", "cl.id", null, "cl.name"); ?>
		title.style.display = "inline-block";
		title_container.appendChild(title);
		title_container.className = "pn_application_section_title";
		title_container.style.display = "inline-block";
		window.parent.addLeftControl(title_container);
		
		window.parent.resetMenu();

		setMenuParams("list", "class="+cl.id);
		setMenuParams("pictures", "class="+cl.id);
		setMenuParams("updates", "sections="+encodeURIComponent("[{name:'students',tags:['class"+cl.id+"']}]")); // TODO better 
		setMenuParams("curriculum", "period="+period.period.id);
		setMenuParams("grades", "class="+cl.id);
		setMenuParams("discipline", "");
		setMenuParams("health", "");
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
		if (menu_items[i].id == id) item = menu_items[i];
		else menu_items[i].button.className = "button";
	document.getElementById('students_page').src = item.page+"?"+item.params;
	item.button.className = "button selected";
}
function updateMenu() {
	var selected_node = tr.getSelectedItem();
	if (selected_node) selected_node = selected_node.node;
	for (var i = 0; i < menu_items.length; ++i) {
		if (menu_items[i].menu_link)
			menu_items[i].menu_link.href = "/dynamic/students/page/tree?page="+menu_items[i].id+(selected_node ? "#"+selected_node.tag : "");
	}
}
function setMenuParams(id, params) {
	for (var i = 0; i < menu_items.length; ++i) {
		if (menu_items[i].id != id) continue;
		menu_items[i].params = params;
		break;
	}
}
document.getElementById('students_page').onload = function() {
	var f = document.getElementById('students_page');
	f = getIFrameWindow(f);
	f = new URL(f.location.href);
	for (var i = 0; i < menu_items.length; ++i) {
		if (f.path == menu_items[i].page) menu_items[i].button.className = "button selected";
		else menu_items[i].button.className = "button";
	}
};

//Initilization of the page and menu
if (window.parent.resetAllMenus) window.parent.resetAllMenus(); 
build_tree();
var node = root.findTag(url.hash);
if (node) node.item.select();

{
	var selected_node = tr.getSelectedItem();
	if (selected_node) selected_node = selected_node.node;
	for (var i = 0; i < menu_items.length; ++i) {
		var item = menu_items[i];
		var button = document.createElement("A");
		item.button = button;
		button.item = item;
		button.className = "button";
		button.innerHTML = "<img src='"+item.icon+"'/> "+item.name;
		button.onmouseover = function() {
			if (this._tooltip) return;
			for (var i = 0; i < menu_items.length; ++i)
				if (menu_items[i].button._tooltip) {
					document.body.removeChild(menu_items[i].button._tooltip);
					menu_items[i].button._tooltip = null;
				}
			this._tooltip = document.createElement("DIV");
			this._tooltip.className = "tooltip";
			this._tooltip.style.position = "absolute";
			this._tooltip.style.top = (absoluteTop(this)+this.scrollHeight+7)+"px";
			this._tooltip.style.left = absoluteLeft(this)+"px";
			this._tooltip.appendChild(document.createTextNode(this.item.info_text));
			document.body.appendChild(this._tooltip);
		};
		button.onmouseout = function() {
			if (!this._tooltip) return;
			document.body.removeChild(this._tooltip);
			this._tooltip = null;
		};
		button.href = "/dynamic/students/page/tree?page="+item.id+(selected_node ? "#"+selected_node.tag : "");
		button.onclick = function(ev) {
			selectPage(this.item.id);
			stopEventPropagation(ev);
			return false;
		};
		document.getElementById('page_header').appendChild(button);
	}
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
	ac.input.style.margin = "2px";
	window.parent.addRightControl(container);
});

// To Do List
<?php if ($can_edit) { ?>
var todo_list_content = null;
function refresh_todo_list() {
	service.customOutput("students","todo_list",{},function(html) {
		if (html.length == 0) {
			if (todo_list_content != null) {
				var left = document.getElementById('left');
				left.removeChild(todo_list_content.container);
				layout.invalidate(left);
				todo_list_content = null;
			}
			return;
		}
		if (todo_list_content == null) {
			var div = document.createElement("DIV");
			var header = document.createElement("DIV");
			header.className = "help_header";
			header.style.fontWeight = "bold";
			header.style.borderTop = "1px solid black";
			header.innerHTML = "<img src='"+theme.icons_16.help+"' style='vertical-align:bottom'/> To Do List";
			setBorderRadius(header, 3,3,3,3, 0,0,0,0);
			var img = document.createElement("IMG");
			img.src = theme.icons_10.refresh;
			img.className = "button_verysoft";
			img.style.padding = "0px";
			img.onclick = function() {
				todo_list_content.innerHTML = "<img src='"+theme.icons_16.loading+"'/>";
				refresh_todo_list();
			};
			img.title = 'Refresh To Do List';
			img.style.marginLeft = "6px";
			img.style.verticalAlign = "bottom";
			header.appendChild(img);
			img = document.createElement("IMG");
			img.src = theme.icons_10.close;
			img.className = "button_verysoft";
			img.style.padding = "0px";
			img.onclick = function() {
				var left = document.getElementById('left');
				left.removeChild(todo_list_content.container);
				layout.invalidate(left);
				todo_list_content = null;
			};
			img.title = 'Close To Do List';
			img.style.marginLeft = "6px";
			img.style.verticalAlign = "bottom";
			header.appendChild(img);
			div.appendChild(header);
			todo_list_content = document.createElement("DIV");
			todo_list_content.style.backgroundColor = "white";
			todo_list_content.style.overflow = "auto";
			todo_list_content.style.height = "100px";
			todo_list_content.container = div;
			div.appendChild(todo_list_content);
			var left = document.getElementById('left');
			left.appendChild(div);
			layout.invalidate(left);
			
		}
		todo_list_content.innerHTML = html;
	});
}
refresh_todo_list();
<?php } ?>

</script>
<?php
	}
	
}
?>