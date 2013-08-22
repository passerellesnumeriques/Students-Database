if (typeof require != 'undefined') {
	require("grid.js");
	require("vertical_layout.js");
	require("horizontal_layout.js");
	require("typed_field.js");
	require("field_text.js");
	require("context_menu.js");
}
function data_list(container, root_table, show_fields) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	t._init_list = function() {
		// analyze and remove container content
		while (container.childNodes.length > 0) {
			var e = container.childNodes[0];
			// TODO get headers
			container.removeChild(e);
		}
		// init header
		t.header = document.createElement("DIV");
		t.header.setAttribute("layout","fixed");
		t.header.className = "data_list_header";
		t.header_left = document.createElement("DIV");
		t.header_left.setAttribute("layout","fixed");
		t.header.appendChild(t.header_left);
		t.header_center = document.createElement("DIV");
		t.header_center.setAttribute("layout","fill");
		t.header.appendChild(t.header_center);
		t.header_right = document.createElement("DIV");
		t.header_right.setAttribute("layout","fixed");
		var div = document.createElement("DIV");
		div.className = "button";
		var img = document.createElement("IMG");
		img.onload = function() { fireLayoutEventFor(t.header); };
		img.src = get_script_path("data_list.js")+"/table_column.png";
		div.onclick = function() { t._select_columns_dialog(this); };
		div.appendChild(img);
		t.header_right.appendChild(div);
		t.header.appendChild(t.header_right);
		container.appendChild(t.header);
		// init grid
		t.grid_container = document.createElement("DIV");
		t.grid_container.setAttribute("layout","fill");
		container.appendChild(t.grid_container);
		require("grid.js",function(){
			t.grid = new grid(t.grid_container);
			t._ready();
		});
		// layout
		require("vertical_layout.js",function(){
			new vertical_layout(container);
		});
		require("horizontal_layout.js",function(){
			new horizontal_layout(t.header);
		});
	};
	t._load_fields = function() {
		service.json("data_model","get_available_fields",{table:root_table},function(result){
			if (result) {
				t.available_fields = result;
				t._ready();
			}
		});
	};
	t._ready = function() {
		if (t.grid == null) return;
		if (t.available_fields == null) return;
		// compute visible fields
		t.show_fields = [];
		var add_field = function(field) {
			for (var i = 0; i < t.show_fields.length; ++i)
				if (t.show_fields[i].field == field.field)
					return;
			t.show_fields.push(field);
		};
		for (var i = 0; i < show_fields.length; ++i)
			for (var j = 0; j < t.available_fields.length; ++j)
				if (show_fields[i] == t.available_fields[j].field) {
					add_field(t.available_fields[j]);
					show_fields.splice(i,1);
					i--;
					break;
				}
		for (var i = 0; i < show_fields.length; ++i) {
			for (var j = 0; j < t.available_fields.length; ++j) {
				var f = t.available_fields[j].field;
				// remove parenthesis and brackets
				do {
					var a = f.indexOf('(');
					if (a < 0) break;
					var b = f.indexOf(')', a);
					if (b < 0) break;
					f = f.substring(0,a)+f.substring(b+1);
				} while (true);
				do {
					var a = f.indexOf('[');
					if (a < 0) break;
					var b = f.indexOf(']', a);
					if (b < 0) break;
					f = f.substring(0,a)+f.substring(b+1);
				} while (true);
				if (show_fields[i] == f) {
					add_field(t.available_fields[j]);
					show_fields.splice(i,1);
					i--;
					break;
				}
				if (f.substring(f.length-show_fields[i].length) == show_fields[i]) {
					var c = f.charAt(f.length-show_fields[i].length-1);
					if (c == '.' || c == '>') {
						add_field(t.available_fields[j]);
						show_fields.splice(i,1);
						i--;
						break;
					}
				}
			}
		}
		// initialize grid
		t._load_typed_fields(function(){
			for (var i = 0; i < t.show_fields.length; ++i) {
				var f = t.show_fields[i];
				var col = t._create_column(f);
				t.grid.addColumn(col);
			}
			// get data
			t._load_data();
		});
	};
	t._load_typed_fields = function(handler) {
		require("typed_field.js",function() {
			var fields = ["field_text.js"];
			var nb = fields.length;
			for (var i = 0; i < fields.length; ++i)
				require(fields[i],function(){if (--nb == 0) handler(); });
		});
	};
	t.grid = null;
	t.available_fields = null;
	
	t._init_list();
	t._load_fields();
	
	t._create_column = function(f) {
		// TODO typed
		var col = new GridColumn(f.field, f.name, null, "field_text", false, null, null, {}, f);
		col.addSorting(function (v1,v2){
			return v1.localeCompare(v2);
		}); // TODO external sorting if paged
		col.addFiltering(); // TODO better + if paged need external filtering
		col.onchanged = function(field, data) {
			t._cell_changed(field);
		};
		col.onunchanged = function(field) {
			t._cell_unchanged(field);
		};
		// TODO onchange + save
		if (f.edit) {
			col.addAction(new GridColumnAction(theme.icons_16.edit,function(ev,action,col){
				var edit_col = function() {
					col.editable = !col.editable;
					action.icon = col.editable ? theme.icons_16.no_edit : theme.icons_16.edit;
					t.grid.rebuildColumn(col);
					fireLayoutEventFor(container);
				};
				t.grid.startLoading();
				if (col.editable) {
					service.json("data_model","unlock",{lock:col.lock},function(result){
						if (result) {
							t._cancel_column_changes(col);
							edit_col();
						}
						t.grid.endLoading();
					});
				} else
					service.json("data_model","lock_column",{table:f.table,column:f.column},function(result){
						if (result) {
							col.lock = result.lock;
							edit_col();
						}
						t.grid.endLoading();
					});
			}));
		}
		return col;
	};
	t._load_data = function() {
		t.grid.startLoading();
		var fields = [];
		for (var i = 0; i < t.show_fields.length; ++i)
			fields.push(t.show_fields[i].field);
		service.json("data_model","get_data_list",{table:root_table,fields:fields},function(result){
			if (!result) {
				t.grid.endLoading();
				return;
			}
			t.tables = result.tables;
			t.data = result.data;
			var data = [];
			for (var i = 0; i < t.data.length; ++i) {
				var row = [];
				for (var j = 0; j < t.data[i].length; ++j)
					row.push(t.data[i][j].v);
				data.push(row);
			}
			t.grid.setData(data);
			t.grid.endLoading();
		});
	};
	t._select_columns_dialog = function(button) {
		var categories = [];
		for (var i = 0; i < t.available_fields.length; ++i)
			if (!categories.contains(t.available_fields[i].cat))
				categories.push(t.available_fields[i].cat);
		var dialog = document.createElement("DIV");
		var table = document.createElement("TABLE"); dialog.appendChild(table);
		table.style.borderCollapse = "collapse";
		table.style.borderSpacing = "0px";
		var tr_head = document.createElement("TR"); table.appendChild(tr_head);
		tr_head.style.backgroundColor = "#C0C0FF";
		var tr_content = document.createElement("TR"); table.appendChild(tr_content);
		for (var i = 0; i < categories.length; ++i) {
			var td = document.createElement("TD");
			tr_head.appendChild(td);
			td.style.borderBottom = "1px solid black";
			td.align = "center";
			td.style.margin = "0px";
			td.style.padding = "2px";
			if (i>0) td.style.borderLeft = "1px solid black";
			td.innerHTML = categories[i];
			td = document.createElement("TD");
			tr_content.appendChild(td);
			td.style.verticalAlign = "top";
			td.style.margin = "0px";
			td.style.padding = "2px";
			if (i>0) td.style.borderLeft = "1px solid black";
			for (var j = 0; j < t.available_fields.length; ++j) {
				var f = t.available_fields[j];
				if (f.cat != categories[i]) continue;
				var cb = document.createElement("INPUT");
				cb.type = 'checkbox';
				cb.data = f;
				var found = false;
				for (var k = 0; k < t.show_fields.length; ++k)
					if (t.show_fields[k].field == t.available_fields[j].field) { found = true; break; }
				if (found) cb.checked = 'checked';
				cb.onclick = function() {
					if (this.checked) {
						t.show_fields.push(this.data);
						var col = t._create_column(this.data);
						t.grid.addColumn(col);
						// TODO handle case if not yet loaded...
						t._load_data();
					} else {
						for (var i = 0; i < t.show_fields.length; ++i) {
							if (t.show_fields[i].field == this.data.field) {
								t.show_fields.splice(i,1);
								t.grid.removeColumn(i);
								break;
							}
						}
					}
				};
				td.appendChild(cb);
				td.appendChild(document.createTextNode(f.name));
				td.appendChild(document.createElement("BR"));
			}
		}
		require("context_menu.js",function(){
			var menu = new context_menu();
			menu.addItem(dialog, true);
			menu.showBelowElement(button);
		});
	};
	
	t._changed_cells = [];
	t._cancel_column_changes = function(col) {
		var index = t.grid.getColumnIndex(col);
		var rows = t.grid.getNbRows();
		for (var i = 0; i < rows; ++i) {
			var f = t.grid.getCellContent(i, index);
			f.typed_field.setData(f.typed_field.getOriginalData());
		}
	};
	t._cell_changed = function(typed_field) {
		if (t._changed_cells.contains(typed_field)) return;
		t._changed_cells.push(typed_field);
		if (t._changed_cells.length == 1) {
			// first change, display save button
			t.save_button = document.createElement("IMG");
			t.save_button.className = "button";
			t.save_button.src = theme.icons_16.save;
			t.save_button.onclick = function() { t._save(); };
			t.header_left.appendChild(t.save_button);
			fireLayoutEventFor(container);
		}
	};
	t._cell_unchanged = function(typed_field) {
		t._changed_cells.remove(typed_field);
		if (t._changed_cells.length == 0 && t.save_button) {
			// no more change: remove save button
			t.header_left.removeChild(t.save_button);
			t.save_button = null;
			fireLayoutEventFor(container);
		}
	};
	t._save = function() {
		// TODO
	};
}
