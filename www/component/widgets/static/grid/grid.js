if (typeof require != 'undefined') {
	require("typed_field.js",function(){
		require("field_blank.js");
	});
	theme.css("grid.css");
}

/** Represents an action that can be done on a column
 * @param {String} id identifier of the action
 * @param {String} icon URL of the icon to display in the header of the column
 * @param {Function} onclick function called when the user clicks on the icon
 * @param {String} tooltip explaination of the action
 */
function GridColumnAction(id,icon,onclick,tooltip) {
	this.id = id;
	this.icon = icon;
	this.onclick = onclick;
	this.tooltip = tooltip;
}

/**
 * Column containing sub-columns (in other words, a TH with a colspan)
 * @param {Element|String} title the title to put in the TH
 * @param {Array} sub_columns columns under this container, which may be containers or final columns
 * @param {Object} attached_data if ever you want to attach some info to this column container
 */
function GridColumnContainer(title, sub_columns, attached_data) {
	this.id = generateID();
	this.title = title;
	/** {String} If set, this is the title but only with text (no icon...) */
	this.text_title = null;
	this.sub_columns = sub_columns;
	this.attached_data = attached_data;
	/** The TH */
	this.th = document.createElement("TH");
	this.th.className = "container";
	if (title instanceof Element)
		this.title_part = title;
	else {
		this.title_part = document.createElement("SPAN");
		this.title_part.innerHTML = title;
	}
	this.th.appendChild(this.title_part);
	this.th.col = this;
	window.to_cleanup.push(this);
	/** Clean to avoid memory leaks */
	this.cleanup = function() {
		this.th.col = null;
		this.th = null;
		this.attached_data = null;
		this.sub_columns = null;
		for (var i = 0; i < this.actions.length; ++i) this.actions[i].element = null;
		this.actions = null;
	};
	/** Refresh the number of levels under this container */
	this._updateLevels = function() {
		this.nb_columns = 0;
		this.levels = 2;
		for (var i = 0; i < this.sub_columns.length; ++i) {
			this.sub_columns[i].parent_column = this;
			if (this.sub_columns[i].isHidden()) continue;
			if (this.sub_columns[i] instanceof GridColumn) {
				this.nb_columns++;
				continue;
			}
			if (this.levels < this.sub_columns[i].levels+1) this.levels = this.sub_columns[i].levels+1;
			this.nb_columns += sub_columns[i].nb_columns;
		}
		this.th.colSpan = this.nb_columns;
		this.th.style.display = this.nb_columns == 0 ? "none" : "";
		if (this.parent_column) this.parent_column._updateLevels();
	};
	/** Called by the grid, to increase the number of rows taken by this container and its sub-columns */
	this.increaseRowSpan = function() {
		for (var i = 0; i < this.sub_columns.length; ++i)
			this.sub_columns[i].increaseRowSpan();
	};
	this._updateLevels();
	/** Get the number of final columns
	 * @returns {Number} the number
	 */
	this.getNbFinalColumns = function() {
		var nb = 0;
		for (var i = 0; i < this.sub_columns.length; ++i) {
			if (this.sub_columns[i] instanceof GridColumnContainer)
				nb += this.sub_columns[i].getNbFinalColumns();
			else
				nb++;
		}
		return nb;
	};
	/** Retrieve all final columns from this container
	 * @returns {Array} list of final columns
	 */
	this.getFinalColumns = function() {
		var list = [];
		for (var i = 0; i < this.sub_columns.length; ++i) {
			if (this.sub_columns[i] instanceof GridColumnContainer) {
				var sub_list = this.sub_columns[i].getFinalColumns();
				for (var j = 0; j < sub_list.length; ++j) list.push(sub_list[j]);
			} else
				list.push(this.sub_columns[i]);
		}
		return list;
	};
	/** Add a column, or a container, under this container
	 * @param {GridColumn|GridColumnContainer} col the column to add
	 * @param {Number} index if specified, the column will be inserted at the specified index
	 */
	this.addSubColumn = function(col, index) {
		if (typeof index == 'undefined' || index >= this.sub_columns.length)
			this.sub_columns.push(col);
		else
			this.sub_columns.splice(index,0,col);
		this._updateLevels();
		this.grid._subColumnAdded(this, col);
	};
	/** Returns true if this column is currently hidden
	 * @returns {Boolean} true if hidden
	 */
	this.isHidden = function() {
		for (var i = 0; i < this.sub_columns.length; ++i)
			if (!this.sub_columns[i].isHidden()) return false;
		return true;
	};
	/** Returns true if this column can be hidden
	 * @returns {Boolean} true if can be hidden
	 */
	this.canBeHidden = function() {
		for (var i = 0; i < this.sub_columns.length; ++i)
			if (!this.sub_columns[i].canBeHidden()) return false;
		return true;
	};
	/** List of actions available on this container */
	this.actions = [];
	/** Add an action
	 * @param {GridColumnAction} action the action to add
	 */
	this.addAction = function(action) {
		this.actions.push(action);
		var t=this;
		var img = document.createElement("IMG");
		img.src = action.icon;
		img.style.verticalAlign = "middle";
		img.style.cursor = "pointer";
		if (action.tooltip) tooltip(img, action.tooltip);
		img.onclick = function(ev) {
			action.onclick(ev, action, t);
		};
		img.style.marginLeft = "1px";
		img.style.marginRight = "1px";
		setOpacity(img, 0.65);
		listenEvent(img, 'mouseover', function() { setOpacity(img, 1); });
		listenEvent(img, 'mouseout', function() { setOpacity(img, 0.65); });
		action.element = img;
		this.th.appendChild(img);
		img.ondomremoved(function(img) { action.element = null; img = null; action = null; t = null; });
	};
	/** Remove the given action
	 * @param {GridColumnAction} action the action to remove
	 */
	this.removeAction = function(action) {
		this.actions.remove(action);
		this.th.removeChild(action.element);
	};
	/** Get an action by its ID
	 * @param {String} id the id of the action to search
	 * @returns {GridColumnAction} the action, or null if not found
	 */
	this.getAction = function(id) {
		for (var i = 0; i < this.actions.length; ++i)
			if (this.actions[i].id == id) return this.actions[i];
		return null;
	};
}

/**
 * A column in a grid
 * @param {String} id identifier
 * @param {String|Element} title the title to put in the header of the column
 * @param {Number} width if specified, fixed width of the column
 * @param {String} align one of 'right','left', or 'center'. If not specified, 'left' will be used.
 * @param {String} field_type class name of the typed_field
 * @param {Boolean} editable indicates if we can edit data in this column
 * @param {Function} onchanged if specified, this function will be registered on every typed_field created for each row
 * @param {Function} onunchanged if specified, this function will be registered on every typed_field created for each row
 * @param {Object} field_args configuration of the typed_field
 * @param {Object} attached_data if ever you want to attach some info to this column
 */
function GridColumn(id, title, width, align, field_type, editable, onchanged, onunchanged, field_args, attached_data) {
	// check parameters
	if (!id) id = generateID();
	if (!field_type) field_type = "field_text";
	if (!field_args) field_args = {};
	// put values in the object
	this.id = id;
	this.title = title;
	/** {String} If set, this is the title but only with text (no icon...) */
	this.text_title = null;
	this.width = width;
	this.align = align ? align : "left";
	this.field_type = field_type;
	/** Indicates if the javascript for the given typed_field has been already loaded */
	this._loaded = false;
	/** Event fired when the javascript for the given typed_field has been loaded, meaning we are ready to display data in this column */
	this.onloaded = new Custom_Event();
	this.editable = editable;
	this.onchanged = onchanged;
	this.onunchanged = onunchanged;
	this.field_args = field_args;
	this.attached_data = attached_data;
	/** Indicates if the column is currently hidden */
	this.hidden = false;
	var t=this;
	require([["typed_field.js",field_type+".js"]], function() { t._loaded = true; t.onloaded.fire(); });
	// init
	/** The TH */
	this.th = document.createElement('TH');
	this.th.rowSpan = 1;
	this.th.col = this;
	this.th.className = "final";
	window.to_cleanup.push(this);
	/** Cleaning to avoid memory leaks */
	this.cleanup = function() {
		if (!this.th) return;
		this.th.col = null;
		this.th = null;
		this.attached_data = null;
		this.grid = null;
		for (var i = 0; i < this.actions.length; ++i)
			if (this.actions[i].element) {
				this.actions[i].element.data = null;
				this.actions[i].element = null;
			}
		this.actions = null;
		this.sort_function = null;
		this.sort_handler = null;
		this.span_actions = null;
		window.to_cleanup.remove(this);
	};
	/** The COL element */
	this.col = document.createElement('COL');
	if (this.width) {
		this.col.style.width = this.width+"px";
		this.col.style.minWidth = this.width+"px";
	}
	/** Event fired when the user click on the column */
	this.onclick = new Custom_Event();
	/** List of actions available on this column */
	this.actions = [];
	
	/** Called by the grid to increase the number of rows taken by the TH of this column */
	this.increaseRowSpan = function() {
		this.th.rowSpan++;
	};
	/** Switch all fields of this column between read-only and editable */
	this.toggleEditable = function() {
		this.editable = !this.editable;
		var index = this.grid.columns.indexOf(this);
		if (this.grid.selectable) index++;
		for (var i = 0; i < this.grid.table.childNodes.length; ++i) {
			var row = this.grid.table.childNodes[i];
			if (index >= row.childNodes.length) continue;
			var td = row.childNodes[index];
			if (td.field)
				td.field.setEditable(this.editable);
		}
		this._refreshTitle();
	};
	/** Change the identifier of this column
	 * @param {String} id the new identifier
	 */
	this.setId = function(id) {
		for (var i = 0; i < this.grid.table.childNodes.length; ++i) {
			var tr = this.grid.table.childNodes[i];
			// change in row_data
			if (tr.row_data)
				for (var j = 0; j < tr.row_data.length; ++j)
					if (tr.row_data[j].col_id == this.id) { tr.row_data[j].col_id = id; break; }
			// change in td
			for (var j = 0; j < tr.childNodes.length; ++j)
				if (tr.childNodes[j].col_id == this.id) { tr.childNodes[j].col_id = id; break; }
		}
		this.id = id;
	};
	/** Add an action
	 * @param {GridColumnAction} action the action to add
	 */
	this.addAction = function(action) {
		this.actions.push(action);
		this._refreshTitle();
	};
	/** Remove an actino
	 * @param {GridColumnAction} action the action to remove
	 */
	this.removeAction = function(action) {
		this.actions.remove(action);
		this._refreshTitle();
	};
	/** Search an action using its ID
	 * @param {String} id the identifier of the action to search
	 * @returns {GridColumnAction} the action, or null if not found
	 */
	this.getAction = function(id) {
		for (var i = 0; i < this.actions.length; ++i)
			if (this.actions[i].id == id) return this.actions[i];
		return null;
	};

	/**
	 * Add sorting capability to this column
	 * @param {Function} sort_function if specified, function to compare 2 values. If not specified, the function compare from the typed_field will be used, if available.
	 */
	this.addSorting = function(sort_function) {
		if (!sort_function) {
			var t=this;
			require([["typed_field.js",field_type+".js"]], function() {
				if (!window[field_type].prototype.compare) return;
				t.addSorting(window[field_type].prototype.compare);
			});
			return;
		}
		if (!this.sort_order)
			this.sort_order = 3; // not sorted
		this.sort_function = sort_function;
		var t=this;
		this.onclick.addListener(function(){
			var new_sort = t.sort_order == 1 ? 2 : 1;
			t._onsort(new_sort);
		});
		this._refreshTitle();
	};
	/**
	 * Add sorting capability, but which cannot be handled directly using the values.
	 * Example: if we use paging, meaning for exemple we display only 100 rows on 500. In this case we cannot sort ourself, because we don't have all the data to do it.
	 * @param {Function} handler function called when sorting is requested on this column
	 */
	this.addExternalSorting = function(handler) {
		if (!this.sort_order)
			this.sort_order = 3; // not sorted
		this.sort_handler = handler;
		this.onclick.addListener(function(){
			var new_sort = t.sort_order == 1 ? 2 : 1;
			t._onsort(new_sort);
		});
	};
	/** Ask to sort data using this column
	 * @param {Boolean} asc true for ascending order, or false for descending order
	 */
	this.sort = function(asc) {
		if (!this.sort_function && !this.sort_handler) {
			var t=this;
			setTimeout(function(){t.sort(asc);},25);
			return;
		};
		this._onsort(asc ? 1 : 2);
	};
	/**
	 * Add filtering capability to this column. The function applyFilters on the grid will be used.
	 */
	this.addFiltering = function() {
		var url = getScriptPath("grid.js");
		var t=this;
		var a = new GridColumnAction('filter', url+"/filter.gif",function(ev,a,col){
			stopEventPropagation(ev);
			if (t.filtered) {
				t.filtered = false;
				a.icon = url+"/filter.gif";
				t._refreshTitle();
				t.grid.applyFilters();
			} else {
				if (t.grid.table.childNodes.length == 0) return false;
				require("context_menu.js", function() {
					var values = [];
					var index = 0;
					var ptr = t.th;
					while (ptr.previousSibling) { index++; ptr = ptr.previousSibling; };
					for (var i = 0; i < t.grid.table.childNodes.length; ++i) {
						var row = t.grid.table.childNodes[i];
						if (row.style.visibility == "hidden") continue;
						var cell = row.childNodes[index];
						var value = cell.field.getCurrentData();
						var found = false;
						for (var j = 0; j < values.length; ++j)
							if (values[j] == value) { found = true; break; }
						if (!found) values.push(value);
					}
					var menu = new context_menu();
					var checkboxes = [];
					for (var i = 0; i < values.length; ++i) {
						var item = document.createElement("DIV");
						var cb = document.createElement("INPUT");
						cb.type = 'checkbox';
						cb.checked = 'checked';
						item.appendChild(cb);
						checkboxes.push(cb);
						t.grid._createField(t, t.field_type, false, null, null, t.field_args, item, values[i], function(input) {
							input.disabled = 'disabled';
						});
						item.style.paddingRight = "2px";
						menu.addItem(item);
						item.onclick = null;
					}
					menu.removeOnClose = true;
					menu.onclose = function() {
						t.filtered = true;
						a.icon = url+"/remove_filter.gif";
						t._refreshTitle();
						t.filter_values = [];
						for (var i = 0; i < checkboxes.length; ++i)
							if (checkboxes[i].checked)
								t.filter_values.push(values[i]);
						if (t.filter_values.length == checkboxes.length) {
							t.filter_values = null;
							t.filtered = false;
							a.icon = url+"/filter.gif";
							t._refreshTitle();
						}
						t.grid.applyFilters();
					};
					menu.showBelowElement(a.element);
				});
			}
			return false;
		});
		this.addAction(a);
	};
	/** Return true if this column is hidden
	 * @returns {Boolean} true if hidden
	 */
	this.isHidden = function() { return this.hidden; };
	/** Return true if this column can be hidden
	 * @returns {Boolean} true if can be hidden
	 */
	this.canBeHidden = function() {
		var f = new window[field_type](null,true,field_args);
		return f.canBeNull();
	};
	/** Hide/Show this column
	 * @param {Boolean} hidden true to hide, false to show
	 */
	this.hide = function(hidden) {
		if (hidden == this.hidden) return;
		this.hidden = hidden;
		this.th.style.display = hidden ? "none" : "";
		// hide td for each row
		var index = this.grid.columns.indexOf(this);
		for (var i = 0; i < this.grid.table.childNodes.length; ++i) {
			var tr = this.grid.table.childNodes[i];
			tr.childNodes[index].style.display = hidden ? "none" : "";
		}
		// refresh container if needed
		if (this.parent_column) this.parent_column._updateLevels();
		layout.changed(this.th.parentNode); // parent node, because if hidden layout will ignore this event
	};
	/** Internal function to create the content of the column header with title, actions... */
	this._refreshTitle = function() {
		var url = getScriptPath("grid.js");
		var t=this;
		this.th.removeAllChildren();
		this.th.style.textAlign = this.align;
		var title_part;
		if (title instanceof Element) title_part = title;
		else {
			title_part = document.createElement("SPAN");
			title_part.innerHTML = title;
		}
		this.th.appendChild(title_part);
		if (this.grid && this.grid.columns_movable && !this.parent_column) {
			title_part.style.cursor = "move";
			title_part.draggable = true;
			title_part.ondragstart = function(event) {
				event.dataTransfer.setData('grid_column_'+t.grid.grid_id,t.id);
				event.dataTransfer.effectAllowed = 'move';
				return true;
			};
			var over = function(event) {
				if (!event.dataTransfer.types.contains("grid_column_"+t.grid.grid_id)) return false;
				var x = event.clientX-t.th.offsetLeft;
				var w = t.th.offsetWidth;
				if (x < w/2) {
					// insert before
					t.th.style.borderLeft = "2px dotted #808080";
					t.th.style.borderRight = "";
				} else {
					// insert after
					t.th.style.borderRight = "2px dotted #808080";
					t.th.style.borderLeft = "";
				}
				event.dataTransfer.dropEffect = "move";
				event.preventDefault();
				return true;
			};
			this.th.ondragenter = over;
			this.th.ondragover = over;
			this.th.ondragleave = function() {
				t.th.style.borderLeft = "";
				t.th.style.borderRight = "";
			};
			this.th.ondrop = function(event) {
				t.th.style.borderLeft = "";
				t.th.style.borderRight = "";
				var col_id = event.dataTransfer.getData("grid_column_"+t.grid.grid_id);
				var x = event.clientX-t.th.offsetLeft;
				var w = t.th.offsetWidth;
				if (x < w/2) {
					t.grid.moveColumnBefore(col_id, t);
				} else {
					t.grid.moveColumnAfter(col_id, t);
				}
			};
		} else {
			title_part.style.cursor = "";
			title_part.draggable = false;
			title_part.ondragstart = null;
			this.th.ondragenter = null;
			this.th.ondragover = null;
			this.th.ondragleave = null;
		}
		this.span_actions = document.createElement("DIV");
		this.span_actions.style.whiteSpace = 'nowrap';
		//if (this.align == "right")
		//	this.th.insertBefore(span, this.th.childNodes[0]);
		//else
			this.th.appendChild(this.span_actions);
		var create_action_image = function(url, info, onclick) {
			var img = document.createElement("IMG");
			img.src = url;
			img.style.verticalAlign = "middle";
			img.style.cursor = "pointer";
			if (info) tooltip(img, info);
			img.onclick = onclick;
			img.style.marginLeft = "1px";
			img.style.marginRight = "1px";
			setOpacity(img, 0.65);
			listenEvent(img, 'mouseover', function() { setOpacity(img, 1); });
			listenEvent(img, 'mouseout', function() { setOpacity(img, 0.65); });
			return img;
		};
		if (this.sort_order) {
			switch (this.sort_order) {
			case 1: // ascending
				this.span_actions.appendChild(create_action_image(
					url+"/arrow_up_10.gif",
					"Sort by descending order (currently ascending)",
					function() { t._onsort(2); }
				));
				break;
			case 2: // descending
				this.span_actions.appendChild(create_action_image(
					url+"/arrow_down_10.gif",
					"Sort by ascending order (currently descending)",
					function() { t._onsort(1); }
				));
				break;
			case 3: // not sorted yet
				this.span_actions.appendChild(create_action_image(
					url+"/arrow_up_10.gif",
					"Sort by ascending order",
					function() { t._onsort(1); }
				));
				this.span_actions.appendChild(create_action_image(
					url+"/arrow_down_10.gif",
					"Sort by descending order",
					function() { t._onsort(2); }
				));
				break;
			}
		}
		for (var i = 0; i < this.actions.length; ++i) {
			var img = create_action_image(
				this.actions[i].icon,
				this.actions[i].tooltip,
				function(ev) { this.data.onclick(ev, this.data, t); }
			);
			img.data = this.actions[i];
			this.actions[i].element = img;
			this.span_actions.appendChild(img);
			img.ondomremoved(function(img) { img.data.element = null; img.data = null; });
		}
		layout.changed(this.th);
	};
	/** Internal function called when sort is requested
	 * @param {Number} sort_order how to sort
	 */
	this._onsort = function(sort_order) {
		var t=this;
		this.grid.onallrowsready(function() {
			// cancel sorting of other columns
			for (var i = 0; i < t.grid.columns.length; ++i) {
				var col = t.grid.columns[i];
				if (col == t) continue;
				if (col.sort_order) {
					col.sort_order = 3;
					col._refreshTitle();
				}
			}
			if (t.sort_function) {
				// get all rows
				var rows = [];
				for (var i = 0; i < t.grid.table.childNodes.length; ++i)
					rows.push(t.grid.table.childNodes[i]);
				// call sort function
				var col_index = t.grid.columns.indexOf(t);
				if (t.grid.selectable) col_index++;
				rows.sort(function(r1,r2){
					var f1 = r1.childNodes[col_index].field;
					var f2 = r2.childNodes[col_index].field;
					var v1 = f1.getCurrentData();
					var v2 = f2.getCurrentData();
					var res = t.sort_function(v1,v2);
					if (sort_order == 2) res = -res;
					return res;
				});
				// order rows
				for (var i = 0; i < rows.length; ++i) {
					if (t.grid.table.childNodes[i] == rows[i]) continue;
					t.grid.table.insertBefore(rows[i], t.grid.table.childNodes[i]);
				}
			} else
				t.sort_handler(sort_order);
			
			t.sort_order = sort_order;
			t._refreshTitle();
		});
	};
}

/**
 * A grid is basically a table, with columns and rows.
 * Then it add different functionalities around it like moving/showing/hidding columns,
 * make row clickable, selectable, edit the data inside, make the table scrollable without moving the headers...
 * Its specificity is for each column, it use a specific typed_field. In other words,
 * each column is 'typed' and can contain only one type of data (text, integer, date...).
 * Then, each cell will contain an instance of a typed_field, which will be used to display a data,
 * to switch between editable and read-only...
 * Two additional layers are typically used instead of using directly a grid:<ul>
 * <li>data_list: given a database table, it will be able to retrieve all the columns we can display, to retrieve the data from the backend, to add paging and sorting capabilities...</li>
 * <li>custom_data_grid: at the opposite of the data_list, which retrieve dynamically what it can display and the data to display from the back-end, the custom_data_grid do not make any call to the back-end, and is used to display a list of data, each of them being represented by a JavaScript object, and being able to extract from this object the data for each column.</li> 
 * </ul>
 * @param {Element} element where the table will be created
 */
function grid(element) {
	if (typeof element == 'string') element = document.getElementById(element);
	var t = this;
	window.to_cleanup.push(t);
	/** Cleaning to avoid memory leaks */
	t.cleanup = function() {
		unlistenEvent(window,'keyup',t._keyListener);
		element = null;
		t.element = null;
		t.columns = null;
		t.table = null;
		t.thead = null;
		t.grid_element = null;
		t.header_rows = null;
		t.colgroup = null;
		t = null;
	};
	/** Generate an ID for this grid */
	t.grid_id = generateID();
	t.element = element;
	/** List of columns */
	t.columns = [];
	/** Indicates if each row can be selected or not */
	t.selectable = false;
	/** If selectable, indicates if only one row can be selected at a time, or several */
	t.selectable_unique = false;
	/** Indicates if the user can move columns */
	t.columns_movable = false;
	/** Event fired when a column has been moved by the user */
	t.on_column_moved = new Custom_Event();
	/** URL of this JavaScript */
	t.url = getScriptPath("grid.js");
	/** {Function} If given, called when selection changed */
	t.onrowselectionchange = null;
	/** Event fired when a cell is created */
	t.oncellcreated = new Custom_Event();
	/** Event fired when a row receive the focus */
	t.onrowfocus = new Custom_Event();
	/** Request to be called when everything is ready and displayed in all rows and columns
	 * @param {Function} listener the function to be called
	 */
	t.onallrowsready = function(listener) {
		if (t._columns_loading == 0 && t._cells_loading == 0) {
			listener();
			return;
		}
		t._loaded_listeners.push(listener);
	};
	/** Add a column container
	 * @param {GridColumnContainer} column_container the container to add
	 * @param {Number} index if given, the container will be inserted at the given index
	 */
	t.addColumnContainer = function(column_container, index) {
		// if more levels, we add new rows in the header
		while (t.header_rows.length < column_container.levels)
			t._addHeaderLevel();
		// if less levels, set the rowSpan of first level
		if (column_container.levels < t.header_rows.length)
			column_container.th.rowSpan = t.header_rows.length-column_container.levels+1;
		t._addColumnContainer(column_container, 0, index);
		// handle movable columns
		if (this.columns_movable) this._columnContainerMovable(column_container);
	};
	/** Internal function when an addition row is needed in the headers */
	t._addHeaderLevel = function() {
		// new level needed
		// apppend a TR
		var tr = document.createElement("TR");
		t.header_rows.push(tr);
		t.header_rows[0].parentNode.appendChild(tr);
		// increase rowSpan of last one
		for (var i = this.selectable ? 1 : 0; i < t.header_rows[0].childNodes.length; i++)
			t.header_rows[0].childNodes[i].col.increaseRowSpan();
		if (this.selectable)
			t.header_rows[0].childNodes[0].rowSpan++;
		layout.changed(t.thead);
	};
	/** Internal function making a column container movable
	 * @param {GridColumnContainer} container the column container
	 */
	t._columnContainerMovable = function(container) {
		container.title_part.style.cursor = "move";
		container.title_part.draggable = true;
		container.title_part.ondragstart = function(event) {
			event.dataTransfer.setData('grid_column_'+t.grid_id,container.id);
			event.dataTransfer.effectAllowed = 'move';
			return true;
		};
		var over = function(event) {
			if (!event.dataTransfer.types.contains("grid_column_"+t.grid_id)) return false;
			var x = event.clientX-container.th.offsetLeft;
			var w = container.th.offsetWidth;
			if (x < w/2) {
				// insert before
				container.th.style.borderLeft = "2px dotted #808080";
				container.th.style.borderRight = "";
			} else {
				// insert after
				container.th.style.borderRight = "2px dotted #808080";
				container.th.style.borderLeft = "";
			}
			event.dataTransfer.dropEffect = "move";
			event.preventDefault();
			return true;
		};
		var leave = function() {
			container.th.style.borderLeft = "";
			container.th.style.borderRight = "";
		};
		var drop = function(event) {
			leave();
			var col_id = event.dataTransfer.getData("grid_column_"+t.grid_id);
			var x = event.clientX-container.th.offsetLeft;
			var w = container.th.offsetWidth;
			if (x < w/2) {
				t.moveColumnBefore(col_id, container);
			} else {
				t.moveColumnAfter(col_id, container);
			}
		};
		container.th.ondragenter = over;
		container.th.ondragover = over;
		container.th.ondragleave = leave;
		container.th.ondrop = drop;
	};
	/** Internal function adding a column container
	 * @param {GridColumnContainer} container the column container to add
	 * @param {Number} level the row in the header where it should be inserted
	 * @param {Number} index the index in the columns where to insert the container
	 */
	t._addColumnContainer = function(container, level, index) {
		container.grid = this;
		// insert the container TH
		if (typeof index != 'undefined') {
			// calculate the real index in the TR for the header
			var matrix = [];
			var matrix2 = [];
			for (var i = 0; i <= level; ++i) {
				var row = [];
				for (var j = 0; j < t.columns.length; ++j) row.push(null);
				matrix.push(row);
				row = [];
				for (var j = 0; j < t.columns.length; ++j) row.push(null);
				matrix2.push(row);
			}
			for (var i = 0; i <= level; ++i) {
				var tr = t.header_rows[i];
				for (var j = 0; j < tr.childNodes.length; ++j) {
					var th = tr.childNodes[j];
					var cols = th.colSpan ? th.colSpan : 1;
					var rows = th.rowSpan ? th.rowSpan : 1;
					var col;
					for (col = 0; col < t.columns.length; ++col)
						if (matrix2[i][col] == null) break;
					matrix[i][col] = th;
					for (var k = 0; k < rows && i+k<=level; k++)
						for (var l = 0; l < cols; ++l)
							matrix2[i+k][col+l] = th;
				}
			}
			var real_index = index + (t.selectable ? 1 : 0);
			var tr_index = 0;
			var i;
			for (i = real_index-1; i >= 0; i--)
				if (matrix[level][i] != null) tr_index++;
			if (tr_index < t.header_rows[level].childNodes.length)
				t.header_rows[level].insertBefore(container.th, t.header_rows[level].childNodes[tr_index]);
			else
				t.header_rows[level].appendChild(container.th);
		} else
			t.header_rows[level].appendChild(container.th);
		// continue insertion
		for (var i = 0; i < container.sub_columns.length; ++i) {
			if (container.sub_columns[i] instanceof GridColumnContainer) {
				index = t._addColumnContainer(container.sub_columns[i], level+container.th.rowSpan, index);
			} else {
				t._addFinalColumn(container.sub_columns[i], level+container.th.rowSpan, index);
				if (typeof index != 'undefined') index++;
			}
		}
		layout.changed(this.thead);
		layout.changed(this.table);
		return index;
	};
	/** Internal function to add a final column
	 * @param {GridColumn} col the column to add
	 * @param {Number} level the row in the headers where the column header should be inserted
	 * @param {Number} index at which index in the columns to insert this new column
	 */
	t._addFinalColumn = function(col, level, index) {
		if (typeof index != 'undefined') {
			if (index < 0) index = undefined;
			else if (index >= t.columns.length) index = undefined;
		}
		if (level < t.header_rows.length-1)
			col.th.rowSpan = t.header_rows.length-level;
		col.grid = this;
		if (typeof index == 'undefined') {
			t.columns.push(col);
			t.colgroup.appendChild(col.col);
			col.th.rowSpan = t.header_rows.length-level+1;
			t.header_rows[level].appendChild(col.th);
		} else {
			t.columns.splice(index,0,col);
			t.colgroup.insertBefore(col.col, t.colgroup.childNodes[t.selectable ? index +1 : index]);
			// need to calculate the real index inside the container
			var index_in_container = index;
			var i;
			for (i = level == 0 && t.selectable ? 1 : 0; i < t.header_rows[level].childNodes.length && index_in_container > 0; ++i) {
				var th = t.header_rows[level].childNodes[i];
				if (th.col instanceof GridColumnContainer) index_in_container -= th.col.getNbFinalColumns();
				else index_in_container--;
			}
			if (i >= t.header_rows[level].childNodes.length)
				t.header_rows[level].appendChild(col.th);
			else
				t.header_rows[level].insertBefore(col.th, t.header_rows[level].childNodes[i]);
		}
		if (level > 0) {
			if (col.parent_column.sub_columns.length > 1) {
				for (var i = 0; i < col.parent_column.sub_columns.length; ++i) {
					if (i == 0) addClassName(col.parent_column.sub_columns[i].th, "first_in_container");
					else removeClassName(col.parent_column.sub_columns[i].th, "first_in_container");
					if (i == col.parent_column.sub_columns.length-1) addClassName(col.parent_column.sub_columns[i].th, "last_in_container");
					else removeClassName(col.parent_column.sub_columns[i].th, "last_in_container");
				}
			}
		}
		if (t._columns_movable)
			col._makeMoveable();
		col._refreshTitle();
		if (!col._loaded) {
			t._columns_loading++;
			col.onloaded.addListener(function() { t._columns_loading--; t._checkLoaded(); });
		}
		// add cells
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var tr = t.table.childNodes[i];
			if (tr.title_row)
				tr.childNodes[0].colSpan++;
			else {
				var td = document.createElement("TD");
				td.col_id = col.id;
				var col_index = index + (t.selectable ? 1 : 0);
				if (col_index >= tr.childNodes.length)
					tr.appendChild(td);
				else
					tr.insertBefore(td, tr.childNodes[col_index]);
				var data = null;
				var original_data = undefined;
				if (tr.row_data)
				for (var k = 0; k < tr.row_data.length; ++k)
					if (col.id == tr.row_data[k].col_id) { data = tr.row_data[k].data; if (typeof tr.row_data[k].original_data != 'undefined') original_data = tr.row_data[k].original_data; break; }
				if (data) td.data_id = data.data_id;
				td.style.textAlign = col.align;
				t._createCell(col, data, td, function(field, original_data){
					if (typeof original_data != 'undefined') field.originalData = original_data;
					field.onfocus.addListener(function() { t.onrowfocus.fire(field.getHTMLElement().parentNode.parentNode); });
				}, original_data);
				td.ondomremoved(function(td) { td.field = null; });
			}
		}
		if (level > 0) {
			if (col.parent_column.sub_columns.length > 1) {
				var ids = [];
				for (var i = 0; i < col.parent_column.sub_columns.length; ++i) ids.push(col.parent_column.sub_columns[i].id);
				for (var i = 0; i < t.table.childNodes.length; ++i) {
					var tr = t.table.childNodes[i];
					for (var j = 0; j < tr.childNodes.length; ++j) {
						var td = tr.childNodes[j];
						if (td.col_id == col.parent_column.sub_columns[0].id) addClassName(td, "first_in_container");
						else if (ids.contains(td.col_id)) removeClassName(td, "first_in_container");
						if (td.col_id == col.parent_column.sub_columns[col.parent_column.sub_columns.length-1].id) addClassName(td, "last_in_container");
						else if (ids.contains(td.col_id)) removeClassName(td, "last_in_container");
					}
				}
			}
		}
		layout.changed(this.thead);
		layout.changed(this.table);
	};
	/** Internal function called when a sub column has been added into a container
	 * @param {GridColumnContainer} container the container
	 * @param {GridColumn|GridColumnContainer} col what has been added in the container
	 */
	t._subColumnAdded = function(container, col) {
		// get the top level
		var top_container = container;
		while (top_container.parent_column) top_container = top_container.parent_column;

		// if more levels, we add new rows in the header
		while (t.header_rows.length < top_container.levels)
			t._addHeaderLevel();
		// decrease the rowSpan of parents if needed
		var total_rowSpan = 0;
		var p = container;
		while (p) { total_rowSpan += p.th.rowSpan; p = p.parent_column; }
		p = container;
		while (p && total_rowSpan > t.header_rows.length-1) {
			if (p.rowSpan > 1) {
				total_rowSpan -= p.rowSpan-1;
				p.rowSpan = 1;
			}
			p = p.parent_column;
		}
		var level;
		for (level = 0; level < t.header_rows.length-1; level++)
			if (t.header_rows[level] == container.th.parentNode) break;
		if (col instanceof GridColumn) {
			// finally, add the final column
			var list = container.getFinalColumns();
			var first_index = this.getColumnIndex(col == list[0] ? list[1] : list[0]);
			t._addFinalColumn(col, level+1, first_index+list.indexOf(col));
		} else {
			var list = container.getFinalColumns();
			var first_index = this.getColumnIndex(list[0]);
			var sub_list = col.getFinalColumns();
			t._addColumnContainer(col, level+1, first_index+list.indexOf(sub_list[0]));
		}
	};
	/** Add a column
	 * @param {GridColumn} column the column to add
	 * @param {Number} index if given, the column will be inserted at the given index
	 */
	t.addColumn = function(column, index) {
		column.th.rowSpan = t.header_rows.length;
		t._addFinalColumn(column,0, index);
	};
	/** Returns the number of final columns
	 * @returns {Number} number of columns
	 */
	t.getNbColumns = function() { return t.columns.length; };
	/** Get a column by its index
	 * @param {Number} index index
	 * @returns {GridColumn} the column
	 */
	t.getColumn = function(index) { return t.columns[index]; };
	/** Get a column by its id
	 * @param {String} id identifier
	 * @returns {GridColumn} the column
	 */
	t.getColumnById = function(id) {
		for (var i = 0; i < t.columns.length; ++i)
			if (t.columns[i].id == id)
				return t.columns[i];
		return null;
	};
	/** Get a column by its attached data
	 * @param {Object} data attached data to search
	 * @returns {GridColumn} the column
	 */
	t.getColumnByAttachedData = function(data) {
		for (var i = 0; i < t.columns.length; ++i)
			if (t.columns[i].attached_data == data)
				return t.columns[i];
		return null;
	};
	/** Get a column container by its attached data
	 * @param {Object} data attached data to search
	 * @returns {GridColumnContainer} the column container
	 */
	t.getColumnContainerByAttachedData = function(data) {
		for (var i = 0; i < t.columns.length; ++i)
			if (t.columns[i].parent_column) {
				var c = t._getColumnContainerByAttachedData(t.columns[i].parent_column, data);
				if (c) return c;
			}
		return null;
	};
	/** Internal function to handle recursivity
	 * @param {GridColumnContainer} container parent container
	 * @param {Object} data attached data to search
	 * @returns {GridColumnContainer} the column container
	 */
	t._getColumnContainerByAttachedData = function(container, data) {
		if (container.attached_data == data) return container;
		if (!container.parent_column) return null;
		return t._getColumnContainerByAttachedData(container.parent_column, data);
	};
	/** Get index of a column
	 * @param {GridColumn} col the column
	 * @returns {Number} the index
	 */
	t.getColumnIndex = function(col) { return t.columns.indexOf(col); };
	/** Get index of a column
	 * @param {String} col_id identifier of the column to search
	 * @returns {Number} the index
	 */
	t.getColumnIndexById = function(col_id) {
		for (var i = 0; i < t.columns.length; ++i)
			if (t.columns[i].id == col_id)
				return i;
		return -1;
	};
	/** Get column container by its id
	 * @param {String} id identifier of the container to search
	 * @returns {GridColumnContainer} the container
	 */
	t.getColumnContainerById = function(id) {
		for (var i = 0; i < t.columns.length; ++i)
			if (t.columns[i].parent_column) {
				var c = t._getColumnContainerById(t.columns[i].parent_column, id);
				if (c) return c;
			}
		return null;
	};
	/** Internal function to handle recursivity
	 * @param {GridColumnContainer} container parent
	 * @param {String} id identifier of the container to search
	 * @returns {GridColumnContainer} the container
	 */
	t._getColumnContainerById = function(container, id) {
		if (container.id == id) return container;
		if (!container.parent_column) return null;
		return t._getColumnContainerById(container.parent_column, id);
	};
	/** Remove a column
	 * @param {Number} index index of the column to remove
	 * @param {Boolean} keep_data if false, data corresponding to the removed column will be removed as well
	 * @param {Boolean} keep_sub_column if true, sub columns won't be removed
	 */
	t.removeColumn = function(index, keep_data, keep_sub_column) {
		var col = t.columns[index];
		t.columns.splice(index,1);
		t.colgroup.removeChild(col.col);
		var td_index = index + (t.selectable ? 1 : 0);
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var row = t.table.childNodes[i];
			if (row.title_row)
				row.childNodes[0].colSpan--;
			else
				row.removeChild(row.childNodes[td_index]);
			if (row.row_data && !keep_data)
				for (var j = 0; j < row.row_data.length; ++j)
					if (row.row_data[j].col_id == col.id) {
						row.row_data.splice(j,1);
						break;
					}
		}
		if (!col.parent_column)
			t.header_rows[0].removeChild(col.th);
		else {
			// decrease colSpan for parents
			var p = col.parent_column;
			while (p) {
				p.th.colSpan--;
				p = p.parent_column;
			}
			// remove from parent
			var p = col.parent_column;
			var c = col;
			while (p) {
				if (!keep_sub_column) p.sub_columns.remove(c);
				c.th.parentNode.removeChild(c.th);
				if (p.sub_columns.length > 0) {
					// still something
					if (p.sub_columns.length == 1) {
						var last_col = p.sub_columns[0];
						for (var i = 0; i < t.table.childNodes.length; ++i) {
							var tr = t.table.childNodes[i];
							for (var j = 0; j < tr.childNodes.length; ++j) {
								var td = tr.childNodes[j];
								if (td.col_id == last_col.id) {
									removeClassName(td, "last_in_container");
									removeClassName(td, "first_in_container");
									break;
								}
							}
						}
					} else {
						var ids = [];
						for (var i = 0; i < col.parent_column.sub_columns.length; ++i) ids.push(col.parent_column.sub_columns[i].id);
						for (var i = 0; i < t.table.childNodes.length; ++i) {
							var tr = t.table.childNodes[i];
							for (var j = 0; j < tr.childNodes.length; ++j) {
								var td = tr.childNodes[j];
								if (td.col_id == col.parent_column.sub_columns[0].id) addClassName(td, "first_in_container");
								else if (ids.contains(td.col_id)) removeClassName(td, "first_in_container");
								if (td.col_id == col.parent_column.sub_columns[col.parent_column.sub_columns.length-1].id) addClassName(td, "last_in_container");
								else if (ids.contains(td.col_id)) removeClassName(td, "last_in_container");
							}
						}
					}
					break;
				}
				// no more sub column -> remove it
				p.th.parentNode.removeChild(p.th);
				p = p.parent_column;
				c = p;
			}
		}
		if (!keep_data)
			t.applyFilters();
		layout.changed(this.thead);
		layout.changed(this.table);
	};
	/**
	 * Refresh a column, by re-building all cells corresponding to it. May be used if the typed field of a column changed
	 * @param {GridColumn} column the column to refresh
	 */
	t.rebuildColumn = function(column) {
		column._refreshTitle();
		var index = t.columns.indexOf(column);
		if (t.selectable) index++;
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var row = t.table.childNodes[i];
			var td = row.childNodes[index];
			if (td.field) {
				var data = td.field.getCurrentData();
				var original = td.field.originalData;
				td.removeAllChildren();
				t._createCell(column, data, td, function(field){
					field.originalData = original;
					field.onfocus.addListener(function() { t.onrowfocus.fire(field.getHTMLElement().parentNode.parentNode); });
				});
				td.style.textAlign = column.align;
			}
		}
	};
	/**
	 * Move a column
	 * @param {String} col_id identifier of the column to move
	 * @param {GridColumn} before_col the column before which to move the column
	 */
	t.moveColumnBefore = function(col_id, before_col) {
		if (before_col.id == col_id) return;
		if (before_col instanceof GridColumnContainer)
			before_col = before_col.getFinalColumns()[0];
		t.moveColumn(col_id, t.getColumnIndex(before_col));
	};
	/**
	 * Move a column
	 * @param {String} col_id identifier of the column to move
	 * @param {GridColumn} after_col the column after which to move the column
	 */
	t.moveColumnAfter = function(col_id, after_col) {
		if (after_col.id == col_id) return;
		if (after_col instanceof GridColumnContainer) {
			var finals = after_col.getFinalColumns();
			after_col = finals[finals.length-1];
		}
		t.moveColumn(col_id, t.getColumnIndex(after_col)+1);
	};
	/**
	 * Move a column
	 * @param {String} col_id identifier of the column to move
	 * @param {Number} index position where to move it
	 */
	t.moveColumn = function(col_id, index) {
		var prev_index = t.getColumnIndexById(col_id);
		if (prev_index >= 0) {
			// this is a final column
			var col = t.getColumnById(col_id);
			t.removeColumn(prev_index, true);
			t.addColumn(col, index-(index > prev_index ? 1 : 0));
			t.on_column_moved.fire({column:col,index:index});
			return;
		}
		// this is a container
		var container = t.getColumnContainerById(col_id);
		// keep hierarchy
		var getHierarchy = function(container) {
			var res = [];
			for (var i = 0; i < container.sub_columns.length; ++i)
				if (container.sub_columns[i] instanceof GridColumnContainer)
					res.push({container:container.sub_columns[i],hierarchy:getHierarchy(container.sub_columns[i])});
				else
					res.push(container.sub_columns[i]);
			return res;
		};
		var hierarchy = getHierarchy(container);
		// remove columns
		var finals = container.getFinalColumns();
		prev_index = t.getColumnIndex(finals[0]);
		for (var i = 0; i < finals.length; ++i)
			t.removeColumn(prev_index, true);
		// put back hierarchy
		var putBack = function(container, hier) {
			for (var i = 0; i < hier.length; ++i)
				if (hier[i] instanceof GridColumn)
					container.sub_columns.push(hier[i]);
				else {
					putBack(hier[i].container, hier[i].hierarchy);
					container.sub_columns.push(hier[i].container);
				}
			container._updateLevels();
		};
		putBack(container, hierarchy);
		// add the container at its new position
		t.addColumnContainer(container, index-(index > prev_index ? finals.length : 0));
		t.on_column_moved.fire({column:container,index:index});
	};
	
	/**
	 * Make rows selectable or not
	 * @param {Boolean} selectable true to be able to select row(s)
	 * @param {Boolean} unique if true, only one row can be selected (using radio buttons)
	 */
	t.setSelectable = function(selectable, unique) {
		if (!unique) unique = false;
		if (t.selectable == selectable && t.selectable_unique == unique) return;
		if ((t.selectable && !selectable) || (t.selectable && selectable && unique != t.selectable_unique)) {
			if (t.header_rows[0].childNodes.length > 0) {
				t.header_rows[0].removeChild(t.header_rows[0].childNodes[0]);
				t.colgroup.removeChild(t.colgroup.childNodes[0]);
				for (var i = 0; i < t.table.childNodes.length; ++i)
					t.table.childNodes[i].removeChild(t.table.childNodes[i].childNodes[0]);
			}
		}
		t.selectable = selectable;
		t.selectable_unique = unique;
		if (selectable) {
			var th = document.createElement('TH');
			th.style.textAlign = "left";
			if (!unique) {
				var cb = document.createElement("INPUT");
				cb.type = 'checkbox';
				cb.onchange = function() { if (this.checked) t.selectAll(); else t.unselectAll(); };
				th.appendChild(cb);
			}
			var col = document.createElement('COL');
			col.width = 20;
			col.style.width ='20px';
			col.style.maxWidth ='20px';
			if (t.header_rows[0].childNodes.length == 0) {
				t.header_rows[0].appendChild(th);
				t.colgroup.appendChild(col);
			} else {
				t.header_rows[0].insertBefore(th, t.header_rows[0].childNodes[0]);
				t.colgroup.insertBefore(col, t.colgroup.childNodes[0]);
			}
			th.rowSpan = t.header_rows.length;
			for (var i = 0; i < t.table.childNodes.length; ++i)
				t.table.childNodes[i].insertBefore(t._createSelectionTD(), t.table.childNodes[i].childNodes[0]);
		}
		layout.changed(this.table);
	};
	/**
	 * Select all rows
	 */
	t.selectAll = function() {
		if (!t.selectable) return;
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var tr = t.table.childNodes[i];
			if (tr.style.visibility == "hidden") continue; // do not select filtered/hidden
			if (tr.className == "title_row") continue;
			var td = tr.childNodes[0];
			var cb = td.childNodes[0];
			if (cb.disabled) continue; //do not select if the checkbox is disabled
			cb.checked = 'checked';
			cb.onchange();
		}
		t._selectionChanged();
	};
	/**
	 * Unselect any selected row
	 */
	t.unselectAll = function() {
		if (!t.selectable) return;
		var changed = false;
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var tr = t.table.childNodes[i];
			var td = tr.childNodes[0];
			var cb = td.childNodes[0];
			if (cb.disabled) continue; //do not unselect if the checkbox is disabled
			if (cb.checked) {
				changed = true;
				cb.checked = '';
				if (cb.onchange) cb.onchange();
			}
		}
		if (changed)
			t._selectionChanged();
	};
	/** Internal function called when selection changed */
	t._selectionChanged = function() {
		if (t.onselect) {
			t.onselect(t.getSelectionByIndexes(), t.getSelectionByRowId());
		}
	};
	/** {Function} if specified, called when the selection changed */
	t.onselect = null;
	/**
	 * Get indexes of selected rows
	 * @returns {Array} list of indexes
	 */
	t.getSelectionByIndexes = function() {
		var selection = [];
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var tr = t.table.childNodes[i];
			var td = tr.childNodes[0];
			var cb = td.childNodes[0];
			if (cb.checked)
				selection.push(i);
		}
		return selection;
	};
	/**
	 * Get id of selected rows
	 * @returns {Array} list of id
	 */
	t.getSelectionByRowId = function() {
		var selection = [];
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var tr = t.table.childNodes[i];
			var td = tr.childNodes[0];
			var cb = td.childNodes[0];
			if (cb.checked)
				selection.push(tr.row_id);
		}
		return selection;
	};
	/**
	 * Check if a row is selected
	 * @param {Number} index index of the row to check
	 * @returns {Boolean} true if  the row is selected
	 */
	t.isSelected = function(index) {
		if (!t.selectable) return false;
		var tr = t.table.childNodes[index];
		var td = tr.childNodes[0];
		var cb = td.childNodes[0];
		return cb.checked;
	};
	/**
	 * Select/Unselect a row
	 * @param {Number} index index of the row
	 * @param {Boolean} selected true to select, false to unselect
	 */
	t.selectByIndex = function(index, selected) {
		if (!t.selectable) return;
		if (t.selectable_unique && selected) {
			for (var i = 0; i < t.table.childNodes.length; i++) {
				var tr = t.table.childNodes[i];
				var td = tr.childNodes[0];
				var cb = td.childNodes[0];
				if (hasClassName(tr, 'selected') != (i==index)) {
					if (t.onrowselectionchange)
						t.onrowselectionchange(tr.row_id, i==index);
					cb.checked = i==index ? 'checked' : '';
					if (i == index) addClassName(tr, "selected"); else removeClassName(tr, "selected");
				}
			}
		} else {
			var tr = t.table.childNodes[index];
			var td = tr.childNodes[0];
			var cb = td.childNodes[0];
			if (cb.checked != selected) {
				cb.checked = selected ? 'checked' : '';
				if (selected) addClassName(tr, "selected"); else removeClassName(tr, "selected");
				if (t.onrowselectionchange)
					t.onrowselectionchange(tr.row_id, selected);
			}
		}
	};
	/**
	 * Disable/Enable a row
	 * @param {Number} index index of the row
	 * @param {Boolean} disabled true to disable, false to enable
	 */
	t.disableByIndex = function(index, disabled){
		if(!t.selectable) return;
		var tr = t.table.childNodes[index];
		var td = tr.childNodes[0];
		var cb = td.childNodes[0];
		cb.disabled = disabled;
	};
	/**
	 * Select/Unselect a row
	 * @param {String} row_id identifier of the row
	 * @param {Boolean} selected true to select, false to unselect
	 */
	t.selectByRowId = function(row_id, selected) {
		if (!t.selectable) return;
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var tr = t.table.childNodes[i];
			if (tr.row_id != row_id && (!t.selectable_unique || !selected)) continue;
			var td = tr.childNodes[0];
			var cb = td.childNodes[0];
			if (t.selectable_unique && tr.row_id != row_id) {
				if (hasClassName(tr,'selected')) {
					cb.checked = "";
					removeClassName(tr, "selected");
					if (t.onrowselectionchange)
						t.onrowselectionchange(tr.row_id, false);
				}
			} else {
				if (cb.checked != selected) {
					cb.checked = selected ? 'checked' : '';
					if (selected) addClassName(tr, "selected"); else removeClassName(tr, "selected");
					if (t.onrowselectionchange)
						t.onrowselectionchange(tr.row_id, selected);
				}
			}
		}
	};
	
	/**
	 * Make the content of the grid scrollable, but keep the header fixed at the top
	 */
	t.makeScrollable = function() {
		var thead_knowledge = [];
		var update = function() {
			// put back the thead, so we can get the width of every th
			if (!t) return;
			t.element.style.paddingTop = "0px";
			t.element.style.position = "static";
			t.grid_element.style.overflow = "auto";
			t.grid_element.style.maxHeight = "";
			t.thead.style.position = "static";
			t.element.style.width = "";
			if (t.element.parentNode._fixed_by_grid) {
				t.element.parentNode.style.width = t.element.parentNode._fixed_by_grid_prev_width;
				t.element.parentNode.style.minWidth = t.element.parentNode._fixed_by_grid_prev_min_width;
				t.element.parentNode.style.maxWidth = t.element.parentNode._fixed_by_grid_prev_max_width;
			}
			var footer = null;
			for (var i = 0; i < t.table.parentNode.childNodes.length; ++i)
				if (t.table.parentNode.childNodes[i].nodeName == "TFOOT") { footer = t.table.parentNode.childNodes[i]; break; }
			if (footer && footer.childNodes.length > 0 && footer.childNodes[0]._for_fixed) footer.removeChild(footer.childNodes[0]);
			// remove fixed width
			if (t.selectable) {
				t.thead.childNodes[0].childNodes[0].style.width = "";
				t.thead.childNodes[0].childNodes[0].style.minWidth = "";
			}
			for (var i = 0; i < t.columns.length; ++i) {
				t.columns[i].th.style.width = "";
				t.columns[i].th.style.minWidth = "";
			}
			// put back original fixed col width
			for (var i = 0; i < t.colgroup.childNodes.length; ++i) {
				if (t.colgroup.childNodes[i]._width) {
					t.colgroup.childNodes[i].style.width = t.colgroup.childNodes[i]._width;
					t.colgroup.childNodes[i].style.minWidth = t.colgroup.childNodes[i]._width;
				} else {
					t.colgroup.childNodes[i].style.width = "";
					t.colgroup.childNodes[i].style.minWidth = "";
				}
			}
			var knowledge = [];
			// fix the size of the container
			var container_width = getWidth(t.element.parentNode, knowledge);
			var total_width = getWidth(t.element, knowledge);
			setWidth(t.element, total_width-1, knowledge);
			if (t.element.parentNode.clientWidth != container_width) {
				// the container is expanding with us ! typically this may be done by the flex box model
				// let's fix temporarly the container !
				t.element.parentNode._fixed_by_grid = true;
				t.element.parentNode._fixed_by_grid_prev_width = t.element.parentNode.style.width;
				t.element.parentNode._fixed_by_grid_prev_min_width = t.element.parentNode.style.minWidth;
				t.element.parentNode._fixed_by_grid_prev_max_width = t.element.parentNode.style.maxWidth;
				setWidth(t.element.parentNode, container_width, knowledge);
				t.element.parentNode.style.minWidth = t.element.parentNode.style.width;
				t.element.parentNode.style.maxWidth = t.element.parentNode.style.width;
			}
			// take header info
			var head_scroll = (-t.grid_element.scrollLeft+(t.grid_element.scrollWidth > t.grid_element.clientWidth ? 0 : 1));
			var head_width = t.grid_element.clientWidth+t.grid_element.scrollLeft-(t.grid_element.scrollWidth > t.grid_element.clientWidth ? 0 : 1);
			// take the width of each th
			if (t.selectable)
				t.thead.childNodes[0].childNodes[0]._width = getWidth(t.thead.childNodes[0].childNodes[0], knowledge);
			for (var i = 0; i < t.columns.length; ++i)
				t.columns[i].th._width = t.columns[i].isHidden() ? 0 : getWidth(t.columns[i].th, knowledge);
			//setWidth(t.element, total_width, knowledge);
			// take the width of each column
			var widths = [];
			if (t.selectable)
				widths.push(t.thead.childNodes[0].childNodes[0]._width);
			for (var i = 0; i < t.columns.length; ++i)
				widths.push(t.columns[i].th._width);
			// fix the width of each th
			if (t.selectable) {
				setWidth(t.thead.childNodes[0].childNodes[0], t.thead.childNodes[0].childNodes[0]._width, knowledge);
				t.thead.childNodes[0].childNodes[0].style.minWidth = t.thead.childNodes[0].childNodes[0].style.width;
			}
			for (var i = 0; i < t.columns.length; ++i) {
				setWidth(t.columns[i].th, t.columns[i].th._width, knowledge);
				t.columns[i].th.style.minWidth = t.columns[i].th.style.width;
			}
			// fix the width of each column
			var tr = document.createElement("TR");
			tr._for_fixed = true;
			tr.title_row = true;
			if (!footer) {
				footer = document.createElement("TFOOT");
				t.table.parentNode.appendChild(footer);
			}
			if (footer.childNodes.length > 0)
				footer.insertBefore(tr,footer.childNodes[0]);
			else
				footer.appendChild(tr);
			for (var i = 0; i < t.colgroup.childNodes.length; ++i) {
				if (t.colgroup.childNodes[i].style.width)
					 t.colgroup.childNodes[i]._width = t.colgroup.childNodes[i].style.width;
				t.colgroup.childNodes[i].style.width = widths[i]+"px";
				t.colgroup.childNodes[i].style.minWidth = widths[i]+"px";
				var td = document.createElement("TD");
				td.style.padding = "0px";
				if (widths[i] == 0) td.style.display = "none";
				var div = document.createElement("DIV");
				td.appendChild(div);
				tr.appendChild(td);
				//setWidth(td, widths[i], knowledge);
				//setWidth(div, widths[i], knowledge);
				div.style.width = widths[i]+"px";
				div.style.minWidth = widths[i]+"px";
			}
			tr.style.height = "0px";
			// put the thead as relative
			t.element.style.position = "relative";
			t.grid_element.style.overflow = "auto";
			if (t.element.style.height) t.grid_element.style.maxHeight = t.element.style.height;
			t.thead.style.position = "absolute";
			t.thead.style.top = "0px";
			t.thead.style.left = head_scroll+"px";
			t.thead.style.overflow = "hidden";
			setWidth(t.thead, head_width, thead_knowledge); 
			var head_height = t.thead.offsetHeight;
			t.element.style.paddingTop = (head_height-1)+"px";
			//t.table.parentNode.style.marginRight = "1px";
			layout.changed(t.element);
		};
		t.element.style.display = "flex";
		t.element.style.flexDirection = "column";
		t.grid_element.style.flex = "1 1 auto";
		t.grid_element.onscroll = function(ev) {
			var head_scroll = (-t.grid_element.scrollLeft+(t.grid_element.scrollWidth > t.grid_element.clientWidth ? 0 : 1));
			var head_width = t.grid_element.clientWidth+t.grid_element.scrollLeft-(t.grid_element.scrollWidth > t.grid_element.clientWidth ? 0 : 1);
			setWidth(t.thead, head_width, thead_knowledge); 
			t.thead.style.left = head_scroll+"px";
		};
		var update_timeout = null;
		var updater = function() {
			if (update_timeout) return;
			update_timeout = setTimeout(function() {
				update_timeout = null;
				if (!t) return;
				if (t._columns_loading == 0 && t._cells_loading == 0) // do not update if still loading, because we will need to update again
					update();
				else
					updater();
			},25);
		};
		layout.listenElementSizeChanged(t.element.parentNode, updater);
		layout.listenElementSizeChanged(t.element, updater);
		layout.listenInnerElementsChanged(t.thead, updater);
		layout.listenInnerElementsChanged(t.table, updater);
	};

	/**
	 * Change the data in the grid.
	 * @param {Array} data each element represent an entry/row.
	 * Each entry is an object {row_id:xxx,row_data:[]} where the row_id can be used later to identify the row or to attach data to the row.
	 * Each row_data element is an object {col_id:xxx,data_id:yyy,data:zzz} where the data is given to the typed field, col_id identifies the column and data_id can be used later to identify the data or to attach information to the data
	 */
	t.setData = function(data) {
		// empty table
		t.unselectAll();
		while (t.table.childNodes.length > 0) t.table.removeChild(t.table.childNodes[0]);
		// create rows
		for (var i = 0; i < data.length; ++i) {
			t.addRow(data[i].row_id, data[i].row_data, data[i].classname);
		}
	};
	/**
	 * Add a row
	 * @param {String} row_id identifier of the row
	 * @param {Object} row_data data of the row: {col_id:xxx,data_id:yyy,data:zzz}
	 * @param {String} classname if specified, gives a CSS class to the TR
	 */
	t.addRow = function(row_id, row_data, classname) {
		var tr = document.createElement("TR");
		if (classname) tr.className = classname;
		var click_listener = function() { t.onrowfocus.fire(tr); };
		listenEvent(tr, 'click', click_listener);
		tr.row_id = row_id;
		tr.row_data = row_data;
		if (t.selectable)
			tr.appendChild(t._createSelectionTD());
		for (var j = 0; j < t.columns.length; ++j) {
			var td = document.createElement("TD");
			tr.appendChild(td);
			var data = null;
			for (var k = 0; k < row_data.length; ++k)
				if (t.columns[j].id == row_data[k].col_id) { data = row_data[k]; break; }
			if (data == null)
				data = {data_id:null,data:"No data found for this column"};
			td.col_id = t.columns[j].id;
			td.data_id = data.data_id;
			if (hasClassName(t.columns[j].th, "first_in_container"))
				addClassName(td, "first_in_container");
			else if (hasClassName(t.columns[j].th, "last_in_container"))
				addClassName(td, "last_in_container");
			td.style.textAlign = t.columns[j].align;
			if (typeof data.data != 'undefined')
				t._createCell(t.columns[j], data.data, td, function(field,data){
					field.onfocus.addListener(function() { t.onrowfocus.fire(tr); });
					if (typeof data.data_display != 'undefined' && data.data_display)
						field.setDataDisplay(data.data_display, data.data_id);
				},data);
			if (typeof data.css != 'undefined' && data.css)
				addClassName(td, data.css);
			td.ondomremoved(function(td) { td.field = null; td.col_id = null; td.data_id = null; });
		}
		// check if sorted or not
		var sorted = false;
		for (var i = 0; i < t.columns.length; ++i)
			if (t.columns[i].sort_function && t.columns[i].sort_order != 3) {
				var new_data = null;
				for (var col = 0; col < row_data.length; ++col)
					if (row_data[col].col_id == t.columns[i].id) { new_data = row_data[col].data; break; }
				for (var row = 0; row < t.table.childNodes.length; ++row) {
					var rdata = t.table.childNodes[row].row_data;
					if (!rdata) continue;
					var data = null;
					for (var col = 0; col < rdata.length; ++col)
						if (rdata[col].col_id == t.columns[i].id) { data = rdata[col].data; break; }
					if (t.columns[i].sort_function(new_data, data) < 0) {
						sorted = true;
						t.table.insertBefore(tr, t.table.childNodes[row]);
						break;
					}
				}
				break;
			}
		if (!sorted)
			t.table.appendChild(tr);
		layout.changed(t.element);
		tr.ondomremoved(function(tr) {
			tr.row_data = null;
			unlistenEvent(tr, 'click', click_listener);
			click_listener = null;
			tr = null;
		});
		return tr;
	};
	/** Internal function to create the cell containing a checkbox or radio button
	 * @returns {Element} the TD
	 */
	t._createSelectionTD = function() {
		var td = document.createElement("TD");
		var cb = document.createElement("INPUT");
		if (t.selectable_unique) {
			cb.type = 'radio';
			if (!t.table.id) t.table.id = generateID();
			cb.name = t.table.id+'_selection';
		} else
			cb.type = 'checkbox';
		cb.style.marginTop = "0px";
		cb.style.marginBottom = "0px";
		cb.style.verticalAlign = "middle";
		cb.onchange = function(ev) {
			var tr = this.parentNode.parentNode;
			if (this.checked) addClassName(tr, "selected"); else removeClassName(tr, "selected");
			if (t.onrowselectionchange)
				t.onrowselectionchange(tr.row_id, this.checked);
			if (t.selectable_unique && this.checked) {
				for (var i = 0; i < t.table.childNodes.length; ++i)
					if (tr != t.table.childNodes[i] && hasClassName(t.table.childNodes[i], "selected")) {
						removeClassName(t.table.childNodes[i], "selected");
						if (t.onrowselectionchange)
							t.onrowselectionchange(t.table.childNodes[i].row_id, false);
					}
			}
			t._selectionChanged();
		};
		td.onclick = function(ev) {
			stopEventPropagation(ev, true);
		};
		td.appendChild(cb);
		return td;
	};
	
	/**
	 * A a row containing only a title, instead of cells
	 * @param {String} title the title
	 * @param {Object} style CSS styles to add to the TD containing the title
	 */
	t.addTitleRow = function(title, style) {
		var tr = document.createElement("TR");
		tr.title_row = true;
		tr.className = "title_row";
		var td = document.createElement("TD");
		tr.appendChild(td);
		td.colSpan = t.columns.length+(t.selectable ? 1 : 0);
		if (typeof title == 'string')
			td.appendChild(document.createTextNode(title));
		else
			td.appendChild(title);
		if (style)
			for (var name in style)
				td.style[name] = style[name];
		t.table.appendChild(tr);
		layout.changed(t.table);
		return tr;
	};
	
	/** Get number of rows
	 * @returns {Number} number of rows
	 */
	t.getNbRows = function() {
		return t.table.childNodes.length;
	};
	/** Get a row
	 * @param {Number} index index of the row
	 * @returns {Element} the TR
	 */
	t.getRow = function(index) {
		return t.table.childNodes[index];
	};
	/** Get the index of a row
	 * @param {Element} row the TR
	 * @returns {Number} its index
	 */
	t.getRowIndex = function(row) {
		for (var i = 0; i < t.table.childNodes.length; ++i)
			if (t.table.childNodes[i] == row) return i;
		return -1;
	};
	/** Get a row
	 * @param {String} id identifier of the row
	 * @returns {Element} the TR
	 */
	t.getRowFromID = function(id) {
		for (var i = 0; i < t.table.childNodes.length; ++i)
			if (t.table.childNodes[i].row_id == id) return t.table.childNodes[i];
		return null;
	};
	/** Get row index
	 * @param {String} row_id identifier of the row to search
	 * @returns {Number} its index
	 */
	t.getRowIndexById = function(row_id) {
		for (var i = 0; i < t.table.childNodes.length; ++i)
			if (t.table.childNodes[i].row_id == row_id) return i;
		return -1;
	};
	/** Get identifier of a row
	 * @param {Element} row the TR
	 * @returns {String} its id
	 */
	t.getRowID = function(row) {
		if (row == null) return null;
		if (typeof row.row_id == 'undefined') return null;
		return row.row_id;
	};
	/** Get identifier of a row
	 * @param {Number} row_index index of the row
	 * @returns {String} its identifier
	 */
	t.getRowIDFromIndex = function(row_index) {
		return t.getRowID(t.getRow(row_index));
	};
	
	/** Remove a row
	 * @param {Number} index index of the row to remove
	 */
	t.removeRowIndex = function(index) {
		t.table.removeChild(t.table.childNodes[index]);
		layout.changed(this.table);
	};
	/** Remove a row
	 * @param {Element} row the TR
	 */
	t.removeRow = function(row) {
		t.table.removeChild(row);
		layout.changed(this.table);
	};
	/** Remove all rows */
	t.removeAllRows = function() {
		while (t.table.childNodes.length > 0)
			t.table.removeChild(t.table.childNodes[0]);
		layout.changed(this.table);
	};
	
	/** Get the element contained in the given cell
	 * @param {Number} row row index
	 * @param {Number} col column index
	 * @returns {Element} the element in the cell
	 */
	t.getCellContent = function(row,col) {
		if (t.selectable) col++;
		var tr = t.table.childNodes[row];
		var td = tr.childNodes[col];
		return td.childNodes[0];
	};
	/** Get the typed field for the given cell
	 * @param {Number} row_index index of the row
	 * @param {Number} col_index index of the column
	 * @returns {typed_field} the field
	 */
	t.getCellField = function(row_index,col_index) {
		if (t.selectable) col_index++;
		var tr = t.table.childNodes[row_index];
		if (!tr) return null;
		if (col_index >= tr.childNodes.length) return null;
		var td = tr.childNodes[col_index];
		return td && td.field ? td.field : null;
	};
	/** Get the typed field for the given cell
	 * @param {String} row_id id of the row
	 * @param {String} col_id id of the column
	 * @returns {typed_field} the field
	 */
	t.getCellFieldById = function(row_id,col_id) {
		return t.getCellField(t.getRowIndexById(row_id), t.getColumnIndexById(col_id));	
	};
	/** Get the data of the given cell (data of its typed_field)
	 * @param {String} row_id id of the row
	 * @param {String} col_id id of the column
	 * @returns {Object} the data
	 */
	t.getCellData = function(row_id, col_id) {
		var f = t.getCellFieldById(row_id, col_id);
		if (!f) return null;
		return f.getCurrentData();
	};
	/** Set the data of the given cell (data of its typed_field)
	 * @param {String} row_id id of the row
	 * @param {String} col_id id of the column
	 * @param {Object} data the data
	 */
	t.setCellData = function(row_id, col_id, data) {
		var f = t.getCellFieldById(row_id, col_id);
		if (!f) return;
		f.setData(data);
	};
	/** Get data id of the given cell
	 * @param {Number} row index of the row
	 * @param {Number} col index of the column
	 * @returns {String} data id
	 */
	t.getCellDataId = function(row,col) {
		if (t.selectable) col++;
		var tr = t.table.childNodes[row];
		var td = tr.childNodes[col];
		return td.data_id;
	};
	/** Set data id of the given cell
	 * @param {Number} row index of the row
	 * @param {Number} col index of the column
	 * @param {String} data_id data id
	 */
	t.setCellDataId = function(row,col,data_id) {
		if (t.selectable) col++;
		var tr = t.table.childNodes[row];
		var td = tr.childNodes[col];
		td.data_id = data_id;
	};
	
	/** Get the index of the row containing the given element
	 * @param {Element} element the element to search
	 * @returns {Number} index of the row containing it, or -1 if not found
	 */
	t.getContainingRowIndex = function(element) {
		while (element && element != document.body) {
			if (element.nodeName == "TD" && element.col_id) {
				var tr = element.parentNode;
				for (var i = 0; i < t.table.childNodes.length; ++i)
					if (t.table.childNodes[i] == tr) return i;
			}
			element = element.parentNode;
		}
		return -1;
	};
	/** Get the row containing the given element
	 * @param {Element} element the element to search
	 * @returns {Element} the TR
	 */
	t.getContainingRow = function(element) {
		while (element && element != document.body) {
			if (element.nodeName == "TD" && element.col_id) {
				var tr = element.parentNode;
				for (var i = 0; i < t.table.childNodes.length; ++i)
					if (t.table.childNodes[i] == tr) return tr;
			}
			element = element.parentNode;
		}
		return null;
	};
	/** Get the id of the row and column containing the given element
	 * @param {Element} element the element to search
	 * @returns {Object} {col_id:xxx,row_id:yyy} or null if not found
	 */
	t.getContainingRowAndColIds = function(element) {
		while (element && element != document.body) {
			if (element.nodeName == "TD" && element.col_id) {
				var tr = element.parentNode;
				for (var i = 0; i < t.table.childNodes.length; ++i)
					if (t.table.childNodes[i] == tr) {
						return {col_id:element.col_id, row_id:tr.row_id};
					}
			}
			element = element.parentNode;
		}
		return null;
	};
	
	/**
	 * Reset everything in the grid: the data, rows, columns...
	 */
	t.reset = function() {
		// remove data rows
		t.table.removeAllChildren();
		// remove columns
		for (var i = 1; i < t.header_rows.length; ++i)
			t.header_rows[i].parentNode.removeChild(t.header_rows[i]);
		while (t.header_rows[0].childNodes.length > 0) t.header_rows[0].removeChild(t.header_rows[0].childNodes[0]);		
		while (t.colgroup.childNodes.length > 0) t.colgroup.removeChild(t.colgroup.childNodes[0]);
		t.columns = [];
		t.setSelectable(!t.selectable);
		t.setSelectable(!t.selectable);
		layout.changed(this.table);
	};
	
	/** Show a loading screen on top of the grid */
	t.startLoading = function() {
		if (t._loading_hidder) return;
		if (!t.table) return;
		t._loading_hidder = new LoadingHidder(t.table);
		t._loading_hidder.setContent("<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Loading data...");
	};
	/** Remove the loading screen on top of the grid */
	t.endLoading = function() {
		if (!t._loading_hidder) return;
		t._loading_hidder.remove();
		t._loading_hidder = null;
	};
	
	/**
	 * Apply filters: show/hide rows. This cannot be used if an external filtering is used.
	 */
	t.applyFilters = function() {
		var selection_changed = false;
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var row = t.table.childNodes[i];
			var hidden = false;
			for (var j = 0; j < t.columns.length; ++j) {
				var col = t.columns[j];
				if (!col.filtered) continue;
				var cell = row.childNodes[j];
				var value = cell.field.getCurrentData();
				var found = false;
				for (var k = 0; k < col.filter_values.length; ++k)
					if (col.filter_values[k] == value) { found = true; break; }
				if (!found) { hidden = true; break; }
			}
			row.style.visibility = hidden ? "hidden" : "visible";
			row.style.position = hidden ? "absolute" : "static";
			row.style.top = "-10000px";
			// TODO color?
			// make sure it is not selected anymore
			if (t.selectable) {
				var td = row.childNodes[0];
				var cb = td.childNodes[0];
				if (cb.checked) {
					cb.checked = '';
					cb.onchange();
					selection_changed = true;
				}
			}
		}
		if (selection_changed)
			t._selectionChanged();
	};
	
	/**
	 * Print the content of the grid
	 * @param {String} template name of the template to use
	 * @param {Object} template_parameters parameters for the template
	 * @param {Function} onprintready called when the print preview is ready
	 */
	t.print = function(template,template_parameters,onprintready) {
		var container = document.createElement("DIV");
		var table = document.createElement("TABLE");
		table.className = "grid";
		container.appendChild(table);
		var thead = document.createElement("THEAD");
		table.appendChild(thead);
		var tr, td;
		for (var i = 0; i < t.header_rows.length; ++i) {
			thead.appendChild(tr = document.createElement("TR"));
			for (var j = 0; j < t.header_rows[i].childNodes.length; ++j) {
				var th = t.header_rows[i].childNodes[j];
				if (!th.col) continue;
				tr.appendChild(td = document.createElement("TH"));
				td.colSpan = th.colSpan;
				td.rowSpan = th.rowSpan;
				if (th.col.isHidden())
					td.style.display = "none";
				else {
					if (th.col.text_title)
						td.appendChild(document.createTextNode(th.col.text_title));
					else if (th.col.title instanceof Element)
						td.innerHTML = th.col.title.outerHTML;
					else
						td.innerHTML = th.col.title;
					td.style.textAlign = th.style.textAlign;
				}
			}
		}
		var tbody = document.createElement("TBODY");
		table.appendChild(tbody);
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var ttr = t.table.childNodes[i];
			tbody.appendChild(tr = document.createElement("TR"));
			tr.className = ttr.className;
			for (var j = 0; j < ttr.childNodes.length; ++j) {
				if (j == 0 && t.selectable) continue;
				var cell = ttr.childNodes[j];
				tr.appendChild(td = document.createElement("TD"));
				td.className = cell.className;
				var col = t.getColumnById(cell.col_id);
				if (col && col.isHidden())
					td.style.display = "none";
				if (!cell.field) {
					td.innerHTML = cell.innerHTML;
					if (cell.style && cell.style.textAlign)
						td.style.textAlign = cell.style.textAlign;
				} else if (cell.col_id) {
					var f = new window[col.field_type](cell.field.getCurrentData(),false,col.field_args);
					td.appendChild(f.getHTMLElement());
					td.style.textAlign = col.align;
					f.fillWidth();
				}
			}
		}
		printContent(container, onprintready, null, template, template_parameters);
	};
	
	/**
	 * Display the menu to select the columns to show
	 * @param {Element} below_this_element the menu will be displayed below this element
	 * @param {Function} onchange called when selection of columns changed
	 */
	t.showHideColumnsMenu = function(below_this_element, onchange) {
		require("context_menu.js", function() {
			var menu = new context_menu();
			for (var i = 0; i < t.header_rows[0].childNodes.length; ++i)
				t._buildColumnsChooser(menu, t.header_rows[0].childNodes[i].col, 0, onchange);
			menu.showBelowElement(below_this_element);
		});
	};
	/**
	 * Internal function to build the menu to choose columns
	 * @param {context_menu} menu the menu to fill
	 * @param {GridColumn} col the column
	 * @param {Number} indent indentation
	 * @param {Function} onchange function to be called when selection change
	 */
	t._buildColumnsChooser = function(menu, col, indent, onchange) {
		if (col.title == "") return;
		var div = document.createElement("DIV");
		div.style.fontSize = "9pt";
		div.style.marginLeft = indent+'px';
		var cb = document.createElement("INPUT");
		cb.type = 'checkbox';
		cb.style.verticalAlign = "middle";
		cb.style.marginRight = "3px";
		cb.checked = col.isHidden() ? "" : "checked";
		cb.disabled = col.canBeHidden() ? "" : "disabled";
		div.appendChild(cb);
		div.appendChild(document.createTextNode(col.text_title ? col.text_title : col.title));
		menu.addItem(div, true);
		var sub_cb = [];
		if (col instanceof GridColumnContainer) {
			for (var i = 0; i < col.sub_columns.length; ++i)
				sub_cb.push(t._buildColumnsChooser(menu, col.sub_columns[i], indent + 20, onchange));
		}
		cb.onchange = function(ev,recurs) {
			if (col instanceof GridColumnContainer) {
				for (var i = 0; i < sub_cb.length; ++i) {
					sub_cb[i].checked = this.checked ? "checked" : "";
					sub_cb[i].onchange(ev,true);
				}
			} else {
				if (this.checked)
					col.hide(false);
				else
					col.hide(true);
			}
			if (!recurs && onchange) onchange();
		};
		return cb;
	};
	/**
	 * Save the state of the grid: list of columns shown/hidden
	 */
	t.saveState = function() {
		if (!window.localStorage || !window.JSON) return; // HTML5 not supported
		var state = { hidden_columns: [] };
		for (var i = 0; i < this.columns.length; ++i)
			if (this.columns[i].isHidden())
				state.hidden_columns.push(this.columns[i].id);
		var stored = window.localStorage.getItem("grid_state");
		if (!stored) stored = {};
		else stored = JSON.parse(stored);
		stored[location.pathname] = state;
		window.localStorage.setItem("grid_state", JSON.stringify(stored));
	};
	/**
	 * Load the state of the grid, previously saved
	 */
	t.loadState = function() {
		if (!window.localStorage || !window.JSON) return; // HTML5 not supported
		var stored = window.localStorage.getItem("grid_state");
		if (!stored) return; // nothing stored
		stored = JSON.parse(stored);
		if (!stored[location.pathname]) return; // nothing stored for this URL
		var state = stored[location.pathname];
		if (state["hidden_columns"])
			for (var i = 0; i < state.hidden_columns.length; ++i) {
				var col = this.getColumnById(state.hidden_columns[i]);
				if (!col) return;
				col.hide(true);
			}
	};
	
	/* --- internal functions --- */
	/** Create the grid */
	t._createTable = function() {
		t.grid_element = document.createElement("DIV");
		var table = document.createElement('TABLE');
		table.style.width = "100%";
		t.grid_element.appendChild(table);
		t.colgroup = document.createElement('COLGROUP');
		table.appendChild(t.colgroup);
		t.thead = document.createElement('THEAD');
		t.header_rows = [];
		t.header_rows.push(document.createElement('TR'));
		t.thead.appendChild(t.header_rows[0]);
		table.appendChild(t.thead);
		t.table = document.createElement('TBODY');
		table.appendChild(t.table);
		t.element.appendChild(t.grid_element);
		table.className = "grid";
		this._table = table;
		// listen to keys
		listenEvent(window,'keyup',t._keyListener);
	};
	/** Listen to keys pressed to navigate in the grid cells
	 * @param {Event} ev the event
	 */
	t._keyListener = function(ev) {
		var getCell = function(element) {
			while (element && element != document.body) {
				if (element.nodeName == 'TD' && element.field)
					return element;
				element = element.parentNode;
			}
			return null;
		};
		var e = getCompatibleKeyEvent(ev);
		if (e.isArrowUp) {
			var cell = getCell(ev.target);
			getCell = null;
			if (!cell) return;
			var row = cell.parentNode;
			var target_row = row.previousSibling;
			if (!target_row) return;
			var index;
			for (index = row.childNodes.length-1; index >= 0; --index) if (row.childNodes[index] == cell) break;
			if (index < 0) return;
			var target_cell = target_row.childNodes[index];
			if (!target_cell || !target_cell.field) return;
			target_cell.field.focus();
			return;
		}
		if (e.isArrowDown) {
			var cell = getCell(ev.target);
			getCell = null;
			if (!cell) return;
			var row = cell.parentNode;
			var target_row = row.nextSibling;
			if (!target_row) return;
			var index;
			for (index = row.childNodes.length-1; index >= 0; --index) if (row.childNodes[index] == cell) break;
			if (index < 0) return;
			var target_cell = target_row.childNodes[index];
			if (!target_cell || !target_cell.field) return;
			target_cell.field.focus();
			return;
		}
		getCell = null;
	};
	/**
	 * Create a cell
	 * @param {GridColumn} column the column
	 * @param {Object} data the data to give to the typed_field
	 * @param {Element} parent the TD
	 * @param {Function} ondone function to call when the cell is ready
	 * @param {Object} ondone_param parameter to give to the ondone function
	 */
	t._createCell = function(column, data, parent, ondone, ondone_param) {
		parent.style.display = column.hidden ? "none" : "";
		t._cells_loading++;
		t._createField(column, column.field_type, column.editable, column.onchanged, column.onunchanged, column.field_args, parent, data, function(field) {
			field.onchange.addListener(function() {
				var tr = parent;
				while (tr.nodeName != 'TR') tr = tr.parentNode;
				if (tr.row_data)
					for (var k = 0; k < tr.row_data.length; ++k)
						if (column.id == tr.row_data[k].col_id) { tr.row_data[k].data = field.getCurrentData(); tr.row_data[k].original_data = field.originalData; break; }
			});
			parent.field = field;
			field.grid_column_id = column.id;
			if (ondone) ondone(field, ondone_param);
			t._cells_loading--;
			t._checkLoaded();
			t.oncellcreated.fire({parent:parent,field:field,column:column,data:data});
		});
	},
	/** Create the typed_field in a cell
	 * @param {GridColumn} column the column
	 * @param {String} field_type class of the typed_field to instantiate
	 * @param {Boolean} editable true to make the typed_field editable
	 * @param {Function} onchanged function to be called when the value changed
	 * @param {Function} onunchanged function to be called when the value comes back to its original value
	 * @param {Object} field_args configuration of the typed_field
	 * @param {Element} parent the container of the field
	 * @param {Object} data data to give to the typed_field
	 * @param {Function} ondone function called when the field has been created and ready
	 */
	t._createField = function(column, field_type, editable, onchanged, onunchanged, field_args, parent, data, ondone) {
		require([["typed_field.js",field_type+".js"]], function() {
			layout.modifyDOM(function() {
				var f = new window[field_type](data, editable, field_args);
				if (onchanged) f.ondatachanged.addListener(onchanged);
				if (onunchanged) f.ondataunchanged.addListener(onunchanged);
				parent.appendChild(f.getHTMLElement());
				ondone(f);
				setTimeout(function() {
					layout.modifyDOM(function() {
						column._cache_fw = f.fillWidth(column._cache_fw);
						layout.changed(parent);
					});
				},1);
			});
		});
	};
	
	/**
	 * Export data of the grid
	 * @param {String} format format of the file to generate
	 * @param {String} filename name of the file to generate
	 * @param {String} sheetname of the file is an Excel file, this is the name of the sheet in the generated Excel file
	 * @param {Array} excluded_columns list of columns' id to do not export
	 */
	t.exportData = function(format,filename,sheetname,excluded_columns) {
		if (!excluded_columns) excluded_columns = [];
		else for (var i = 0; i < excluded_columns.length; ++i) {
			excluded_columns[i] = t.getColumnById(excluded_columns[i]);
			if (excluded_columns[i] == null) {
				excluded_columns.splice(i,1);
				i--;
			}
		}
		var ex = {format:format,name:filename,sheets:[]};
		var sheet = {name:sheetname,rows:[]};
		ex.sheets.push(sheet);
		// headers
		for (var i = 0; i < t.header_rows.length; ++i) {
			var row = [];
			for (var j = 0; j < t.header_rows[i].childNodes.length; ++j) {
				var th = t.header_rows[i].childNodes[j];
				var excluded = false;
				for (var k = 0; k < excluded_columns.length; ++k) if (excluded_columns[k].th == th) { excluded = true; break; }
				var cell = {};
				if (excluded)
					cell.value = "";
				else {
					if (th.rowSpan && th.rowSpan > 1) cell.rowSpan = th.rowSpan;
					if (th.colSpan && th.colSpan > 1) cell.colSpan = th.colSpan;
					if (th.col && th.col.text_title)
						cell.value = th.col.text_title;
					else {
						if (th.col.span_actions)
							th.removeChild(th.col.span_actions);
						cell.value = th.innerHTML;
						if (th.col.span_actions)
							th.appendChild(th.col.span_actions);
					}
					cell.style = {fontWeight:'bold',textAlign:'center'};
				}
				row.push(cell);
			}
			sheet.rows.push(row);
		}
		// rows
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var row = [];
			for (var j = 0; j < t.table.childNodes[i].childNodes.length; ++j) {
				var cell = {};
				var excluded = false;
				if (!t.table.childNodes[i].title_row)
					for (var k = 0; k < excluded_columns.length; ++k) if (j < t.columns.length && excluded_columns[k].id == t.columns[j].id) { excluded = true; break; }
				if (excluded)
					cell.value = "";
				else {
					var td = t.table.childNodes[i].childNodes[j];
					if (td.rowSpan && td.rowSpan > 1) cell.rowSpan = td.rowSpan;
					if (td.colSpan && td.colSpan > 1) cell.colSpan = td.colSpan;
					if (td.field) td.field.exportCell(cell);
					else cell.value = td.innerHTML;
					if (!cell.style) cell.style = {};
					cell.style.textAlign = t.columns[j].align;
				}
				row.push(cell);
			}
			sheet.rows.push(row);
		}
		// export
		var form = document.createElement("FORM");
		var input = document.createElement("INPUT");
		form.appendChild(input);
		form.action = "/dynamic/lib_php_excel/service/create";
		form.method = 'POST';
		input.type = 'hidden';
		input.name = 'input';
		input.value = service.generateInput(ex);
		if (t._download_frame) document.body.removeChild(t._download_frame);
		var frame = document.createElement("IFRAME");
		frame.style.position = "absolute";
		frame.style.top = "-10000px";
		frame.style.visibility = "hidden";
		frame.name = "grid_download";
		document.body.appendChild(frame);
		form.target = "grid_download";
		document.body.appendChild(form);
		form.submit();
		t._download_frame = frame;
		window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_INFO,"Your file is being generated, and the download will start soon...",[{action:"close"}],5000));
	};

	/** Number of columns still loading */
	t._columns_loading = 0;
	/** Number of cells still loading */
	t._cells_loading = 0;
	/** List of functions to call when everything is loaded and ready */
	t._loaded_listeners = [];
	/** Internal function called when something has been loaded, to check if everything is already loaded */
	t._checkLoaded = function() {
		if (t._columns_loading == 0 && t._cells_loading == 0) {
			var list = t._loaded_listeners;
			t._loaded_listeners = [];
			for (var i = 0; i < list.length; ++i) list[i]();
		}
	};
	
	/* initialization */
	t._createTable();
}
