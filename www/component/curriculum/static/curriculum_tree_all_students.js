// #depends[curriculum_tree.js]

function CurriculumTreeNode_AllStudents(root) {
	CurriculumTreeNode.call(this, root, "all_students", true);
	this.item.cells[0].addStyle({fontWeight:"bold"});
	new CurriculumTreeNode_CurrentStudents(this);
	new CurriculumTreeNode_Alumni(this);
}
CurriculumTreeNode_AllStudents.prototype = new CurriculumTreeNode;
CurriculumTreeNode_AllStudents.prototype.constructor = CurriculumTreeNode_AllStudents;
CurriculumTreeNode_AllStudents.prototype.createTitle = function() { return "All Students"; };
CurriculumTreeNode_AllStudents.prototype.createInfo = function() {
	var div = document.createElement("DIV");
	div.innerHTML = batches.length+" batch"+(batches.length > 1 ? "es" : "");
	return div;
};
CurriculumTreeNode_AllStudents.prototype.getURLParameters = function() {
	return {}; // all students: nothing specified
};
