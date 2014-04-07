// #depends[curriculum_tree.js]

function CurriculumTreeNode_Alumni(parent) {
	CurriculumTreeNode.call(this, parent, "alumni", true);
	this.item.cells[0].addStyle({fontWeight:"bold"});
	var batches = this.getBatches();
	for (var i = 0; i < batches.length; ++i) new CurriculumTreeNode_Batch(this, batches[i]);
}
CurriculumTreeNode_Alumni.prototype = new CurriculumTreeNode;
CurriculumTreeNode_Alumni.prototype.constructor = CurriculumTreeNode_Alumni;
CurriculumTreeNode_Alumni.prototype.createTitle = function() { return "Alumni"; };
CurriculumTreeNode_Alumni.prototype.getBatches = function() {
	var list = [];
	for (var i = 0; i < batches.length; ++i) {
		var is_alumni = parseSQLDate(batches[i].end_date).getTime() < new Date().getTime();
		if (is_alumni) list.push(batches[i]);
	}
	return list;
};
CurriculumTreeNode_Alumni.prototype.createInfo = function() {
	var batches = this.getBatches();
	var div = document.createElement("DIV");
	div.innerHTML = batches.length+" batch"+(batches.length > 1 ? "es" : "");
	return div;
};
CurriculumTreeNode_Alumni.prototype.getURLParameters = function() {
	return {batches:'alumni'};
};
