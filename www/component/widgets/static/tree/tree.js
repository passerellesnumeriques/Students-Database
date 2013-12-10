function TreeColumn(title) {
	this.title = title;
}
function TreeItem(cells, expanded) {
	if (typeof cells == 'string') cells = [new TreeCell(cells)];
	else if (typeof cells == 'object' && !(cells instanceof Array) && cells.constructor.name != "Array")
		cells = [new TreeCell(cells)];
	if (!expanded) expanded = false;
	this.cells = cells;
	this.children = [];
	this.expanded = expanded;
	this.addItem = function(item) {
		item.parent_item = this;
		this.children.push(item);
		if (this.tree) {
			this.tree._create_item(this, item);
			this.tree._refresh_heads();
		}
	};
	this.removeItem = function(item) {
		item.parent_item = null;
		this.children.remove(item);
		if (this.tree) {
			this.tree._removeItem(item);
			this.tree._refresh_heads();
		}
	};
	this.get_level = function() {
		var level = 0;
		var p = this.parent;
		while (p) { level++; p = p.parent; }
		return level;
	};
	this.toggle_expand = function() { if (this.expanded) this.collapse(); else this.expand(); };
	this.expand = function() {
		if (this.expanded) return;
		this.expanded = true;
		for (var i = 0; i < this.children.length; ++i)
			this._show(this.children[i]);
		this.tree._refresh_heads();
	};
	this._show = function(item) {
		item.tr.style.visibility = 'visible';
		item.tr.style.position = 'static';
		if (item.expanded) {
			for (var i = 0; i < item.children.length; ++i)
				this._show(item.children[i]);
			this.tree._refresh_heads();
		}
	};
	this.collapse = function() {
		if (!this.expanded) return;
		this.expanded = false;
		for (var i = 0; i < this.children.length; ++i)
			this._hide(this.children[i]);
		this.tree._refresh_heads();
	};
	this._hide = function(item) {
		item.tr.style.visibility = 'hidden';
		item.tr.style.position = 'absolute';
		for (var i = 0; i < item.children.length; ++i)
			this._hide(item.children[i]);
	};
}
function TreeCell(html) {
	this.html = html;
}

function tree(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	this.columns = [];
	this.show_columns = false;
	this.items = [];
	var url = get_script_path("tree.js");
	
	this._build_from_html = function() {
		// TODO
	};
	this.setShowColumn = function(show) {
		this.show_columns = show;
		this.tr_columns.style.visibility = show ? 'visible' : 'hidden';
		this.tr_columns.style.position = show ? 'static' : 'absolute';
	};
	this.addColumn = function(col) {
		this.columns.push(col);
		this.tr_columns.appendChild(col.th = document.createElement("TH"));
		col.th.innerHTML = col.title;
	};
	this.addItem = function(item) {
		this.items.push(item);
		this._create_item(null, item);
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
	this._removeItem = function(item) {
		try { this.tbody.removeChild(item.tr); } catch (e) {}
		while (item.children.length > 0)
			this._removeItem(item.children[0]);
	};
	this._create_item = function(parent, item) {
		item.tree = this;
		item.parent = parent;
		item.tr = document.createElement("TR");
		item.tr.item = item;
		var visible;
		if (!parent) visible = true;
		else {
			var p = parent;
			visible = true;
			while (p) {
				if (!p.expanded) { visible = false; break; }
				p = p.parent;
			}
		}
		item.tr.style.visibility = visible ? 'visible' : 'hidden';
		item.tr.style.position = visible ? 'static' : 'absolute';
		var td = document.createElement("TD"); item.tr.appendChild(td);
		td.style.padding = "0px";
		td.appendChild(item.head = document.createElement("DIV"));
		item.head.style.display = 'inline-block';
		item.head.style.position = 'relative';
		item.head.style.paddingLeft = "2px";
		td.appendChild(item.cells[0].container = document.createElement("SPAN"));
		if (typeof item.cells[0].html == 'string')
			item.cells[0].container.innerHTML = item.cells[0].html;
		else
			item.cells[0].container.appendChild(item.cells[0].html);
		if (item.cells.length == 1 && this.columns.length > 1)
			td.colSpan = this.columns.length;
		for (var i = 1; i < item.cells.length; ++i) {
			item.tr.appendChild(item.cells[i].container = document.createElement("TD"));
			item.cells[i].container.style.padding = "0px";
			if (typeof item.cells[i].html == 'string')
				item.cells[i].container.innerHTML = item.cells[i].html;
			else
				item.cells[i].container.appendChild(item.cells[i].html);
		}
		if (!parent)
			this.tbody.appendChild(item.tr);
		else {
			if (parent.children.length == 1)
				this.tbody.insertBefore(item.tr, parent.tr.nextSibling);
			else {
				var next = parent.tr.nextSibling;
				while (next && next.item.get_level() >= item.get_level()) next = next.nextSibling;
				this.tbody.insertBefore(item.tr, next);
			}
		}
		for (var i = 0; i < item.children.length; ++i)
			this._create_item(item, item.children[i]);
	};
	this._refresh_heads_activated = false;
	this._refresh_heads = function() {
		if (this._refresh_heads_activated) return;
		this._refresh_heads_activated = true;
		var t=this;
		setTimeout(function() {
			t._refresh_heads_activated = false;
			t._refresh_heads_();
		},10);
	};
	this._refresh_heads_ = function() {
		for (var i = 0; i < this.items.length; ++i)
			this._clean_heads(this.items[i]);
		for (var i = 0; i < this.items.length; ++i)
			this._compute_heights(this.items[i]);
		for (var i = 0; i < this.items.length; ++i)
			this._refresh_head(this.items[i], [], i > 0, i < this.items.length-1);
	};
	this._clean_heads = function(item) {
		item.head.style.height = "";
		while (item.head.childNodes.length > 0) item.head.removeChild(item.head.childNodes[0]);
		for (var i = 0; i < item.children.length; ++i)
			this._clean_heads(item.children[i]);
	};
	this._compute_heights = function(item) {
		item.head.computed_height = item.head.parentNode.clientHeight;
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
			
			item.head.style.width = ((parents.length+1)*16)+'px';
			item.head.style.verticalAlign = "bottom";
			item.head.style.height = item.head.computed_height+'px';
			// lines of parents
			for (var i = 0; i < parents.length; ++i) {
				if (!parents[i]) continue;
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
			if (item.children.length > 0) {
				var img = document.createElement("IMG");
				img.src = url+(item.expanded ? "minus" : "plus")+".png";
				img.style.position = 'absolute';
				img.style.right = '4px';
				img.style.bottom = '1px';
				item.head.appendChild(img);
				img.style.cursor = 'pointer';
				img.item = item;
				img.onclick = function() { this.item.toggle_expand(); };
			}
		}
		// children
		var children_parents = [];
		for (var i = 0; i < parents.length; ++i) children_parents.push(parents[i]);
		children_parents.push(has_after);
		for (var i = 0; i < item.children.length; ++i) {
			this._refresh_head(item.children[i], children_parents, true, i < item.children.length-1);
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
}
