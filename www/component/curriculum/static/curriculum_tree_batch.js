// #depends[curriculum_tree.js]

/**
 * Node for a batch
 * @param {CurriculumTreeNode} parent parent node (either current students or alumni)
 * @param {Batch} batch batch object
 */
function CurriculumTreeNode_Batch(parent, batch) {
	this.batch = batch;
	/** Indicates if this batch already graduated or not */
	this.is_alumni = parseSQLDate(batch.end_date).getTime() < new Date().getTime();
	CurriculumTreeNode.call(this, parent, "batch"+batch.id, !this.is_alumni);
	batch.periods.sort(function(p1,p2) {
		var ap1 = getAcademicPeriod(p1.academic_period);
		var ap2 = getAcademicPeriod(p2.academic_period);
		return parseSQLDate(ap1.start).getTime() - parseSQLDate(ap2.start).getTime();
	});
	for (var i = 0; i < batch.periods.length; ++i)
		new CurriculumTreeNode_BatchPeriod(this, batch.periods[i]);
}
CurriculumTreeNode_Batch.prototype = new CurriculumTreeNode;
CurriculumTreeNode_Batch.prototype.constructor = CurriculumTreeNode_Batch;
CurriculumTreeNode_Batch.prototype.getIcon = function() { return "/static/curriculum/batch_16.png"; };
CurriculumTreeNode_Batch.prototype.createTitle = function(editable) {
	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode("Batch "));
	var batch = this.batch;
	window.top.datamodel.create_cell(window, "StudentBatch", null, "name", batch.id, batch.name, "field_text", {can_be_null:false,max_length:100}, editable && can_edit_batches, span, function(value) { batch.name = value; });
	return span;
};
CurriculumTreeNode_Batch.prototype.createInfo = function() {
	var batch = this.batch;
	var div = document.createElement("DIV");
	var span_integration = document.createElement("DIV");
	var b = document.createElement("B"); b.appendChild(document.createTextNode("Integration")); span_integration.appendChild(b); span_integration.appendChild(document.createTextNode(": "));
	window.top.datamodel.create_cell(window, "StudentBatch", null, "start_date", this.batch.id, batch.start_date, "field_date", {}, false, span_integration, function(value) { batch.start_date = value; });
	div.appendChild(span_integration);
	var span_graduation = document.createElement("DIV");
	var b = document.createElement("B"); b.appendChild(document.createTextNode("Graduation")); span_graduation.appendChild(b); span_graduation.appendChild(document.createTextNode(": "));
	window.top.datamodel.create_cell(window, "StudentBatch", null, "end_date", this.batch.id, batch.end_date, "field_date", {}, false, span_graduation, function(value) { batch.end_date = value; });
	div.appendChild(span_graduation);
	if (window.can_edit_batches) {
		var button = document.createElement("BUTTON");
		button.className = "action";
		button.innerHTML = "<img src='"+theme.icons_16.edit+"'/> Edit";
		button.title = "Edit batch, periods and specializations";
		button.batch = batch;
		button.onclick = function() {
			editBatch(this.batch);
		};
		div.appendChild(button);
		button = document.createElement("BUTTON");
		button.className = "action red";
		button.innerHTML = "<img src='"+theme.icons_16.remove_white+"'/> Remove";
		button.batch = batch;
		button.onclick = function() {
			removeBatch(this.batch);
		};
		div.appendChild(button);
	}
	return div;
};
CurriculumTreeNode_Batch.prototype.getURLParameters = function() {
	return {batch:this.batch.id};
};

/** Open a popup to create a new batch */
function createNewBatch() {
	require("popup_window.js",function(){
		var popup = new popup_window("Create New Batch", theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add), "");
		popup.setContentFrame("/dynamic/curriculum/page/edit_batch?popup=yes&onsave=newBatchCreated");
		popup.show();
	});
}
/** Callback when a batch has been created
 * @param {Number} id the ID of the created batch
 */
function newBatchCreated(id) {
	var node = window.curriculum_root.findTag("batch"+id);
	if (node) {
		// batch already created, and saved again
		batchSaved(id);
		return;
	}
	service.json("curriculum","get_academic_calendar",{},function(cal) {
		academic_years = cal;
		service.json("curriculum","get_batch",{id:id},function(batch){
			addBatch(batch);
		});
		if (window.parent.reloadMenu) window.parent.reloadMenu();
	});
}
/** Add the given batch to the tree
 * @param {Batch} batch the batch to add
 */
function addBatch(batch) {
	var is_alumni = parseSQLDate(batch.end_date).getTime() < new Date().getTime();
	var parent;
	if (is_alumni) parent = window.curriculum_root.findTag("alumni");
	else parent = window.curriculum_root.findTag("current_students");
	new CurriculumTreeNode_Batch(parent, batch);
}
/** Open a popup to edit the given batch
 * @param {Batch} batch the batch to edit
 */
function editBatch(batch) {
	require("popup_window.js",function(){
		var popup = new popup_window("Edit Batch", theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.edit), "");
		popup.setContentFrame("/dynamic/curriculum/page/edit_batch?popup=yes&id="+batch.id+"&onsave=batchSaved");
		popup.show();
	});
}
/** Callback when a batch is edited and saved
 * @param {Number} id the ID of the batch
 */
function batchSaved(id) {
	service.json("curriculum","get_academic_calendar",{},function(cal) {
		academic_years = cal;
		var node = window.curriculum_root.findTag("batch"+id);
		node.item.parent.removeItem(node.item);
		service.json("curriculum","get_batch",{id:id},function(batch){
			addBatch(batch);
		});
		if (window.parent.reloadMenu) window.parent.reloadMenu();
	});
}
/** Ask the user to confirm, then remove from database and from the tree
 * @param {Batch} batch the batch to remove
 */
function removeBatch(batch) {
	window.top.datamodel.confirm_remove("StudentBatch", batch.id, function() {
		var node = window.curriculum_root.findTag("batch"+batch.id);
		node.item.parent.removeItem(node.item);
	});
}
