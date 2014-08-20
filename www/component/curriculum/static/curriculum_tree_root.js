// #depends[curriculum_tree.js]

/**
 * Root node of the tree
 */
function CurriculumTreeNode_Root() {
	this.item = tr;
	this.item.node = this;
	new CurriculumTreeNode_AllStudents(this);
	window.to_cleanup.push(this);
	this.cleanup = function() {
		this.item.node = null;
		this.item = null;
		this.parent = null;
	};
}
CurriculumTreeNode_Root.prototype = new CurriculumTreeNode;
CurriculumTreeNode_Root.prototype.constructor = CurriculumTreeNode_Root;
