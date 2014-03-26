if (typeof require != 'undefined') {
	require("typed_field.js",function(){
		require("field_blank.js");
	});
	require("dragndrop.js");
	theme.css("grid.css");
}

function GridColumnAction(icon,onclick) {
	this.icon = icon;
	this.onclick = onclick;
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
	this.editable = editable;
	this.onchanged = onchanged;
	this.onunchanged = onunchanged;
	this.field_args = field_args;
	this.attached_data = attached_data;
	// init
	this.th = document.createElement('TH');
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
						var input = t.grid._create_field(t.field_type, false, null, null, t.field_args, item, values[i]);
						input.disabled = 'disabled';
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
		this.th.innerHTML = title;
		if (this.sort_order) {
			var img;
			switch (this.sort_order) {
			case 1: // ascending
				img = document.createElement("IMG");
				img.src = url+"/arrow_up_10.gif";
				img.style.verticalAlign = "middle";
				img.style.cursor = "pointer";
				img.onclick = function() { t._onsort(2); };
				this.th.appendChild(img);
				break;
			case 2: // descending
				img = document.createElement("IMG");
				img.src = url+"/arrow_down_10.gif";
				img.style.verticalAlign = "middle";
				img.style.cursor = "pointer";
				img.onclick = function() { t._onsort(1); };
				this.th.appendChild(img);
				break;
			case 3: // not sorted yet
				var h = function() { t._onsort(1); };
				img = document.createElement("IMG");
				img.src = url+"/arrow_up_10.gif";
				img.style.verticalAlign = "middle";
				img.style.cursor = "pointer";
				img.onclick = h;
				this.th.appendChild(img);
				img = document.createElement("IMG");
				img.src = url+"/arrow_down_10.gif";
				img.style.verticalAlign = "middle";
				img.style.cursor = "pointer";
				img.onclick = h;
				this.th.appendChild(img);
				break;
			}
		}
		for (var i = 0; i < this.actions.length; ++i) {
			var img = document.createElement("IMG");
			img.src = this.actions[i].icon;
			img.style.verticalAlign = "middle";
			img.style.cursor = "pointer";
			img.data = this.actions[i];
			img.onclick = function(ev) { this.data.onclick(ev, this.data, t); };
			this.actions[i].element = img;
			this.th.appendChild(img);
		}
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
	
	t.addColumn = function(column, index) {
		column.grid = t;
		if (index == null || typeof index == 'undefined' || index >= t.columns.length) {
			t.columns.push(column);
			t.header.appendChild(column.th);
			t.colgroup.appendChild(column.col);
		} else {
			t.header.insertBefore(column.th, t.columns[index].th);
			t.colgroup.insertBefore(column.col, t.columns[index].col);
			t.columns.splice(index,0,column);
		}
		column._refresh_title();
		require("dragndrop.js",function() {
			dnd.configure_drag_element(column.th, true, null, function(){
				return column;
			});
			dnd.configure_drop_element(column.th, function(data,x,y){
				if (!data.grid || data.grid != t)
					return null;
				return get_script_path("grid.js")+"/move_column.gif";
			},function(data){
				if (data == column) return;
				var i = t.columns.indexOf(column);
				var j = t.columns.indexOf(data);
				if (i == j+1) return;
				t.moveColumn(j,i);
			});
		});
		// add cells
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var tr = t.table.childNodes[i];
			var td = document.createElement("TD");
			td.col_id = column.id;
			if (index == null || typeof index == 'undefined' || index == t.columns.length-1)
				tr.appendChild(td);
			else
				tr.insertBefore(td, tr.childNodes[index+t.selectable ? 1 : 0]);
			td.field = t._create_cell(column, null, td);
		}
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
	t.getColumnIndex = function(col) { return t.columns.indexOf(col); };
	t.removeColumn = function(index) {
		var col = t.columns[index];
		t.columns.splice(index,1);
		t.header.removeChild(col.th);
		t.colgroup.removeChild(col.col);
		var td_index = index + (t.selectable ? 1 : 0);
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var row = t.table.childNodes[i];
			row.removeChild(row.childNodes[td_index]);
		}
		t.apply_filters();
	};
	t.moveColumn = function(index_src, index_dst) {
		var col = t.columns[index_src];
		t.columns.splice(index_src,1);
		t.header.removeChild(col.th);
		t.colgroup.removeChild(col.col);
		var td_index = index_src + (t.selectable ? 1 : 0);
		var tds = [];
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var row = t.table.childNodes[i];
			tds.push(row.removeChild(row.childNodes[td_index]));
		}
		if (index_dst > index_src) index_dst--;
		t.columns.splice(index_dst,0,col);
		var i2 = index_dst + (t.selectable ? 1 : 0);
		if (i2 == t.header.childNodes.length)
			t.header.appendChild(col.th);
		else
			t.header.insertBefore(col.th, t.header.childNodes[i2]);
		if (i2 == t.colgroup.childNodes.length)
			t.colgroup.appendChild(col.col);
		else
			t.colgroup.insertBefore(col.col, t.colgroup.childNodes[i2]);
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var row = t.table.childNodes[i];
			if (i2 == row.childNodes.length)
				row.appendChild(tds[i]);
			else
				row.insertBefore(tds[i], row.childNodes[i2]);
		}
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
				td.innerHTML = "";
				td.field = t._create_cell(column, data, td);
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
			if (t.header.childNodes.length == 0) {
				t.header.appendChild(th);
				t.colgroup.appendChild(col);
			} else {
				t.header.insertBefore(th, t.header.childNodes[0]);
				t.colgroup.insertBefore(col, t.colgroup.childNodes[0]);
			}
		} else if (t.header.childNodes.length > 0) {
			t.header.removeChild(t.header.childNodes[0]);
			t.colgroup.removeChild(t.colgroup.childNodes[0]);
		}
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
			cb.onchange();
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
				td.field = t._create_cell(t.columns[j], data.data, td);
			if (typeof data.css != 'undefined' && data.css)
				td.className = data.css;
		}
		t.table.appendChild(tr);
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
	
	t.removeRowIndex = function(index) {
		t.table.removeChild(t.table.childNodes[index]);
	};
	t.removeRow = function(row) {
		t.table.removeChild(row);
	};
	t.removeAllRows = function() {
		while (t.table.childNodes.length > 0)
			t.table.removeChild(t.table.childNodes[0]);
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
		while (t.header.childNodes.length > 0) t.header.removeChild(t.header.childNodes[0]);		
		while (t.colgroup.childNodes.length > 0) t.colgroup.removeChild(t.colgroup.childNodes[0]);
		t.columns = [];
		t.setSelectable(!t.selectable);
		t.setSelectable(!t.selectable);
	};
	
	t.startLoading = function() {
		if (t.loading_back) return;
		t.loading_back = document.createElement("DIV");
		t.loading_back.style.backgroundColor = "rgba(192,192,192,0.35)";
		t.loading_back.style.position = "absolute";
		t.loading_back.style.top = absoluteTop(t.element)+"px";
		t.loading_back.style.left = absoluteLeft(t.element)+"px";
		t.loading_back.style.width = t.element.offsetWidth+"px";
		t.loading_back.style.height = t.element.offsetHeight+"px";
		document.body.appendChild(t.loading_back);
		set_lock_screen_content(t.loading_back, "<img src='"+theme.icons_16.loading+"'/> Loading data...");
	};
	t.endLoading = function() {
		if (!t.loading_back) return;
		document.body.removeChild(t.loading_back);
		t.loading_back = null;
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
		t.form = document.createElement('FORM');
		var table = document.createElement('TABLE');
		t.form.appendChild(table);
		table.style.width = "100%";
		t.colgroup = document.createElement('COLGROUP');
		table.appendChild(t.colgroup);
		var thead = document.createElement('THEAD');
		t.header = document.createElement('TR');
		thead.appendChild(t.header);
		table.appendChild(thead);
		t.table = document.createElement('TBODY');
		table.appendChild(t.table);
		t.element.appendChild(t.form);
		table.className = "grid";
	};
	t._create_cell = function(column, data, parent) {
		return t._create_field(column.field_type, column.editable, column.onchanged, column.onunchanged, column.field_args, parent, data);
	},
	t._create_field = function(field_type, editable, onchanged, onunchanged, field_args, parent, data) {
		var f;
//		if (data == grid_deactivated_cell)
//			f = new field_blank(document.createElement("DIV"), grid_deactivated_cell);
//		else
			f = new window[field_type](data, editable, field_args);
		if (onchanged) f.ondatachanged.add_listener(onchanged);
		if (onunchanged) f.ondataunchanged.add_listener(onunchanged);
		parent.appendChild(f.getHTMLElement());
		return f;
	};
	
	/* initialization */
	t._createTable();
}
