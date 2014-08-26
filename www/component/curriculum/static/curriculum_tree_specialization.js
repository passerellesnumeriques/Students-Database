// #depends[curriculum_tree.js]

/**
 * Specialization in a batch period
 * @param {CurriculumTreeNode_Period} parent batch period
 * @param {Specialization} spe specialization information
 */
function CurriculumTreeNode_Specialization(parent, spe) {
	this.spe = spe;
	CurriculumTreeNode.call(this, parent, "period"+parent.period.id+"_specialization"+spe.id, true);
	for (var i = 0; i < parent.period.classes.length; ++i)
		if (parent.period.classes[i].spe_id == spe.id)
			new CurriculumTreeNode_Class(this, parent.period.classes[i]);
}
CurriculumTreeNode_Specialization.prototype = new CurriculumTreeNode;
CurriculumTreeNode_Specialization.prototype.constructor = CurriculumTreeNode_Specialization;
CurriculumTreeNode_Specialization.prototype.getIcon = function() { return "/static/curriculum/curriculum_16.png"; };
CurriculumTreeNode_Specialization.prototype.createTitle = function(editable) {
	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode("Specialization "));
	var spe = this.spe;
	window.top.datamodel.create_cell(window, "Specialization", null, "name", spe.id, spe.name, "field_text", {can_be_null:false,max_length:100}, editable && can_edit_batches, span, function(value) { spe.name = value; });
	return span;
};
CurriculumTreeNode_Specialization.prototype.createInfo = function() {
	var div = document.createElement("DIV");
	if (window.can_edit_batches) {
		var button = document.createElement("BUTTON");
		button.className = "action";
		button.innerHTML = "<img src='"+theme.icons_16.edit+"'/> Edit";
		button.title = "Edit batch, periods and specializations";
		button.node = this;
		button.onclick = function() {
			editBatch(this.node.parent.parent.batch);
		};
		div.appendChild(button);
		button = document.createElement("BUTTON");
		button.className = "action green";
		button.innerHTML = "<img src='"+theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add)+"'/> New Class";
		button.title = "Create a new class";
		button.node = this;
		button.onclick = function() {
			newClass(this.node.parent, this.node.spe);
		};
		div.appendChild(button);
	}
	return div;
};
CurriculumTreeNode_Specialization.prototype.getURLParameters = function() {
	return {batch:this.parent.parent.batch.id,period:this.parent.period.id,specialization:this.spe.id};
};
