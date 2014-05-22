// #depends[/static/widgets/grid/grid.js]

function CustomDataGridColumn(grid_column, data_getter, shown) {
	this.grid_column = grid_column;
	this.data_getter = data_getter;
	this.shown = shown;
}

function custom_data_grid(container, id_getter) {
	if (typeof container == 'string') container = document.getElementById(container);
	this.id_getter = id_getter;
	if (container) {
		this.container = container;
		this.grid = new grid(container);

		this.object_added = new Custom_Event();
		this.object_removed = new Custom_Event();
		this.selection_changed = new Custom_Event();
		this.list = [];
		this.columns = [];
		this._supported_drops = [];
		this._drag_supports = [];
		this._actions = [];

		var button = document.createElement("BUTTON");
		button.className = "flat small";
		button.innerHTML = "<img src='/static/data_model/table_column.png'/>";
		button.t = this;
		button.onclick = function() { this.t._menuColumns(this); return false; };
		this.col_actions = new GridColumn("#actions", button, null, null, "field_html");
		this.grid.addColumn(this.col_actions);
	}
}
custom_data_grid.prototype = {
	list: [],
	columns: [],
	addColumn: function(column) {
		this.columns.push(column);
		if (column.shown) { column.shown = false; this.showColumn(column.grid_column.id); }
	},
	getColumnById: function(col_id) {
		for (var i = 0; i < this.columns.length; ++i)
			if (this.columns[i].grid_column.id == col_id)
				return this.columns[i];
		return null;
	},
	showColumn: function(col_id) {
		var col = this.getColumnById(col_id);
		if (col.shown) return;
		col.shown = true;
		var index = 0;
		for (var i = 0; i < this.columns.length; ++i)
			if (this.columns[i].grid_column.id == col_id) break;
			else if (this.columns[i].shown) index++;
		this.grid.addColumn(col.grid_column, index);
	},
	hideColumn: function(col_id) {
		var col = this.getColumnById(col_id);
		if (!col.shown) return;
		col.shown = false;
		var index = this.grid.getColumnIndex(col.grid_column);
		this.grid.removeColumn(index);
	},
	addObject: function(obj) {
		this.list.push(obj);
		var id = this.id_getter(obj);
		var row_data = [];
		for (var i = 0; i < this.columns.length; ++i)
			row_data.push({col_id:this.columns[i].grid_column.id,data_id:id,data:this.columns[i].data_getter(obj)});
		var actions_container = document.createElement("DIV");
		for (var i = 0; i < this._actions.length; ++i)
			this._actions[i](actions_container, obj);
		row_data.push({col_id:"#actions",data_id:id,data:actions_container});
		var row = this.grid.addRow(id, row_data);
		if (this._drag_supports.length > 0) {
			var t=this;
			row.draggable = true;
			row.ondragstart = function(event) {
				for (var i = 0; i < t._drag_supports.length; ++i)
					event.dataTransfer.setData(t._drag_supports[i].data_type,t._drag_supports[i].data_getter(obj));
				event.dataTransfer.effectAllowed = "move";
				return true;
			};
		}
		this.object_added.fire(obj);
	},
	removeObject: function(obj) {
		this.list.remove(obj);
		this.grid.removeRow(this.grid.getRowFromID(this.id_getter(obj)));
		this.object_removed.fire(obj);
	},
	getList: function() {
		return this.list;
	},
	
	makeSelectable: function() {
		this.grid.setSelectable(true);
		var t=this;
		this.grid.onselect = function() {
			t.selection_changed.fire();
		};
	},
	getSelection: function() {
		var ids = this.grid.getSelectionByRowId();
		var list = [];
		for (var i = 0; i < this.list.length; ++i)
			if (ids.contains(this.id_getter(this.list[i])))
				list.push(this.list[i]);
		return list;
	},
	
	_supported_drops: [],
	addDropSupport: function(data_type, get_drop_effect, handler) {
		if (this._supported_drops.length == 0) {
			var t=this;
			var drag_handler = function(event) {
				var drop = null;
				for (var i = 0; i < t._supported_drops.length; ++i) {
					for (var j = 0; j < event.dataTransfer.types.length; ++j)
						if (event.dataTransfer.types[j] == t._supported_drops[i].data_type) {
							drop = t._supported_drops[i];
							break;
						}
					if (drop) break;
				}
				var data = event.dataTransfer.getData(drop.data_type);
				var effect = drop.get_drop_effect(data);
				if (effect != null) {
					t.container.style.outline = "2px dotted #808080";
					event.dataTransfer.dropEffect = effect;
					event.preventDefault();
					return false;
				}
			};
			t.container.ondragover = drag_handler; 
			t.container.ondragenter = drag_handler;
			t.container.ondragleave = function(event) {
				t.container.style.outline = "";
			};
			t.container.ondrop = function(event) {
				t.container.style.outline = "";
				var drop = null;
				for (var i = 0; i < t._supported_drops.length; ++i) {
					for (var j = 0; j < event.dataTransfer.types.length; ++j)
						if (event.dataTransfer.types[j] == t._supported_drops[i].data_type) {
							drop = t._supported_drops[i];
							break;
						}
					if (drop) break;
				}
				var data = event.dataTransfer.getData(drop.data_type);
				drop.handler(data);
				event.stopPropagation();
				return false;
			};
		}
		this._supported_drops.push({data_type:data_type,get_drop_effect:get_drop_effect,handler:handler});
	},

	_drag_supports: [],
	addDragSupport: function(data_type, data_getter) {
		this._drag_supports.push({data_type:data_type, data_getter:data_getter});
	},
	
	_actions: [],
	addAction: function(creator) {
		this._actions.push(creator);
	},
	
	_menuColumns: function(button) {
		var t=this;
		require("context_menu.js", function() {
			var menu = new context_menu();
			for (var i = 0; i < t.columns.length; ++i) {
				var div = document.createElement("DIV");
				var cb = document.createElement("INPUT"); cb.type = 'checkbox';
				cb.checked = t.columns[i].shown ? "checked" : "";
				cb.col = t.columns[i];
				cb.onchange = function() {
					if (this.checked)
						t.showColumn(this.col.grid_column.id);
					else
						t.hideColumn(this.col.grid_column.id);
				};
				div.appendChild(cb);
				div.appendChild(document.createTextNode(" "+t.columns[i].grid_column.title));
				menu.addItem(div, true);
			}
			menu.showBelowElement(button);
		});
	}
};