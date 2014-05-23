// #depends[curriculum_tree.js]

/**
 * Root node of the tree
 */
function CurriculumTreeNode_Root() {
	this.item = tr;
	this.item.node = this;
	new CurriculumTreeNode_AllStudents(this);
}
CurriculumTreeNode_Root.prototype = new CurriculumTreeNode;
CurriculumTreeNode_Root.prototype.constructor = CurriculumTreeNode_Root;
