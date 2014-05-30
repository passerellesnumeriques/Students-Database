// #depends[curriculum_tree.js]

/**
 * Current students: batches which did not graduate yet
 * @param {CurriculumTreeNode_AllStudents} parent parent node
 */
function CurriculumTreeNode_CurrentStudents(parent) {
	CurriculumTreeNode.call(this, parent, "current_students", true);
	this.item.cells[0].addStyle({fontWeight:"bold"});
	var batches = this.getBatches();
	for (var i = 0; i < batches.length; ++i) new CurriculumTreeNode_Batch(this, batches[i]);
}
CurriculumTreeNode_CurrentStudents.prototype = new CurriculumTreeNode;
CurriculumTreeNode_CurrentStudents.prototype.constructor = CurriculumTreeNode_CurrentStudents;
CurriculumTreeNode_CurrentStudents.prototype.createTitle = function() { return "Current Students"; };
/**
 * Return the list of Batch which are not yet graduated
 * @returns {Array} list of Batch objects
 */
CurriculumTreeNode_CurrentStudents.prototype.getBatches = function() {
	var list = [];
	for (var i = 0; i < batches.length; ++i) {
		var is_alumni = parseSQLDate(batches[i].end_date).getTime() < new Date().getTime();
		if (!is_alumni) list.push(batches[i]);
	}
	return list;
};
CurriculumTreeNode_CurrentStudents.prototype.createInfo = function() {
	var batches = this.getBatches();
	var div = document.createElement("DIV");
	div.innerHTML = batches.length+" batch"+(batches.length > 1 ? "es" : "");
	return div;
};
CurriculumTreeNode_CurrentStudents.prototype.getURLParameters = function() {
	return {batches:'current'};
};
