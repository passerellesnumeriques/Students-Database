if (typeof theme != 'undefined') theme.css("tree.css");

/** Defines a column in the tree
 * @param {String} title the title of the column
 */
function TreeColumn(title) {
	this.title = title;
}
/**
 * An item (row) in the tree
 * @param {Array} cells list of TreeCell
 * @param {Boolean} expanded indicates if the item will be expanded or collapsed at the beginning
 * @param {Function} onselect callback when the item is selected, or null if the item is not selectable
 */
function TreeItem(cells, expanded, onselect, children_on_demand) {
	if (typeof cells == 'string') cells = [new TreeCell(cells)];
	else if (typeof cells == 'object' && !(cells instanceof Array) && getObjectClassName(cells) != "Array")
		cells = [new TreeCell(cells)];
	if (!expanded) expanded = false;
	this.cells = cells;
	this.onselect = onselect;
	this.cells[0].element.className = "tree_cell tree_cell_main";
	if (onselect) this.cells[0].element.className += " tree_cell_selectable";
	for (var i = 1; i < this.cells.length; ++i) this.cells[i].element.className = "tree_cell";
	/** {Array} list of TreeItem: the children of this item */
	if (children_on_demand)
		this.children_on_demand = children_on_demand;
	else
		this.children = [];
	this.expanded = expanded;
	/** Add or change an HTML element that will be displayed at the right, taking the height of this item and all its children
	 * @param {Element} control the HTML element to add on the right
	 * @returns {Element} the TD containing the control
	 */
	this.setRightFillControl = function(control) {
		if (this.right_td) {
			this.right_td.removeAllChildren();
			this.right_td.appendChild(control);
			return this.right_td;
		}
		this.right_td = document.createElement("TD");
		this.right_td.appendChild(control);
		this.tr.appendChild(this.right_td);
		this.tree._refresh_heads();
		return this.right_td;
	};
	/** Set the callback when the item is selected, and marks the item as selectable
	 * @param {Function} onselect callback called when the item is selected
	 */
	this.setOnSelect = function(onselect) {
		this.onselect = onselect;
		this.cells[0].element.className = "tree_cell tree_cell_main tree_cell_selectable";
	};
	/** Append a child item
	 * @param {TreeItem} item the new child
	 */
	this.addItem = function(item) {
		item.parent = this;
		if (this.tree) this.tree._create_item(item);
		this.children.push(item);
		if (this.tree) this.tree._refresh_heads();
	};
	/** Insert a child item
	 * @param {TreeItem} item the child to insert
	 * @param {Number} index position where the child will be inserted
	 */
	this.insertItem = function(item, index) {
		if (index >= this.children.length) {
			this.addItem(item);
			return;
		}
		item.parent = this;
		if (this.tree) this.tree._create_item(item, index);
		this.children.splice(index,0,item);
		if (this.tree) this.tree._refresh_heads();
	};
	/** Remove a child item
	 * @param {TreeItem} item the child to remove
	 */
	this.removeItem = function(item) {
		this.children.remove(item);
		if (this.tree) {
			this.tree._removeItem(item);
			this.tree._refresh_heads();
		}
		item.parent = null;
	};
	/** Remove this item (equivalent to call parent.removeItem) */
	this.remove = function() {
		this.parent.removeItem(this);
	};
	/** Get the deep of this item
	 * @returns {Number} the deep of this item (0 for root level)
	 */
	this.getLevel = function() {
		var level = 0;
		var p = this.parent;
		while (p) { level++; p = p.parent; }
		return level;
	};
	/** Toggles between expanded and collapsed */
	this.toggleExpand = function() { if (this.expanded) this.collapse(); else this.expand(); };
	/** Expand this item */
	this.expand = function() {
		if (this.expanded) return;
		this.expanded = true;
		for (var i = 0; i < this.children.length; ++i)
			this._show(this.children[i]);
		this.tree._refresh_heads();
	};
	/** Make the given item and all its children visible
	 * @param {TreeItem} item the item to show
	 */
	this._show = function(item) {
		item.tr.style.visibility = 'visible';
		item.tr.style.position = 'static';
		if (item.expanded) {
			for (var i = 0; i < item.children.length; ++i)
				this._show(item.children[i]);
			this.tree._refresh_heads();
		}
	};
	/** Collapse this item */
	this.collapse = function() {
		if (!this.expanded) return;
		this.expanded = false;
		for (var i = 0; i < this.children.length; ++i)
			this._hide(this.children[i]);
		this.tree._refresh_heads();
	};
	/** Hides the given item, and all its children
	 * @param {TreeItem} item the item to hide
	 */
	this._hide = function(item) {
		item.tr.style.visibility = 'hidden';
		item.tr.style.position = 'absolute';
		item.tr.style.top = "-10000px";
		item.tr.style.left = "-10000px";
		if (item.children)
		for (var i = 0; i < item.children.length; ++i)
			this._hide(item.children[i]);
	};
	/** Ensure this item is visible (expand all necessary parents) */
	this.makeVisible = function() {
		if (!this.parent) return;
		this.parent.makeVisible();
		this.parent.expand();
	};
	/** Select this item */
	this.select = function() {
		this.tree.selectItem(this);
	};
	/** Add CSS on the TR containing this item
	 * @param {Object} style the styles to set
	 */
	this.addRowStyle = function(style) {
		for (var name in style) this.tr.style[name] = style[name];
	};
}
/** Cell of a TreeItem
 * @param {Element|String} html the content of the cell
 */
function TreeCell(html) {
	if (typeof html == 'string') {
		var div = document.createElement("DIV");
		div.style.display = "inline-block";
		div.innerHTML = html;
		html = div;
	}
	/** {Element} the content of the cell */
	this.element = html;
	/** {Boolean} indicates if this cell is in a header */
	this.is_header = false;
	
	/** Add CSS on the element of this cell
	 * @param {Object} style the styles to set
	 */
	this.addStyle = function(style) {
		for (var name in style) this.element.style[name] = style[name];
	};
	var t=this;
	/** Add a contextual menu to this cell
	 * @param {Function} menu_builder function populating a context_menu
	 */
	this.addContextMenu = function(menu_builder) {
		t.element.oncontextmenu = function(ev) {
			require("context_menu.js",function() {
				var menu = new context_menu();
				menu_builder(menu);
				if (menu.getItems().length > 0)
					menu.showBelowElement(t.element);
			});
			stopEventPropagation(ev);
			return false;
		};
		var img = document.createElement("IMG");
		img.src = theme.icons_10.arrow_down_context_menu;
		img.className = "button_verysoft";
		img.style.padding = "0px";
		img.style.verticalAlign = "bottom";
		img.style.marginLeft = "2px";
		img.onclick = function(ev) { t.element.oncontextmenu(ev); };
		t.element.appendChild(img);
	};
	/** Add an icon at the end of this cell, corresponding to an action on the item
	 * @param {String} icon URL of the icon
	 * @param {String} tooltip tooltip to display for this action icon, or null
	 * @param {Function} onclick callback called when the icon is clicked 
	 */
	this.addActionIcon = function(icon, tooltip, onclick) {
		var img = document.createElement("IMG");
		img.src = icon;
		img.className = "button_verysoft";
		img.style.padding = "0px";
		img.style.verticalAlign = "bottom";
		img.style.marginLeft = "2px";
		img.onclick = onclick;
		if (tooltip) img.title = tooltip;
		t.element.appendChild(img);
	};
}

/** Tree widget
 * @param {Element|String} container where the tree will be displayed
 */
function tree(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	this.columns = [];
	this.show_columns = false;
	this.items = [];
	this.children = this.items;
	var url = get_script_path("tree.js");
	container.className = "tree";
	var t=this;
	
	this.inner_cells_vertical_border = null;
	
	this._build_from_html = function() {
		// TODO
	};
	this.setShowColumn = function(show) {
		this.show_columns = show;
		this.tr_columns.style.visibility = show ? 'visible' : 'hidden';
		this.tr_columns.style.position = show ? 'static' : 'absolute';
		if (!show) {
			this.tr_columns.style.top = "-9000px";
			this.tr_columns.style.left = "-9000px";
		}
	};
	this.addColumn = function(col) {
		this.columns.push(col);
		this.tr_columns.appendChild(col.th = document.createElement("TH"));
		col.th.innerHTML = col.title;
	};
	this.addItem = function(item) {
		item.parent = null;
		this._create_item(item);
		this.items.push(item);
		this._refresh_heads();
	};
	this.insertItem = function(item, index) {
		if (typeof index == 'undefined' || index >= this.items.length) {
			this.addItem(item);
			return;
		}
		item.parent = null;
		this._create_item(item, index);
		this.items.splice(index,0,item);
		this._refresh_heads();
	};
	this.removeItem = function(item) {
		if (item.parent)
			item.parent.removeItem(item);
		else {
			this._removeItem(item);
			this._refresh_heads();
		}
	};
	this.clearItems = function() {
		while (this.tbody.childNodes.length > 0)
			this.tbody.removeChild(this.tbody.childNodes[0]);
		this.items = [];
	};
	this.addHeader = function(content, collapsable, index) {
		var container = document.createElement("DIV");
		container.style.display = "inline-block";
		container.appendChild(content);
		var cell = new TreeCell(container);
		var item = new TreeItem([cell],true);
		item.is_header = true;
		item.is_collapsable = collapsable;
		this.insertItem(item, index);
		return item;
	};
	this.addColumnsHeadersRow = function(parent) {
		var cells = [];
		for (var i = 0; i < this.columns.length; ++i) {
			var html = document.createElement("SPAN");
			html.appendChild(document.createTextNode(this.columns[i].title));
			html.style.paddingLeft = "2px";
			html.style.paddingRight = "2px";
			var cell = new TreeCell(html);
			cell.is_header = true;
			cells.push(cell);
		}
		var item = new TreeItem(cells);
		item.is_header = true;
		item.add_root_line = true;
		item.is_collapsable = false;
		if (!parent) parent = this;
		parent.addItem(item);
		return item;
	};
	this._removeItem = function(item) {
		try { this.tbody.removeChild(item.tr); } catch (e) {}
		var list = item.children;
		item.children = [];
		for (var i = 0; i < list.length; ++i)
			this._removeItem(list[i]);
		if (item == this._selected_item) {
			var p = item.parent;
			while (p && !p.onselect) p = p.parent;
			if (p) this.selectItem(p);
		}
	};
	this._selected_item = null;
	this.selectItem = function(item) {
		item.makeVisible();
		item.cells[0].element.onclick(createEvent("click",{}));
	};
	this.getSelectedItem = function() { return this._selected_item; };
	this._create_item = function(item, index) {
		item.tree = this;
		item.tr = document.createElement("TR");
		item.tr.item = item;
		var visible;
		if (!item.parent) visible = true;
		else {
			var p = item.parent;
			visible = true;
			while (p) {
				if (!p.expanded) { visible = false; break; }
				p = p.parent;
			}
		}
		item.tr.style.visibility = visible ? 'visible' : 'hidden';
		item.tr.style.position = visible ? 'static' : 'absolute';
		if (!visible) {
			item.tr.style.top = "-10000px";
			item.tr.style.left = "-10000px";
		}
		var td = document.createElement(item.cells[0].is_header ? "TH" : "TD"); item.tr.appendChild(td);
		item.cells[0].td = td;
		td.style.padding = "0px";
		td.style.whiteSpace = "nowrap";
		if (item.cells.length > 1 && this.inner_cells_vertical_border) td.style.borderRight = this.inner_cells_vertical_border; 
		td.appendChild(item.head = document.createElement("DIV"));
		item.head.style.display = 'inline-block';
		item.head.style.position = 'relative';
		item.head.style.paddingLeft = "2px";
		td.appendChild(item.cells[0].element);
		if (item.cells.length == 1 && this.columns.length > 1)
			td.colSpan = this.columns.length;
		for (var i = 1; i < item.cells.length; ++i) {
			td = document.createElement(item.cells[i].is_header ? "TH" : "TD");
			item.cells[i].td = td;
			item.tr.appendChild(td);
			td.style.padding = "0px";
			if (item.cells.length > i+1 && this.inner_cells_vertical_border) td.style.borderRight = this.inner_cells_vertical_border; 
			td.appendChild(item.cells[i].element);
		}
		if (!item.parent) {
			if (typeof index == 'undefined')
				this.tbody.appendChild(item.tr);
			else
				this.tbody.insertBefore(item.tr, this.tbody.childNodes[index+1]); // +1 to skip the tr_columns
		} else {
			if (item.parent.children.length == 0)
				this.tbody.insertBefore(item.tr, item.parent.tr.nextSibling);
			else if (item.parent.children.length == 1) {
				if (typeof index == 'undefined') {
					var next = item.parent.tr.nextSibling;
					while (next && next.item.getLevel() >= item.getLevel()) next = next.nextSibling;
					this.tbody.insertBefore(item.tr, next);
				} else
					this.tbody.insertBefore(item.tr, item.parent.children[index].tr);
			} else {
				if (typeof index == 'undefined') {
					var next = item.parent.tr.nextSibling;
					while (next && next.item.getLevel() >= item.getLevel()) next = next.nextSibling;
					this.tbody.insertBefore(item.tr, next);
				} else {
					this.tbody.insertBefore(item.tr, item.parent.children[index].tr);
				}
			}
		}
		if (item.onselect) {
			item.cells[0].element.onclick = function() {
				if (t._selected_item) {
					t._selected_item.cells[0].element.className = "tree_cell tree_cell_main tree_cell_selectable";
				}
				t._selected_item = item;
				item.cells[0].element.className = "tree_cell tree_cell_main tree_cell_selected";
				item.onselect();
			};
		}
		if (typeof item.children != 'undefined')
			for (var i = 0; i < item.children.length; ++i)
				this._create_item(item.children[i]);
	};
	this._refresh_heads_activated = false;
	this._refresh_heads = function() {
		if (this._refresh_heads_activated) return;
		this._refresh_heads_activated = true;
		setTimeout(function() {
			t._refresh_heads_activated = false;
			t._refresh_heads_();
		},10);
	};
	this._refresh_heads_ = function() {
		for (var i = 0; i < this.items.length; ++i)
			this._clean_heads(this.items[i]);
		for (var i = 0; i < t.items.length; ++i)
			t._compute_heights(t.items[i]);
		for (var i = 0; i < t.items.length; ++i)
			t._refresh_head(t.items[i], [], i > 0, i < t.items.length-1);
	};
	this._clean_heads = function(item) {
		item.head.style.height = "";
		while (item.head.childNodes.length > 0) item.head.removeChild(item.head.childNodes[0]);
		if (item.expanded)
			for (var i = 0; i < item.children.length; ++i)
				this._clean_heads(item.children[i]);
	};
	this._compute_heights = function(item) {
		item.head.computed_height = item.head.parentNode.clientHeight;
		if (item.expanded)
			for (var i = 0; i < item.children.length; ++i)
				this._compute_heights(item.children[i]);
	};
	this._refresh_head = function(item, parents, has_before, has_after) {
		var doit = true;
		if (item.head_already) {
			if (item.head_parents.length == parents.length) {
				var ok = true;
				for (var i = 0; i < parents.length; ++i)
					if (item.head_parents[i] != parents[i]) { ok = false; break; }
				if (ok && item.head_has_before == has_before && item.head_has_after == has_after)
					doit = false;
			}
		}
		if (doit) {
			item.has_already = true;
			item.head_parents = parents;
			item.head_has_before = has_before;
			item.head_has_after = has_after;
			
			if (item.right_td) {
				var level = item.getLevel();
				var next = item.tr.nextSibling;
				var count = 1;
				while (next != null && next.item.getLevel() > level) { next = next.nextSibling; count++; }
				item.right_td.rowSpan = count;
			}
			
			if (item.is_header) {
				if (item.is_collapsable) {
					item.head.style.width = "18px";
					var collapse_icon = document.createElement("IMG");
					collapse_icon.src = "/static/widgets/section/"+(item.expanded ? "collapse" : "expand")+".png";
					collapse_icon.style.cursor = "pointer";
					collapse_icon.onclick = function() {
						item.toggleExpand();
					};
					item.head.appendChild(collapse_icon);
				} else if (item.add_root_line) {
					item.head.parentNode.style.position = "relative";
					item.head.style.position = "absolute";
					item.head.style.left = "0px";
					item.head.style.width = "16px";
					item.head.style.height = item.head.computed_height+'px';
					var line = document.createElement("DIV");
					line.style.position = 'absolute';
					line.style.width = "1px";
					line.style.right = '7px';
					line.style.top = "0px";
					line.style.height = "100%";
					line.style.borderLeft = "1px solid #A0A0A0";
					item.head.appendChild(line);
				} else {
					item.head.style.width = "0px";
					item.head.style.visibility = "hidden";
					item.head.style.position = "absolute";
				}
			} else {
				var root = item;
				while (root.parent != null) root = root.parent;
				var p;
				if (root != item && root.is_header) {
					p = [];
					for (var i = 1; i < parents.length; ++i) p.push(parents[i]);
				} else
					p = parents;
				item.head.style.width = ((p.length+1)*16)+'px';
				item.head.style.verticalAlign = "bottom";
				item.head.style.height = item.head.computed_height+'px';
				// lines of parents
				for (var i = 0; i < p.length; ++i) {
					if (!p[i]) continue;
					var line = document.createElement("DIV");
					line.style.position = 'absolute';
					line.style.width = "1px";
					line.style.left = (i*16+9)+'px';
					line.style.top = '0px';
					line.style.height = '100%';
					line.style.borderLeft = "1px solid #A0A0A0";
					item.head.appendChild(line);
				}
				// vertical line
				if (has_before || has_after) {
					var line = document.createElement("DIV");
					line.style.position = 'absolute';
					line.style.width = "1px";
					line.style.right = '7px';
					if (has_after && has_before) {
						line.style.top = "0px";
						line.style.height = "100%";
					} else if (has_after) {
						line.style.bottom = "0px";
						line.style.height = "6px";
					} else {
						line.style.bottom = "5px";
						line.style.height = (getHeight(item.tr)-5)+"px";
					}
					line.style.borderLeft = "1px solid #A0A0A0";
					item.head.appendChild(line);
				}
				// horizontal line
				{
					var line = document.createElement("DIV");
					line.style.position = 'absolute';
					line.style.width = "7px";
					line.style.right = '1px';
					line.style.bottom = "4px"; 
					line.style.height = "1px";
					line.style.borderTop = "1px solid #A0A0A0";
					item.head.appendChild(line);
				}
				// box
				var img = document.createElement("IMG");
				img.style.position = 'absolute';
				img.style.right = '4px';
				img.style.bottom = '1px';
				img.style.cursor = 'pointer';
				img.item = item;
				if (typeof item.children == 'undefined' && item.children_on_demand) {
					img.src = url+"loading.gif";
					item.head.appendChild(img);
					item.children = [];
					item.children_on_demand(item, function() {
						img.src = url+(item.expanded ? "minus" : "plus")+".png";
						img.onclick = function() { this.item.toggleExpand(); };
					});
				} else
				if (item.children.length > 0) {
					img.src = url+(item.expanded ? "minus" : "plus")+".png";
					item.head.appendChild(img);
					img.onclick = function() { this.item.toggleExpand(); };
				}
			}
		}
		// children
		if (item.expanded && item.children.length > 0) {
			var children_parents = [];
			for (var i = 0; i < parents.length; ++i) children_parents.push(parents[i]);
			children_parents.push(has_after);
			for (var i = 0; i < item.children.length; ++i) {
				this._refresh_head(item.children[i], children_parents, true, i < item.children.length-1);
			}
		}
	};

	this._create = function() {
		container.appendChild(this.table = document.createElement("TABLE"));
		this.table.style.borderCollapse = "collapse";
		this.table.style.borderSpacing = "0px";
		this.table.appendChild(this.tbody = document.createElement("TBODY"));
		this.tbody.appendChild(this.tr_columns = document.createElement("TR"));
		this.setShowColumn(this.show_columns);
	};
	
	this._create();
	this._build_from_html();
	layout.addHandler(container, function() {
		t._refresh_heads();
	});
}

/**
 * Create a TreeItem with a single cell
 * @param {String} icon URL of the icon, or null
 * @param {String} text text of the item
 * @param {Boolean} expanded indicates if the item should be expanded or collapsed
 * @param {Function} onselect callback when the item is selected, or null if not selectable
 * @param {Function} context_menu_builder function populating a context menu, or null if no context menu
 * @param {Array} actions list of possible actions on the item (added as small icons at the end of the item)
 * @returns {TreeItem} the item built
 */
function createTreeItemSingleCell(icon, text, expanded, onselect, context_menu_builder, actions) {
	var div = document.createElement("DIV");
	div.style.display = "inline-block";
	if (icon) {
		var img = document.createElement("IMG");
		img.src = icon;
		img.style.marginRight = "2px";
		img.style.verticalAlign = "bottom";
		div.appendChild(img);
	}
	if (typeof text == 'string')
		div.appendChild(document.createTextNode(text));
	else
		div.appendChild(text);
	if (!actions) actions = [];
	if (context_menu_builder) {
		div.oncontextmenu = function(ev) {
			require("context_menu.js",function() {
				var menu = new context_menu();
				context_menu_builder(menu);
				if (menu.getItems().length > 0)
					menu.showBelowElement(div);
			});
			stopEventPropagation(ev);
			return false;
		};
		actions.push({icon:theme.icons_10.arrow_down_context_menu,tooltip:null,action:function(ev){div.oncontextmenu(ev);}});
	}
	for (var i = 0; i < actions.length; ++i) {
		var img = document.createElement("IMG");
		img.src = actions[i].icon;
		img.className = "button_verysoft";
		img.style.padding = "0px";
		img.style.verticalAlign = "bottom";
		img.style.marginLeft = "2px";
		if (actions[i].tooltip) img.title = actions[i].tooltip;
		img.action = actions[i].action;
		img.onclick = function(ev) { this.action(ev); stopEventPropagation(ev); return false; };
		div.appendChild(img);
	}
	return new TreeItem([new TreeCell(div)], expanded, onselect);
}
