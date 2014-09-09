/**
 * Abstract class representing a node in the curriculum tree
 * @param {CurriculumTreeNode} parent parent node
 * @param {String} tag tag of the node
 * @param {Boolean} expanded indicates if the node should be expanded or collapsed
 */
function CurriculumTreeNode(parent, tag, expanded) {
	if (!parent) return; // only prototype inititalization
	this.parent = parent;
	this.item = createTreeItemSingleCell(this.getIcon(), this.createTitle(), expanded);
	this.tag = tag;
	_initCurriculumTreeNode(this);
}
/**
 * Initialize a node
 * @param {CurriculumTreeNode} node the node to be initialized
 */
function _initCurriculumTreeNode(node) {
	node.item.node = node;
	node.item.setOnSelect(function() { node._onselect(); });
	node.parent.item.addItem(node.item);
	window.to_cleanup.push(node);
	node.cleanup = function() {
		this.item.node = null;
		this.item = null;
		this.parent = null;
	};
}
CurriculumTreeNode.prototype = {
	/** {CurriculumTreeNode} parent node */
	parent: null,
	/** {String} tag */
	tag: "",
	/** {TreeItem} item of the tree widget */
	item: null,
	/** Called when the node is selected */
	_onselect: function() {
		// Footer
		var footer = document.getElementById('tree_footer_title');
		footer.removeAllChildren();
		var icon = this.getIcon();
		if (icon) {
			var img = document.createElement("IMG");
			img.src = icon;
			footer.appendChild(img);
		}
		var title = this.createTitle(true);
		if (typeof title == 'string')
			footer.appendChild(document.createTextNode(title));
		else
			footer.appendChild(title);
		footer = document.getElementById('tree_footer_content');
		footer.removeAllChildren();
		var info = this.createInfo();
		if (info) footer.appendChild(info);
		layout.changed(document.getElementById('tree_footer').parentNode);
		
		// make sure it is visible (scroll if necessary)
		if (this.item.tr)
			scrollToSee(this.item.tr);
		
		// Update frame
		nodeSelected(this);
	},
	/** Searches the given tag
	 * @param {String} tag the tag to search
	 * @returns {CurriculumTreeNode} the node having the searched tag, or null if not found
	 */
	findTag: function(tag) {
		if (this.tag == tag) return this;
		for (var i = 0; i < this.item.children.length; ++i) {
			var n = this.item.children[i].node.findTag(tag);
			if (n) return n;
		}
		return null;
	},
	/** Remove this node from the tree */
	remove: function() {
		this.parent.item.removeItem(this.item);
	},
	/** Get the icon URL
	 * @returns {String} the URL or null if no icon
	 */
	getIcon: function() { return null; },
	/** Create the node title
	 * @param {Boolean} editable indicates if the title may be editable
	 * @returns {Object} the title (string or html element)
	 */
	createTitle: function(editable) { return ""; },
	/** Create html containing information about this node
	 * @returns {Element} the html
	 */
	createInfo: function() {
	},
	/** Returns parameters that should be given in the URL of sub-pages
	 * @returns {Object} the parameters
	 */
	getURLParameters: function () {
		return {};
	}
};
