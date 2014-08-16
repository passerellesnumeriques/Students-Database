// #depends[/static/widgets/grid/grid.js]

function CustomDataGridColumn(grid_column, data_getter, shown, data_getter_param, select_menu_name) {
	this.grid_column = grid_column;
	this.data_getter = data_getter;
	this.data_getter_param = data_getter_param;
	this.select_menu_name = select_menu_name;
	this.shown = shown;
}

function CustomDataGridColumnContainer(title, sub_columns, select_menu_name) {
	this.title = title;
	this.grid_column_container = null;
	this.sub_columns = sub_columns;
	this.select_menu_name = select_menu_name;
	this.shown = false;
	this.getColumnById = function(id) {
		for (var i = 0; i < this.sub_columns.length; ++i) {
			if (this.sub_columns[i] instanceof CustomDataGridColumn) {
				if (this.sub_columns[i].grid_column.id == id) return this.sub_columns[i];
			} else {
				var col = this.sub_columns[i].getColumnById(id);
				if (col) return col;
			}
		}
		return null;
	};
	this.getNbFinalColumnsShown = function() {
		if (!this.shown) return 0;
		var nb = 0;
		for (var i = 0; i < this.sub_columns.length; ++i) {
			if (this.sub_columns[i] instanceof CustomDataGridColumn) {
				if (this.sub_columns[i].shown) nb++;
			} else {
				nb += this.sub_columns[i].getNbFinalColumnsShown();
			}
		}
		return nb;
	};
	this.getFinalColumns = function() {
		var list = [];
		for (var i = 0; i < this.sub_columns.length; ++i) {
			if (this.sub_columns[i] instanceof CustomDataGridColumn) {
				list.push(this.sub_columns[i]);
			} else {
				var sub_list = this.sub_columns[i].getFinalColumns();
				for (var j = 0; j < sub_list.length; ++j) list.push(sub_list[j]);
			}
		}
		return list;
	};
}

function custom_data_grid(container, id_getter) {
	if (typeof container == 'string') container = document.getElementById(container);
	this.id_getter = id_getter;
	if (container) {
		this.container = container;
		this.grid = new grid(container);

		this.object_added = new Custom_Event();
		this.object_removed = new Custom_Event();
		this.column_shown = new Custom_Event();
		this.column_hidden = new Custom_Event();
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
		this.col_actions = new GridColumn("#actions", button, null, "right", "field_html");
		this.grid.addColumn(this.col_actions);
	}
}
custom_data_grid.prototype = {
	list: [],
	/** List of CustomDataGridColumn, or CustomDataGridColumnContainer */
	columns: [],
	addColumn: function(column) {
		this.columns.push(column);
		if (column.shown) { column.shown = false; this.showColumn(column.grid_column.id); }
	},
	getColumnById: function(col_id) {
		for (var i = 0; i < this.columns.length; ++i) {
			if (this.columns[i] instanceof CustomDataGridColumn) {
				if (this.columns[i].grid_column.id == col_id)
					return this.columns[i];
			} else {
				var col = this.columns[i].getColumnById(col_id);
				if (col) return col;
			}
		}
		return null;
	},
	getAllFinalColumns: function() {
		var list = [];
		for (var i = 0; i < this.columns.length; ++i) {
			if (this.columns[i] instanceof CustomDataGridColumn)
				list.push(this.columns[i]);
			else {
				var sub_list = this.columns[i].getFinalColumns();
				for (var j = 0; j < sub_list.length; ++j) list.push(sub_list[j]);
			}
		}
		return list;
	},
	showColumn: function(col_id) {
		var col = this.getColumnById(col_id);
		if (col.shown) return;
		col.shown = true;
		var index = 0;
		for (var i = 0; i < this.columns.length; ++i) {
			if (this.columns[i] instanceof CustomDataGridColumn) {
				if (this.columns[i].grid_column.id == col_id) {
					this.grid.addColumn(col.grid_column, index);
					break;
				} else if (this.columns[i].shown) index++;
			} else {
				if (this.columns[i].getColumnById(col_id) != null) {
					// contains the column
					this._showColumnInContainer(null, this.columns[i], col_id, index);
					break;
				} else {
					// does not contain the column
					index += this.columns[i].getNbFinalColumnsShown();
				}
			}
		}
		this.column_shown.fire(col);
	},
	_showColumnInContainer: function(parent_container, container, col_id, index) {
		if (container.grid_column_container && container.grid_column_container.th.parentNode == null) {
			container.grid_column_container = null;
			container.shown = false;
		}
		var index_in_container = 0;
		for (var i = 0; i < container.sub_columns.length; ++i) {
			if (container.sub_columns[i] instanceof CustomDataGridColumn) {
				// final column
				if (container.sub_columns[i].grid_column.id == col_id) {
					// found the one to show
					if (!container.shown) {
						// first one: create the container with the column
						container.grid_column_container = new GridColumnContainer(container.title, [container.sub_columns[i].grid_column]);
						container.shown = true;
						if (!parent_container)
							this.grid.addColumnContainer(container.grid_column_container, index);
						return;
					} else {
						// already shown
						container.grid_column_container.addSubColumn(container.sub_columns[i].grid_column, index_in_container);
						return;
					}
				} else {
					// not this one
					if (container.sub_columns[i].shown) index_in_container++;
				}
			} else {
				// child is a container
				if (container.sub_columns[i].getColumnById(col_id) != null) {
					// it contains it
					this._showColumnInContainer(container, container.sub_columns[i], col_id, index+index_in_container);
					if (!container.shown) {
						container.shown = true;
						container.grid_column_container = new GridColumnContainer(container.title, [container.sub_columns[i].grid_column_container]);
						if (!parent_container)
							this.grid.addColumnContainer(container.grid_column_container, index);
					}
					return;
				}
				if (container.shown)
					index_in_container += container.getNbFinalColumnsShown();
			}
		}
	},
	hideColumn: function(col_id) {
		var col = this.getColumnById(col_id);
		if (!col.shown) return;
		col.shown = false;
		var index = this.grid.getColumnIndex(col.grid_column);
		this.grid.removeColumn(index);
		this.column_hidden.fire(col);
	},
	addColumnContainer: function(column_container, index) {
		if (typeof index == 'undefined' || index >= this.columns.length)
			this.columns.push(column_container);
		else
			this.columns.splice(index, 0, column_container);
		var list = column_container.getFinalColumns();
		for (var i = 0; i < list.length; ++i)
			if (!list[i].shown) { list.splice(i,1); i--; } else list[i].shown = false;
		for (var i = 0; i < list.length; ++i)
			this.showColumn(list[i].grid_column.id);
	},
	addColumnInContainer: function(container, column, index) {
		if (typeof index == 'undefined' || index >= container.sub_columns.length)
			container.sub_columns.push(column);
		else
			container.sub_columns.splice(index,0,column);
		if (column.shown) {
			column.shown = false;
			this.showColumn(column.grid_column.id);
		}
	},
	removeColumn: function(col_id) {
		this.hideColumn(col_id);
		for (var i = 0; i < this.columns.length; ++i) {
			if (this.columns[i] instanceof CustomDataGridColumn) {
				if (this.columns[i].grid_column.id == col_id) {
					this.columns.splice(i,1);
					return;
				}
			} else {
				if (this.columns[i].getColumnById(col_id) != null) {
					this.removeColumnFromContainer(col_id, this.columns[i]);
					return;
				}
			}
		}
	},
	removeColumnFromContainer: function(col_id, container) {
		this.hideColumn(col_id);
		for (var i = 0; i < container.sub_columns.length; ++i) {
			if (container.sub_columns[i] instanceof CustomDataGridColumn) {
				if (container.sub_columns[i].grid_column.id == col_id) {
					container.sub_columns.splice(i,1);
					if (container.sub_columns.length == 0 && container.shown) {
						container.shown = false;
						// automatically removed by the grid
					}
					return;
				}
			} else {
				if (container.sub_columns[i].getColumnById(col_id) != null) {
					this.removeColumnFromContainer(col_id, container.sub_columns[i]);
					return;
				}
			}
		}
	},
	removeColumnContainer: function(container) {
		// to remove a container, we need to remove all its content
		for (var i = 0; i < container.sub_columns.length; ++i) {
			if (container.sub_columns[i] instanceof CustomDataGridColumn) {
				this.removeColumnFromContainer(container.sub_columns[i].grid_column.id, container);
				i--;
				continue;
			}
			removeColumnContainer(container.sub_columns[i]);
		}
		// then remove it
		for (var i = 0; i < this.columns.length; ++i) {
			if (this.columns[i] == container) {
				this.columns.splice(i,1);
				return;
			}
			if (this.columns[i] instanceof CustomDataGridColumnContainer) {
				if (this._removeFromContainer(container, this.columns[i]))
					return;
			}
		}
	},
	_removeFromContainer: function(col, container) {
		for (var i = 0; i < container.sub_columns.length; ++i) {
			if (container.sub_columns[i] == col) {
				container.sub_columns.splice(i,1);
				return true;
			}
			if (container.sub_columns[i] instanceof CustomDataGridColumnContainer) {
				if (container.sub__removeFromContainer(col, container.sub_columns[i]))
					return true;
			}
		}
		return false;
	},
	addObject: function(obj) {
		this.list.push(obj);
		var id = this.id_getter(obj);
		var row_data = [];
		var columns = this.getAllFinalColumns();
		for (var i = 0; i < columns.length; ++i)
			row_data.push({col_id:columns[i].grid_column.id,data_id:id,data:columns[i].data_getter(obj, columns[i].data_getter_param)});
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
					event.dataTransfer.setData(t._drag_supports[i].data_type,t._drag_supports[i].data_getter(obj,t._drag_supports[i].data_getter_param));
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
	
	makeSelectable: function(unique) {
		this.grid.setSelectable(true, unique);
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
	addDragSupport: function(data_type, data_getter, data_getter_param) {
		this._drag_supports.push({data_type:data_type, data_getter:data_getter, data_getter_param:data_getter_param});
	},
	
	_actions: [],
	addAction: function(creator) {
		this._actions.push(creator);
	},
	
	_menuColumns: function(button) {
		var t=this;
		require("context_menu.js", function() {
			var menu = new context_menu();
			t._fillMenuColumns(menu, t.columns, 0);
			menu.showBelowElement(button);
		});
	},
	_fillMenuColumns: function(menu, columns, padding) {
		for (var i = 0; i < columns.length; ++i) {
			var div = document.createElement("DIV");
			div.style.paddingLeft = padding+"px";
			if (columns[i] instanceof CustomDataGridColumn) {
				var cb = document.createElement("INPUT"); cb.type = 'checkbox';
				cb.checked = columns[i].shown ? "checked" : "";
				cb.col = columns[i];
				cb.g = this;
				cb.onchange = function() {
					if (this.checked)
						this.g.showColumn(this.col.grid_column.id);
					else
						this.g.hideColumn(this.col.grid_column.id);
				};
				div.appendChild(cb);
				div.appendChild(document.createTextNode(" "+(columns[i].select_menu_name ? columns[i].select_menu_name : columns[i].grid_column.title)));
			} else {
				div.appendChild(document.createTextNode(columns[i].select_menu_name ? columns[i].select_menu_name : columns[i].title));
				div.className = "context_menu_title";
			}
			menu.addItem(div, true);
			if (columns[i] instanceof CustomDataGridColumnContainer) {
				this._fillMenuColumns(menu, columns[i].sub_columns, padding+20);
			}
		}
	}
};