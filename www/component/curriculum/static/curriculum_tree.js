// Tree nodes
function CurriculumTreeNode(parent, tag, expanded) {
	if (!parent) return; // only prototype inititalization
	this.parent = parent;
	this.item = createTreeItemSingleCell(this.getIcon(), this.createTitle(), expanded);
	this.tag = tag;
	_initCurriculumTreeNode(this);
}
function _initCurriculumTreeNode(node) {
	node.item.node = node;
	node.item.setOnSelect(function() { node._onselect(); });
	node.parent.item.addItem(node.item);
}
CurriculumTreeNode.prototype = {
	parent: null,
	tag: "",
	item: null,
	_onselect: function() {
		// Footer
		var footer = document.getElementById('tree_footer_title');
		footer.innerHTML = "";
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
		footer.innerHTML = "";
		var info = this.createInfo();
		if (info) footer.appendChild(info);
		layout.invalidate(document.getElementById('tree_footer').parentNode);
		
		// Menu Links
		var menu_items = page_header.getMenuItems();
		var params = this.getURLParameters();
		for (var i = 0; i < menu_items.length; ++i) {
			var item = menu_items[i];
			var u = new URL(item.original_url);
			for (var name in params) u.params[name] = params[name];
			item.link.href = u.toString();
		}
		
		// Update frame
		var frame = document.getElementById('students_page');
		if (frame.src) {
			var url = new URL(getIFrameWindow(frame).location.href);
			var found = false;
			for (var i = 0; i < menu_items.length; ++i) {
				var u = new URL(menu_items[i].original_url);
				if (u.path == url.path) {
					getIFrameWindow(frame).location.href = menu_items[i].link.href;
					found = true;
					break;
				}
			}
			if (!found) // go to first item (students list)
				getIFrameWindow(frame).location.href = menu_items[0].link.href;
		}
		
		// Update application menu
		if (window.parent.resetAllMenus) {
			var items = window.parent.header.getMenuItems();
			for (var i = 0; i < items.length; ++i) {
				items[i].link.href = items[i].original_url+"#"+this.tag;
			}
		}
	},
	findTag: function(tag) {
		if (this.tag == tag) return this;
		for (var i = 0; i < this.item.children.length; ++i) {
			var n = this.item.children[i].node.findTag(tag);
			if (n) return n;
		}
		return null;
	},
	remove: function() {
		this.parent.item.removeItem(this.item);
	},
	getIcon: function() { return null; },
	createTitle: function(editable) { return ""; },
	createInfo: function() {
	},
	getURLParameters: function () {
		return {};
	}
};
