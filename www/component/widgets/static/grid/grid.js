if (typeof require != 'undefined') {
	require("typed_field.js",function(){
		require("field_blank.js");
	});
	require("dragndrop.js");
	theme.css("grid.css");
}

function GridColumnAction(icon,onclick,tooltip) {
	this.icon = icon;
	this.onclick = onclick;
	this.tooltip = tooltip;
}

function GridColumnContainer(title, sub_columns, attached_data) {
	this.title = title;
	this.sub_columns = sub_columns;
	this.attached_data = attached_data;
	this.th = document.createElement("TH");
	this.th.innerHTML = title;
	this.th.col = this;
	this._updateLevels = function() {
		this.nb_columns = 0;
		this.levels = 2;
		for (var i = 0; i < this.sub_columns.length; ++i) {
			this.sub_columns[i].parent_column = this;
			if (this.sub_columns[i] instanceof GridColumn) {
				this.nb_columns++;
				continue;
			}
			if (this.sub_columns[i].levels > this.levels+1) this.levels = this.sub_columns[i].levels+1;
			this.nb_columns += sub_columns[i].nb_columns;
		}
		this.th.colSpan = this.nb_columns;
		if (this.parent_column) this.parent_column._updateLevels();
	};
	this._updateLevels();
	this.getNbFinalColumns = function() {
		var nb = 0;
		for (var i = 0; i < this.sub_columns.length; ++i) {
			if (this.sub_columns[i] instanceof GridColumnContainer)
				nb += this.sub_columns[i].getNbFinalColumns();
			else
				nb++;
		}
	};
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
	this.addSubColumn = function(final_col) {
		this.sub_columns.push(final_col);
		this._updateLevels();
		this.grid._subColumnAdded(this, final_col);
	};
}

function GridColumn(id, title, width, align, field_type, editable, onchanged, onunchanged, field_args, attached_data) {
	// check parameters
	if (!id) id = generateID();
	if (!field_type) field_type = "field_text";
	require(field_type+".js");
	if (!field_args) field_args = {};
	// put values in the object
	this.id = id;
	this.title = title;
	this.width = width;
	this.align = align ? align : "left";
	this.field_type = field_type;
	require([["typed_field.js",field_type+".js"]]);
	this.editable = editable;
	this.onchanged = onchanged;
	this.onunchanged = onunchanged;
	this.field_args = field_args;
	this.attached_data = attached_data;
	// init
	this.th = document.createElement('TH');
	this.th.rowSpan = 1;
	this.th.col = this;
	this.col = document.createElement('COL');
	this.onclick = new Custom_Event();
	this.actions = [];
	
	this.toggleEditable = function() {
		this.editable = !this.editable;
		var index = this.grid.columns.indexOf(this);
		if (this.grid.selectable) index++;
		for (var i = 0; i < this.grid.table.childNodes.length; ++i) {
			var row = this.grid.table.childNodes[i];
			var td = row.childNodes[index];
			if (td.field)
				td.field.setEditable(this.editable);
		}
		this._refresh_title();
	};
	
	this.addAction = function(action) {
		this.actions.push(action);
		this._refresh_title();
	};

	this.addSorting = function(sort_function) {
		this.sort_order = 3; // not sorted
		this.sort_function = sort_function;
		var t=this;
		this.onclick.add_listener(function(){
			var new_sort = t.sort_order == 1 ? 2 : 1;
			t._onsort(new_sort);
		});
	};
	this.addExternalSorting = function(handler) {
		this.sort_order = 3; // not sorted
		this.sort_handler = handler;
		this.onclick.add_listener(function(){
			var new_sort = t.sort_order == 1 ? 2 : 1;
			t._onsort(new_sort);
		});
	};
	
	this.addFiltering = function() {
		var url = get_script_path("grid.js");
		var t=this;
		var a = new GridColumnAction(url+"/filter.gif",function(ev,a,col){
			if (t.filtered) {
				t.filtered = false;
				a.icon = url+"/filter.gif";
				t._refresh_title();
				t.grid.apply_filters();
			} else {
				if (t.grid.table.childNodes.length == 0) return;
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
						t.grid._create_field(t.field_type, false, null, null, t.field_args, item, values[i], function(input) {
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
						t._refresh_title();
						t.filter_values = [];
						for (var i = 0; i < checkboxes.length; ++i)
							if (checkboxes[i].checked)
								t.filter_values.push(values[i]);
						if (t.filter_values.length == checkboxes.length) {
							t.filter_values = null;
							t.filtered = false;
							a.icon = url+"/filter.gif";
							t._refresh_title();
						}
						t.grid.apply_filters();
					};
					menu.showBelowElement(a.element);
				});
			}
			stopEventPropagation(ev);
			return false;
		});
		this.addAction(a);
	};
	
	this._refresh_title = function() {
		var url = get_script_path("grid.js");
		var t=this;
		this.th.removeAllChildren();
		if (title instanceof Element)
			this.th.appendChild(title);
		else
			this.th.innerHTML = title;
		var span = document.createElement("SPAN");
		span.style.whiteSpace = 'nowrap';
		this.th.appendChild(span);
		if (this.sort_order) {
			var img;
			switch (this.sort_order) {
			case 1: // ascending
				img = document.createElement("IMG");
				img.src = url+"/arrow_up_10.gif";
				img.style.verticalAlign = "middle";
				img.style.cursor = "pointer";
				tooltip(img, "Sort by descending order (currently ascending)");
				img.onclick = function() { t._onsort(2); };
				span.appendChild(img);
				break;
			case 2: // descending
				img = document.createElement("IMG");
				img.src = url+"/arrow_down_10.gif";
				img.style.verticalAlign = "middle";
				img.style.cursor = "pointer";
				tooltip(img, "Sort by ascending order (currently descending)");
				img.onclick = function() { t._onsort(1); };
				span.appendChild(img);
				break;
			case 3: // not sorted yet
				var h = function() { t._onsort(1); };
				img = document.createElement("IMG");
				img.src = url+"/arrow_up_10.gif";
				img.style.verticalAlign = "middle";
				img.style.cursor = "pointer";
				tooltip(img, "Sort by descending order");
				img.onclick = h;
				span.appendChild(img);
				img = document.createElement("IMG");
				img.src = url+"/arrow_down_10.gif";
				img.style.verticalAlign = "middle";
				img.style.cursor = "pointer";
				tooltip(img, "Sort by ascending order");
				img.onclick = h;
				span.appendChild(img);
				break;
			}
		}
		for (var i = 0; i < this.actions.length; ++i) {
			var img = document.createElement("IMG");
			img.src = this.actions[i].icon;
			img.style.verticalAlign = "middle";
			img.style.cursor = "pointer";
			img.data = this.actions[i];
			if (this.actions[i].tooltip)
				tooltip(img, this.actions[i].tooltip);
			img.onclick = function(ev) { this.data.onclick(ev, this.data, t); };
			this.actions[i].element = img;
			span.appendChild(img);
		}
		layout.invalidate(this.th);
	};
	this._onsort = function(sort_order) {
		// cancel sorting of other columns
		for (var i = 0; i < this.grid.columns.length; ++i) {
			var col = this.grid.columns[i];
			if (col == this) continue;
			if (col.sort_order) {
				col.sort_order = 3;
				col._refresh_title();
			}
		}
		if (this.sort_function) {
			// remove all rows
			var rows = [];
			while (this.grid.table.childNodes.length > 0) {
				rows.push(this.grid.table.childNodes[0]);
				this.grid.table.removeChild(this.grid.table.childNodes[0]);
			}
			// call sort function
			var t=this;
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
			// put back rows in the new order
			for (var i = 0; i < rows.length; ++i)
				this.grid.table.appendChild(rows[i]);
		} else
			this.sort_handler(sort_order);
		
		this.sort_order = sort_order;
		this._refresh_title();
	};
}

function grid(element) {
	if (typeof element == 'string') element = document.getElementById(element);
	var t = this;
	t.element = element;
	t.columns = [];
	t.selectable = false;
	t.url = get_script_path("grid.js");
	t.onrowselectionchange = null;
	t.oncellcreated = new Custom_Event();
	
	t.addColumnContainer = function(column_container, index) {
		// if more levels, we add new rows in the header
		while (t.header_rows.length < column_container.levels)
			t._addHeaderLevel();
		// if less levels, set the rowSpan of first level
		if (column_container.levels < t.header_rows.length)
			column_container.th.rowSpan = t.header_rows.length-column_container.levels+1;
		t._addColumnContainer(column_container, 0, index);
	};
	t._addHeaderLevel = function() {
		// new level needed
		// apppend a TR
		var tr = document.createElement("TR");
		t.header_rows.push(tr);
		t.header_rows[0].parentNode.appendChild(tr);
		// increase rowSpan of first row
		for (var i = 0; i < t.header_rows[0].childNodes.length; i++)
			t.header_rows[0].childNodes[i].rowSpan++;
	};
	t._addColumnContainer = function(container, level, index) {
		container.grid = this;
		if (typeof index != 'undefined' && level == 0) {
			// this is first level, we need to calculate what is the real index in first TR
			var real_index = t.selectable ? 1 : 0;
			while (index > 0) {
				index -= t.header_rows[0].childNodes[real_index].colSpan;
				real_index++;
			}
			if (index < 0) index = undefined;
			else if (index > t.header_rows[0].childNodes.length-1) index = undefined;
			else index = real_index;
		} else index = undefined;
		if (typeof index != 'undefined')
			t.header_rows[level].insertBefore(container.th, t.header_rows[level].childNodes[index]);
		else
			t.header_rows[level].appendChild(container.th);
		for (var i = 0; i < container.sub_columns.length; ++i) {
			if (container.sub_columns[i] instanceof GridColumnContainer) {
				t._addColumnContainer(container.sub_columns[i], level+1);
				continue;
			}
			t._addFinalColumn(container.sub_columns[i], level+1);
		}
	};
	t._addFinalColumn = function(col, level, index) {
		if (typeof index != 'undefined') {
			if (index < 0) index = undefined;
			else if (index >= t.columns.length) index = undefined;
		}
		col.grid = this;
		if (typeof index == 'undefined') {
			t.columns.push(col);
			t.colgroup.appendChild(col.col);
			t.header_rows[level].appendChild(col.th);
		} else {
			t.columns.splice(index,0,col);
			t.colgroup.insertBefore(col.col, t.colgroup.childNodes[t.selectable ? index +1 : index]);
			// need to calculate the real index
			var i;
			for (i = level == 0 && t.selectable ? 1 : 0; i < t.header_rows[level].childNodes.length && index > 0; ++i) {
				var th = t.header_rows[level].childNodes[i];
				if (th.col instanceof GridColumnContainer) index -= th.col.getNbFinalColumns();
				else index--;
			}
			if (i >= t.header_rows[level].childNodes.length)
				t.header_rows[level].appendChild(col.th);
			else
				t.header_rows[level].insertBefore(col.th, t.header_rows[level].childNodes[i]);
		}
		col._refresh_title();
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
				t._create_cell(col, null, td);
			}
		}
		layout.invalidate(this.table);
	};
	t._subColumnAdded = function(container, final_col) {
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
		// finally, add the final column
		var list = container.getFinalColumns();
		var last_index = this.getColumnIndex(list[list.length-2]);
		var level;
		for (level = 0; level < t.header_rows.length-1; level++) {
			if (t.header_rows[level] == container.th.parentNode) break;
		}
		t._addFinalColumn(final_col, level+1, last_index+1);
	};
	
	t.addColumn = function(column, index) {
		column.th.rowSpan = t.header_rows.length;
		t._addFinalColumn(column,0, index);
	};
	t.getNbColumns = function() { return t.columns.length; };
	t.getColumn = function(index) { return t.columns[index]; };
	t.getColumnById = function(id) {
		for (var i = 0; i < t.columns.length; ++i)
			if (t.columns[i].id == id)
				return t.columns[i];
		return null;
	};
	t.getColumnByAttachedData = function(data) {
		for (var i = 0; i < t.columns.length; ++i)
			if (t.columns[i].attached_data == data)
				return t.columns[i];
		return null;
	};
	t.getColumnContainerByAttachedData = function(data) {
		for (var i = 0; i < t.columns.length; ++i)
			if (t.columns[i].parent_column) {
				var c = t._getColumnContainerByAttachedData(t.columns[i].parent_column, data);
				if (c) return c;
			}
		return null;
	};
	t._getColumnContainerByAttachedData = function(container, data) {
		if (container.attached_data == data) return container;
		if (!container.parent_column) return null;
		t._getColumnContainerByAttachedData(container.parent_column, data);
	};
	t.getColumnIndex = function(col) { return t.columns.indexOf(col); };
	t.removeColumn = function(index) {
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
				p.sub_columns.remove(c);
				c.th.parentNode.removeChild(c.th);
				if (p.sub_columns.length > 0) break; // still something
				// no more sub column -> remove it
				p.th.parentNode.removeChild(p.th);
				p = p.parent_column;
				c = p;
			}
		}
		t.apply_filters();
		layout.invalidate(this.table);
	};
	t.rebuildColumn = function(column) {
		column._refresh_title();
		var index = t.columns.indexOf(column);
		if (t.selectable) index++;
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var row = t.table.childNodes[i];
			var td = row.childNodes[index];
			if (td.field) {
				var data = td.field.getCurrentData();
				td.removeAllChildren();
				t._create_cell(column, data, td);
				td.style.textAlign = column.align;
			}
		}
	};
	
	t.setSelectable = function(selectable) {
		if (t.selectable == selectable) return;
		t.selectable = selectable;
		if (selectable) {
			var th = document.createElement('TH');
			var cb = document.createElement("INPUT");
			cb.type = 'checkbox';
			cb.onchange = function() { if (this.checked) t.selectAll(); else t.unselectAll(); };
			th.appendChild(cb);
			var col = document.createElement('COL');
			col.width = 20;
			if (t.header_rows[0].childNodes.length == 0) {
				t.header_rows[0].appendChild(th);
				t.colgroup.appendChild(col);
			} else {
				t.header_rows[0].insertBefore(th, t.header_rows[0].childNodes[0]);
				t.colgroup.insertBefore(col, t.colgroup.childNodes[0]);
			}
			th.rowSpan = t.header_rows.length;
		} else if (t.header_rows[0].childNodes.length > 0) {
			t.header_rows[0].removeChild(t.header_rows[0].childNodes[0]);
			t.colgroup.removeChild(t.colgroup.childNodes[0]);
		}
		layout.invalidate(this.table);
	};
	t.selectAll = function() {
		if (!t.selectable) return;
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var tr = t.table.childNodes[i];
			if (tr.style.visibility == "hidden") continue; // do not select filtered/hidden
			var td = tr.childNodes[0];
			var cb = td.childNodes[0];
			if (cb.disabled) continue; //do not select if the checkbox is disabled
			cb.checked = 'checked';
			cb.onchange();
		}
		t._selection_changed();
	};
	t.unselectAll = function() {
		if (!t.selectable) return;
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var tr = t.table.childNodes[i];
			var td = tr.childNodes[0];
			var cb = td.childNodes[0];
			if (cb.disabled) continue; //do not unselect if the checkbox is disabled
			cb.checked = '';
			if (cb.onchange) cb.onchange();
		}
		t._selection_changed();
	};
	t._selection_changed = function() {
		if (t.onselect) {
			t.onselect(t.getSelectionByIndexes(), t.getSelectionByRowId());
		}
	};
	t.onselect = null;
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
	t.isSelected = function(index) {
		if (!t.selectable) return false;
		var tr = t.table.childNodes[index];
		var td = tr.childNodes[0];
		var cb = td.childNodes[0];
		return cb.checked;
	};
	t.selectByIndex = function(index, selected) {
		if (!t.selectable) return;
		var tr = t.table.childNodes[index];
		var td = tr.childNodes[0];
		var cb = td.childNodes[0];
		cb.checked = selected ? 'checked' : '';
		tr.className = selected ? "selected" : "";
	};
	t.disableByIndex = function(index, disabled){
		if(!t.selectable) return;
		var tr = t.table.childNodes[index];
		var td = tr.childNodes[0];
		var cb = td.childNodes[0];
		cb.disabled = disabled;
	};
	t.selectByRowId = function(row_id, selected) {
		if (!t.selectable) return;
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var tr = t.table.childNodes[i];
			if (tr.row_id != row_id) continue;
			var td = tr.childNodes[0];
			var cb = td.childNodes[0];
			cb.checked = selected ? 'checked' : '';
			tr.className = selected ? "selected" : "";
			break;
		}
	};

	/**
	 * Change the data in the grid.
	 * data is an array, each element representing an entry/row.
	 * Each entry is an object {row_id:xxx,row_data:[]} where the row_id can be used later to identify the row or to attach data to the row. 
	 * Each row_data element is an object {col_id:xxx,data_id:yyy,data:zzz} where the data is given to the typed field, col_id identifies the column and data_id can be used later to identify the data or to attach information to the data
	 */
	t.setData = function(data) {
		// empty table
		t.unselectAll();
		while (t.table.childNodes.length > 0) t.table.removeChild(t.table.childNodes[0]);
		// create rows
		for (var i = 0; i < data.length; ++i) {
			t.addRow(data[i].row_id, data[i].row_data);
		}
	};
	t.addRow = function(row_id, row_data) {
		var tr = document.createElement("TR");
		tr.row_id = row_id;
		if (t.selectable) {
			var td = document.createElement("TD");
			tr.appendChild(td);
			var cb = document.createElement("INPUT");
			cb.type = 'checkbox';
			cb.style.marginTop = "0px";
			cb.style.marginBottom = "0px";
			cb.style.verticalAlign = "middle";
			cb.onchange = function(ev) {
				this.parentNode.parentNode.className = this.checked ? "selected" : "";
				if (t.onrowselectionchange)
					t.onrowselectionchange(tr.row_id, this.checked);
				t._selection_changed();
			};
			td.appendChild(cb);
		}
		for (var j = 0; j < t.columns.length; ++j) {
			var td = document.createElement("TD");
			tr.appendChild(td);
			var data = null;
			for (var k = 0; k < row_data.length; ++k)
				if (t.columns[j].id == row_data[k].col_id) { data = row_data[k]; break; }
			if (data == null)
				data = {data_id:null,data:"No data found for this colum"};
			td.col_id = t.columns[j].id;
			td.data_id = data.data_id;
			td.style.textAlign = t.columns[j].align;
			if (typeof data.data != 'undefined')
				t._create_cell(t.columns[j], data.data, td);
			if (typeof data.css != 'undefined' && data.css)
				td.className = data.css;
		}
		t.table.appendChild(tr);
		layout.invalidate(t.table);
		return tr;
	};
	
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
		layout.invalidate(t.table);
		return tr;
	};
	
	t.getNbRows = function() {
		return t.table.childNodes.length;
	};
	t.getRow = function(index) {
		return t.table.childNodes[index];
	};
	t.getRowIndex = function(row) {
		for (var i = 0; i < t.table.childNodes.length; ++i)
			if (t.table.childNodes[i] == row) return i;
		return -1;
	};
	t.getRowFromID = function(id) {
		for (var i = 0; i < t.table.childNodes.length; ++i)
			if (t.table.childNodes[i].row_id == id) return t.table.childNodes[i];
		return null;
	};
	
	t.removeRowIndex = function(index) {
		t.table.removeChild(t.table.childNodes[index]);
		layout.invalidate(this.table);
	};
	t.removeRow = function(row) {
		t.table.removeChild(row);
		layout.invalidate(this.table);
	};
	t.removeAllRows = function() {
		while (t.table.childNodes.length > 0)
			t.table.removeChild(t.table.childNodes[0]);
		layout.invalidate(this.table);
	};
	
	t.getCellContent = function(row,col) {
		if (t.selectable) col++;
		var tr = t.table.childNodes[row];
		var td = tr.childNodes[col];
		return td.childNodes[0];
	};
	t.getCellField = function(row,col) {
		if (t.selectable) col++;
		var tr = t.table.childNodes[row];
		if (col >= tr.childNodes.length) return null;
		var td = tr.childNodes[col];
		return td.field ? td.field : null;
	};
	t.getCellDataId = function(row,col) {
		if (t.selectable) col++;
		var tr = t.table.childNodes[row];
		var td = tr.childNodes[col];
		return td.data_id;
	};
	t.setCellDataId = function(row,col,data_id) {
		if (t.selectable) col++;
		var tr = t.table.childNodes[row];
		var td = tr.childNodes[col];
		td.data_id = data_id;
	};
	
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
	
	t.reset = function() {
		// remove data rows
		while (t.table.childNodes.length > 0) t.table.removeChild(t.table.childNodes[0]);		
		// remove columns
		for (var i = 1; i < t.header_rows.length; ++i)
			t.header_rows[i].parentNode.removeChild(t.header_rows[i]);
		while (t.header_rows[0].childNodes.length > 0) t.header_rows[0].removeChild(t.header_rows[0].childNodes[0]);		
		while (t.colgroup.childNodes.length > 0) t.colgroup.removeChild(t.colgroup.childNodes[0]);
		t.columns = [];
		t.setSelectable(!t.selectable);
		t.setSelectable(!t.selectable);
		layout.invalidate(this.table);
	};
	
	t.startLoading = function() {
		if (t._loading_hidder) return;
		if (!t.table) return;
		t._loading_hidder = new LoadingHeader(t.table);
		t._loading_hidder.setContent("<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Loading data...");
	};
	t.endLoading = function() {
		if (!t._loading_hidder) return;
		t._loading_hidder.remove();
		t._loading_hidder = null;
	};
	
	t.apply_filters = function() {
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
			t._selection_changed();
	};
	
	/* --- internal functions --- */
	t._createTable = function() {
		t.grid_element = t.form = document.createElement('FORM');
		var table = document.createElement('TABLE');
		t.form.appendChild(table);
		table.style.width = "100%";
		t.colgroup = document.createElement('COLGROUP');
		table.appendChild(t.colgroup);
		var thead = document.createElement('THEAD');
		t.header_rows = [];
		t.header_rows.push(document.createElement('TR'));
		thead.appendChild(t.header_rows[0]);
		table.appendChild(thead);
		t.table = document.createElement('TBODY');
		table.appendChild(t.table);
		t.element.appendChild(t.form);
		table.className = "grid";
	};
	t._create_cell = function(column, data, parent, ondone) {
		t._create_field(column.field_type, column.editable, column.onchanged, column.onunchanged, column.field_args, parent, data, function(field) {
			parent.field = field;
			if (ondone) ondone(field);
			t.oncellcreated.fire({parent:parent,field:field,column:column,data:data});
		});
	},
	t._create_field = function(field_type, editable, onchanged, onunchanged, field_args, parent, data, ondone) {
		require([["typed_field.js",field_type+".js"]], function() {
			var f = new window[field_type](data, editable, field_args);
			f.fillWidth();
			if (onchanged) f.ondatachanged.add_listener(onchanged);
			if (onunchanged) f.ondataunchanged.add_listener(onunchanged);
			parent.appendChild(f.getHTMLElement());
			ondone(f);
		});
	};
	
	/* initialization */
	t._createTable();
}
