// #depends[/static/widgets/grid/grid.js]

/**
 * Define a column in a custom_data_grid
 * @param {GridColumn} grid_column the column to put in the grid
 * @param {Function} data_getter function which can extract the data of this column from the object of a row (the object is given as parameter)
 * @param {Boolean} shown if true, it will be shown at the beginning
 * @param {Object} data_getter_param given as a second paramter of data_getter
 * @param {String} select_menu_name if specified, when the user select the columns to display, this string will be displayed instead of the column title
 * @param {Boolean} always_shown if true, the user won't be able to hide this column
 */
function CustomDataGridColumn(grid_column, data_getter, shown, data_getter_param, select_menu_name, always_shown) {
	this.grid_column = grid_column;
	this.data_getter = data_getter;
	this.data_getter_param = data_getter_param;
	this.select_menu_name = select_menu_name;
	if (select_menu_name)
		this.grid_column.text_title = select_menu_name;
	this.shown = always_shown ? true : shown;
	this.always_shown = always_shown;
}

/**
 * Define a column container in a custom_data_grid
 * @param {String} title the title of the container
 * @param {Array} sub_columns list of CustomDataGridColumn
 * @param {String} select_menu_name if specified, when the user select the columns to display, this string will be displayed instead of the column title
 */
function CustomDataGridColumnContainer(title, sub_columns, select_menu_name) {
	this.title = title;
	/** {GridColumnContainer} Corresponding container for the grid */
	this.grid_column_container = null;
	this.sub_columns = sub_columns;
	this.select_menu_name = select_menu_name;
	/** Indicates if it is currently displayed */
	this.shown = false;
	/**
	 * Search the CustomDataGridColumn having the given id
	 * @param {String} id the column to search
	 */
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
	/**
	 * Get the number of CustomDataGridColumn currently shown from this container
	 * @returns {Number} number of columns
	 */
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
	/**
	 * Retrieve the list of CustomDataGridColumn in this container (the leaves)
	 * @returns {Array} the list of final columns
	 */
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

/**
 * A custom_data_grid is a kind of data_list, but without any dynamic requests to the backend.
 * In other words, we should have everything already available in JavaScript.
 * But it is similar in the way it provides an additional layer on top of a grid, making its usage
 * easier to display data, while taking advantages of all functionalities and flexibility provided by a grid.
 * It will create a grid, with the defined columns. Then, each row is represented by a JavaScript object.
 * Each column being able to extract its data from this object (through the data_getter given to each column).
 * Thus the custom_data_grid provides a grid but manipulating objects instead of a matrix of data.
 * Then it provides few additional functionalities:<ul>
 * <li>select the columns to show/hide</li>
 * <li>add/remove objects (each representing a row in the grid)</li>
 * <li>drag and drop support</li>
 * </ul>
 * @param {Element} container where to put this grid
 * @param {Function} id_getter function able to extract a unique ID from an object representing a row
 */
function custom_data_grid(container, id_getter) {
	if (typeof container == 'string') container = document.getElementById(container);
	this.id_getter = id_getter;
	if (container) {
		this.container = container;
		/** The grid */
		this.grid = new grid(container);

		/** Event fired when a new row is added */
		this.object_added = new Custom_Event();
		/** Event fired when a row is removed */
		this.object_removed = new Custom_Event();
		/** Event fired when a column is shown */
		this.column_shown = new Custom_Event();
		/** Event fired when a column is hidden */
		this.column_hidden = new Custom_Event();
		/** Event fired when the selected rows changed */
		this.selection_changed = new Custom_Event();
		this.list = [];
		this.columns = [];
		this._supported_drops = [];
		this._drag_supports = [];
		this._actions = [];
		/** Content of the header of the actions column */
		this.col_actions_title = document.createElement("DIV");
		/** Action column */
		this.col_actions = new GridColumn("#actions", this.col_actions_title, null, "right", "field_html");
		this.grid.addColumn(this.col_actions);
		
		this.setColumnsChooserInGrid();
	}
}
custom_data_grid.prototype = {
	/** List of objects, each representing a row */
	list: [],
	/** List of CustomDataGridColumn, or CustomDataGridColumnContainer */
	columns: [],
	/** Add a column
	 * @param {CustomDataGridColumn} column the column to add
	 * @param {Number} index optional, to insert the column at a specific index 
	 */
	addColumn: function(column, index) {
		if (typeof index == 'undefined' || index >= this.columns.length)
			this.columns.push(column);
		else
			this.columns.splice(index,0,column);
		if (column.shown) { column.shown = false; this.showColumn(column.grid_column.id); }
	},
	/** Retrieve a column from its id
	 * @param {String} col_id the id
	 * @returns {CustomDataGridColumn} the column, or null if not found
	 */
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
	/** Retrieve all final columns (leaves)
	 * @returns {Array} list of CustomDataGridColumn
	 */
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
	/** Display the given column
	 * @param {String} col_id the id of the column to display
	 */
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
		// put data
		var t=this;
		this.grid.onallrowsready(function() {
			for (var i = 0; i < t.list.length; ++i)
				t.grid.setCellData(t.id_getter(t.list[i]), col_id, col.data_getter(t.list[i], col.data_getter_param));
			t.column_shown.fire(col);
		});
	},
	/** Internal function to display a final column, that we know is in a container. If the container was not yet displayed, it will be displayed
	 * @param {CustomDataGridColumnContainer} parent_container the parent of the container if we have at least 2 levels of containers, or null
	 * @param {CustomDataGridColumnContainer} container the container
	 * @param {String} col_id the id of the final column
	 * @param {Number} index where to insert it in the grid
	 */
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
						if (container.select_menu_name)
							container.grid_column_container.text_title = container.select_menu_name;
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
					var sub_container_shown = container.sub_columns[i].grid_column_container;
					this._showColumnInContainer(container, container.sub_columns[i], col_id, index+index_in_container);
					if (!container.shown) {
						container.shown = true;
						container.grid_column_container = new GridColumnContainer(container.title, [container.sub_columns[i].grid_column_container]);
						if (!parent_container)
							this.grid.addColumnContainer(container.grid_column_container, index);
						else
							parent_container.grid_column_container.addSubColumn(container.grid_column_container);
					} else if (!sub_container_shown)
						container.grid_column_container.addSubColumn(container.sub_columns[i].grid_column_container);
					return;
				}
				if (container.shown)
					index_in_container += container.getNbFinalColumnsShown();
			}
		}
	},
	/** Hide the given column
	 * @param {String} col_id the id of the column to hide
	 */
	hideColumn: function(col_id) {
		var col = this.getColumnById(col_id);
		if (!col.shown) return;
		col.shown = false;
		var index = this.grid.getColumnIndex(col.grid_column);
		this.grid.removeColumn(index);
		for (var i = 0; i < this.columns.length; ++i)
			if (this.columns[i] instanceof CustomDataGridColumnContainer)
				if (this.columns[i].getColumnById(col_id) != null)
					if (this.columns[i].getNbFinalColumnsShown() == 0 && this.columns[i].shown)
						this.columns[i].shown = false;
		this.column_hidden.fire(col);
	},
	/** Add a container
	 * @param {CustomDataGridColumnContainer} column_container the container to add, which may already contain columns or not
	 * @param {Number} index if specified, the container will be inserted at the given index
	 */
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
	/** Add a column, or a sub-container, in an existing column container
	 * @param {CustomDataGridColumnContainer} container the container in which to add a column
	 * @param {CustomDataGridColumn|CustomDataGridColumnContainer} column what to add
	 * @param {Number} index if specified, it will be inserted at the given index inside the container
	 */
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
	/** Remove a column
	 * @param {String} col_id the id of the column to remove
	 */
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
	/** Remove a column which is inside a container
	 * @param {String} col_id the id of the column to remove
	 * @param {CustomDataGridColumnContainer} container the container containing the column to remove
	 */
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
	/** Remove a column container, and all its sub-columns
	 * @param {CustomDataGridColumnContainer} container the container to remove
	 */
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
	/** Internal function to remove a column inside a container. If the container becomes empty, it won't be displayed anymore.
	 * @param {CustomDataGridColumn|CustomDataGridColumnContainer} col the column or container to remove
	 * @param {CustomDataGridColumnContainer} container the container
	 * @returns {Boolean} true if we found col inside container
	 */
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
	/** Add a row
	 * @param {Object} obj object containing the data of the row
	 */
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
	/** Remove a row
	 * @param {Object} obj the object representing the row
	 */
	removeObject: function(obj) {
		this.list.remove(obj);
		this.grid.removeRow(this.grid.getRowFromID(this.id_getter(obj)));
		this.object_removed.fire(obj);
	},
	/** Returns the list of objects representing the rows
	 * @returns {Array} list of objects
	 */
	getList: function() {
		return this.list;
	},
	/** Make each row selectable
	 * @param {Boolean} unique if true, only one row can be selected (using radio buttons), else several rows can be selected (using checkboxes)
	 */
	makeSelectable: function(unique) {
		this.grid.setSelectable(true, unique);
		var t=this;
		this.grid.onselect = function() {
			t.selection_changed.fire();
		};
	},
	/** Return the list of objects corresponding to the currently selected rows
	 * @returns {Array} list of objects
	 */
	getSelection: function() {
		var ids = this.grid.getSelectionByRowId();
		var list = [];
		for (var i = 0; i < this.list.length; ++i)
			if (ids.contains(this.id_getter(this.list[i])))
				list.push(this.list[i]);
		return list;
	},
	/** Hide the column containing actions */
	hideActions: function() {
		var col = this.grid.getColumnById("#actions");
		if (col) col.hide(true);
	},
	/** Show the column containing actions */
	showActions: function() {
		var col = this.grid.getColumnById("#actions");
		if (col) col.hide(false);
	},
	refreshColumnData: function(column_id) {
		var col = this.getColumnById(column_id);
		for (var i = 0; i < this.list.length; ++i) {
			var row_id = this.id_getter(this.list[i]);
			var field = this.grid.getCellFieldById(row_id, column_id);
			if (field)
				field.setData(col.data_getter(this.list[i], col.data_getter_param));
		}
	},
	/** List of supported drops */
	_supported_drops: [],
	/** Add the capacity to accept dropped objects
	 * @param {String} data_type type of data we can drop, which must be set when starting the drag
	 * @param {Function} get_drop_effect function to give which drop effect to display according to the data, or null if the data is not accepted
	 * @param {Function} handler function called when something has been dropped 
	 */
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
	/** List of supported drags */
	_drag_supports: [],
	/** Add the capacity to drag a row from this grid
	 * @param {String} data_type data type to set in the drag event
	 * @param {Function} data_getter function providing the data to drag, given the object of the dragged row
	 * @param {Object} data_getter_param if specified, it will be given as second parameter of the data_getter function 
	 */
	addDragSupport: function(data_type, data_getter, data_getter_param) {
		this._drag_supports.push({data_type:data_type, data_getter:data_getter, data_getter_param:data_getter_param});
	},
	/** List of actions */
	_actions: [],
	/** Add a possible action on a row
	 * @param {Function} creator function to create the HTML element corresponding to the action
	 */
	addAction: function(creator) {
		this._actions.push(creator);
	},
	/**
	 * If called, we will display a button to choose the columns to show and hide, directly in the grid, in the header of the actions' column
	 */
	setColumnsChooserInGrid: function() {
		if (this.columns_chooser) {
			if (this.columns_chooser.parentNode == this.col_actions_title) return;
			if (this.columns_chooser.parentNode) this.columns_chooser.parentNode.removeChild(this.columns_chooser);
		}
		this.columns_chooser = document.createElement("BUTTON");
		this.columns_chooser.className = "flat small";
		this.columns_chooser.innerHTML = "<img src='/static/data_model/table_column.png'/>";
		var t=this;
		this.columns_chooser.onclick = function() { t._menuColumns(this); return false; };
		this.col_actions_title.appendChild(this.columns_chooser);
	},
	/**
	 * When the given button is clicked, we will display the list of columns we can show or hide. This allows to display this button anywhere in the page,
	 * at the opposite of setColumnsChooserInGrid which display it directly in the grid  
	 * @param {Element} button the button
	 */
	setColumnsChooserButton: function(button) {
		if (this.columns_chooser) {
			if (this.columns_chooser == button) return;
			if (this.columns_chooser.parentNode) this.columns_chooser.parentNode.removeChild(this.columns_chooser);
		}
		this.columns_chooser = button;
		var t=this;
		this.columns_chooser.onclick = function() { t._menuColumns(this); return false; };
	},
	/**
	 * When the given button is clicked, we will export the content of the grid.
	 * @param {Element} button the button
	 * @param {String} filename if specified, the exported file will have this name
	 * @param {String} sheetname if specified, and export is in Excel format, the sheet of the Excel file will have this name
	 */
	setExportButton: function(button,filename,sheetname) {
		var t=this;
		button.onclick = function() {
			require("context_menu.js",function(){
				var menu = new context_menu();
				menu.removeOnClose = true;
				menu.addTitleItem(null, "Export Format");
				menu.addIconItem('/static/data_model/excel_16.png', 'Excel 2007 (.xlsx)', function() { t.grid.exportData('excel2007',filename,sheetname,["#actions"]); });
				menu.addIconItem('/static/data_model/excel_16.png', 'Excel 5 (.xls)', function() { t.grid.exportData('excel5',filename,sheetname,["#actions"]); });
				menu.addIconItem('/static/data_model/pdf_16.png', 'PDF', function() { t.grid.exportData('pdf',filename,sheetname,["#actions"]); });
				menu.addIconItem('/static/data_model/csv.gif', 'CSV', function() { t.grid.exportData('csv',filename,sheetname,["#actions"]); });
				menu.showBelowElement(button);
			});
		};
	},
	/**
	 * When the given button is clicked, we will display the screen to print the grid
	 * @param {Element} button the button
	 */
	setPrintButton: function(button) {
		var t=this;
		button.onclick = function() {
			t.grid.print();
		};
	},
	
	/** Ask to highlight the row on which the cursor is */
	highlightRowOnHover : function() {
		addClassName(this.grid._table, "selected_hover");
	},
	
	/** Internal function to create the context_menu to display for choosing columns to show/hide
	 * @param {Element} button the button below which we will display the context menu
	 */
	_menuColumns: function(button) {
		var t=this;
		require("context_menu.js", function() {
			var menu = new context_menu();
			t._fillMenuColumns(menu, t.columns, 0);
			menu.showBelowElement(button);
		});
	},
	/** Internal function filling a context_menu with the given columns
	 * @param {context_menu} menu the menu to fill
	 * @param {Array} columns the list of columns
	 * @param {Number} padding to add an indentation
	 */
	_fillMenuColumns: function(menu, columns, padding) {
		for (var i = 0; i < columns.length; ++i) {
			var div = document.createElement("DIV");
			div.style.paddingLeft = padding+"px";
			if (columns[i] instanceof CustomDataGridColumn) {
				if (columns[i].always_shown) continue;
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
				var cols = columns[i].getFinalColumns();
				if (cols.length == 0) continue;
				var always = true;
				for (var j = 0; j < cols.length; ++j) always &= cols[j].always_shown;
				if (always) continue;
				var cb = document.createElement("INPUT"); cb.type = 'checkbox';
				cb.checked = columns[i].shown ? "checked" : "";
				cb.col = columns[i];
				cb.sub_cols = cols;
				cb.g = this;
				cb.style.cssFloat = "left";
				cb.onchange = function() {
					for (var i = 0; i < this.sub_cols.length; ++i) {
						if (this.checked)
							this.g.showColumn(this.sub_cols[i].grid_column.id);
						else
							this.g.hideColumn(this.sub_cols[i].grid_column.id);
						// check or uncheck sub check-boxes
						var items = menu.getItems();
						for (var j = 0; j < items.length; ++j) {
							if (items[j].childNodes[0].col == this.sub_cols[i])
								items[j].childNodes[0].checked = this.checked ? "checked" : "";
						}
					}
				};
				div.appendChild(cb);
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