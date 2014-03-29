// #depends[curriculum_tree.js]

function CurriculumTreeNode_Batch(parent, batch) {
	this.batch = batch;
	this.is_alumni = parseSQLDate(batch.end_date).getTime() < new Date().getTime();
	CurriculumTreeNode.call(this, parent, "batch"+batch.id, !this.is_alumni);
	batch.periods.sort(function(p1,p2) { return parseSQLDate(p1.start_date).getTime() - parseSQLDate(p2.start_date).getTime();});
	for (var i = 0; i < batch.periods.length; ++i)
		new CurriculumTreeNode_AcademicPeriod(this, batch.periods[i]);
}
CurriculumTreeNode_Batch.prototype = new CurriculumTreeNode;
CurriculumTreeNode_Batch.prototype.constructor = CurriculumTreeNode_Batch;
CurriculumTreeNode_Batch.prototype.getIcon = function() { return "/static/curriculum/batch_16.png"; };
CurriculumTreeNode_Batch.prototype.createTitle = function(editable) {
	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode("Batch "));
	var batch = this.batch;
	window.top.datamodel.create_cell("StudentBatch", null, "name", batch.id, batch.name, editable && can_edit_batches, span, function(value) { batch.name = value; });
	return span;
};
CurriculumTreeNode_Batch.prototype.createInfo = function() {
	var batch = this.batch;
	var div = document.createElement("DIV");
	var span_integration = document.createElement("DIV");
	var b = document.createElement("B"); b.appendChild(document.createTextNode("Integration")); span_integration.appendChild(b); span_integration.appendChild(document.createTextNode(": "));
	window.top.datamodel.create_cell("StudentBatch", null, "start_date", this.batch.id, batch.start_date, false, span_integration, function(value) { batch.start_date = value; });
	div.appendChild(span_integration);
	var span_graduation = document.createElement("DIV");
	var b = document.createElement("B"); b.appendChild(document.createTextNode("Graduation")); span_graduation.appendChild(b); span_graduation.appendChild(document.createTextNode(": "));
	window.top.datamodel.create_cell("StudentBatch", null, "end_date", this.batch.id, batch.end_date, false, span_graduation, function(value) { batch.end_date = value; });
	div.appendChild(span_graduation);
	var button = document.createElement("BUTTON");
	button.className = "button_verysoft";
	button.innerHTML = "<img src='"+theme.icons_16.edit+"'/> Edit";
	button.title = "Edit batch, periods and specializations";
	button.batch = batch;
	button.onclick = function() {
		edit_batch(this.batch);
	};
	div.appendChild(button);
	button = document.createElement("BUTTON");
	button.className = "button_verysoft";
	button.innerHTML = "<img src='"+theme.icons_16.remove+"'/> Remove";
	button.batch = batch;
	button.onclick = function() {
		remove_batch(this.batch);
	};
	div.appendChild(button);
	return div;
};
CurriculumTreeNode_Batch.prototype.getURLParameters = function() {
	return {batch:this.batch.id};
};

function create_new_batch() {
	require("popup_window.js",function(){
		var popup = new popup_window("Create New Batch", theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add), "");
		popup.setContentFrame("/dynamic/curriculum/page/edit_batch?popup=yes&onsave=new_batch_created");
		popup.show();
	});
}
function new_batch_created(id) {
	service.json("curriculum","get_batch",{id:id},function(batch){
		add_batch(batch);
	});
}
function add_batch(batch) {
	var is_alumni = parseSQLDate(batch.end_date).getTime() < new Date().getTime();
	var parent;
	if (is_alumni) parent = window.curriculum_root.findTag("alumni");
	else parent = window.curriculum_root.findTag("current_students");
	new CurriculumTreeNode_Batch(parent, batch);
}
function edit_batch(batch) {
	require("popup_window.js",function(){
		var popup = new popup_window("Edit Batch", theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.edit), "");
		popup.setContentFrame("/dynamic/curriculum/page/edit_batch?popup=yes&id="+batch.id+"&onsave=batch_saved");
		popup.show();
	});
}
function batch_saved(id) {
	var node = window.curriculum_root.findTag("batch"+id);
	node.item.parent.removeItem(node.item);
	service.json("curriculum","get_batch",{id:id},function(batch){
		add_batch(batch);
	});
}
function remove_batch(batch) {
	window.top.datamodel.confirm_remove("StudentBatch", batch.id, function() {
		var node = window.curriculum_root.findTag("batch"+batch.id);
		node.item.parent.removeItem(node.item);
	});
}
