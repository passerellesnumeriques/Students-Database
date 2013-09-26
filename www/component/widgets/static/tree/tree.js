function TreeColumn(title) {
	this.title = title;
}
function TreeItem(cells) {
	this.cells = cells;
	this.children = [];
	this.expanded = false;
	this.addItem = function(item) {
		this.children.push(item);
		if (this.tree) {
			this.tree._create_item(this, item);
			this.tree._refresh_heads(this.parent);
			this.tree._refresh_heads(this);
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
		this.tree._refresh_heads(this.parent);
		this.tree._refresh_heads(this);
	};
	this._show = function(item) {
		item.tr.style.visibility = 'visible';
		item.tr.style.position = 'static';
		if (item.expanded) {
			for (var i = 0; i < item.children.length; ++i)
				this._show(item.children[i]);
			this.tree._refresh_heads(item);
		}
	};
	this.collapse = function() {
		if (!this.expanded) return;
		this.expanded = false;
		for (var i = 0; i < this.children.length; ++i)
			this._hide(this.children[i]);
		this.tree._refresh_heads(this.parent);
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
	
	this._build_from_html = function() {
		// TODO
	};
	this.setShowColumn = function(show) {
		this.show_columns = show;
		this.tr_columns.style.visibility = show ? 'visible' : 'hidden';
		this.tr_columns.style.position = show ? 'static' : 'absolute';
	};
	this.addColumn = function(col) {
		this.tr_columns.appendChild(col.th = document.createElement("TH"));
		col.th.innerHTML = col.title;
	};
	this.addItem = function(item) {
		this.items.push(item);
		this._create_item(null, item);
		this._refresh_heads();
	};
	this.removeItem = function(item) {
		this._removeItem(item);
		this._refresh_heads(item.parent);
		if (item.parent) this._refresh_heads(item.parent.parent);
	};
	this.clearItems = function() {
		while (this.tbody.childNodes.length > 0)
			this.tbody.removeChild(this.tbody.childNodes[0]);
		this.items = [];
	};
	this._removeItem = function(item) {
		this.tbody.removeChild(item.tr);
		if (item.parent == null) this.items.remove(item); else item.parent.children.remove(item);
		while (item.children.length > 0)
			this.removeItem(item.children[0]);
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
		td.appendChild(item.head = document.createElement("SPAN"));
		item.head.style.paddingLeft = "2px";
		td.appendChild(item.cells[0].container = document.createElement("SPAN"));
		if (typeof item.cells[0].html == 'string')
			item.cells[0].container.innerHTML = item.cells[0].html;
		else
			item.cells[0].container.appendChild(item.cells[0].html);
		for (var i = 1; i < item.cells.length; ++i) {
			tr.appendChild(item.cells[i].container = document.createElement("TD"));
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
	this._refresh_heads = function(parent) {
		var items = parent ? parent.children : this.items;
		var level = 0;
		var p = parent;
		while (p) { level++; p = p.parent; }
		for (var i = 0; i < items.length; ++i) {
			var item = items[i];
			var url = get_script_path("tree.js")+"tree";
			if (item.parent)
				url += "_child";
			else
				url += "_root";
			if (item.children.length > 0) {
				if (item.expanded)
					url += "_open";
				else
					url += "_close";
			} else
				url += "_nochild";
			if (i == items.length-1)
				url += "_last";
			else
				url += "_continue";
			url += ".gif";
			while (item.head.childNodes.length > 0) item.head.removeChild(item.head.childNodes[0]);
			var img = document.createElement("IMG");
			img.src = url;
			img.style.verticalAlign = "middle";
			img.style.cursor = "pointer";
			img.item = item;
			img.onclick = function() { this.item.toggle_expand(); };
			item.head.appendChild(img);
			var base_url = get_script_path("tree.js")+"tree_";
			var p = parent;
			while (p) {
				var list = p.parent ? p.parent.children : this.items;
				var index = list.indexOf(p);
				var img = document.createElement("IMG");
				img.style.verticalAlign = "middle";
				img.style.cursor = "pointer";
				img.src = base_url + (index == list.length-1 ? "empty" : "continue")+".gif";
				item.head.insertBefore(img, item.head.childNodes[0]);
				p = p.parent;
			}
		}
	};

	this._create = function() {
		container.appendChild(this.table = document.createElement("TABLE"));
		this.table.style.borderCollapse = "collapse";
		this.table.appendChild(this.tbody = document.createElement("TBODY"));
		this.tbody.appendChild(this.tr_columns = document.createElement("TR"));
		this.setShowColumn(this.show_columns);
	};
	
	this._create();
	this._build_from_html();
}
