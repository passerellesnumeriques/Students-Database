// #depends[curriculum_tree.js]

/**
 * Alumni: contains batches which already graduated
 * @param {CurriculumTreeNode_AllStudents} parent all students node
 */
function CurriculumTreeNode_Alumni(parent) {
	CurriculumTreeNode.call(this, parent, "alumni", true);
	this.item.cells[0].addStyle({fontWeight:"bold"});
	var batches = this.getBatches();
	for (var i = 0; i < batches.length; ++i) new CurriculumTreeNode_Batch(this, batches[i]);
}
CurriculumTreeNode_Alumni.prototype = new CurriculumTreeNode;
CurriculumTreeNode_Alumni.prototype.constructor = CurriculumTreeNode_Alumni;
CurriculumTreeNode_Alumni.prototype.createTitle = function() { return "Alumni"; };
/**
 * Return the list of Batch which are alumni
 * @returns {Array} list of Batch objects
 */
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
	return {batches:'alumni',group_type:group_type_id};
};
