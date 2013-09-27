if (typeof require != 'undefined') {
	require("grid.js");
	require("vertical_layout.js");
	require("horizontal_layout.js");
	require("horizontal_menu.js");
	require("typed_field.js");
	require("field_text.js");
	require("field_html.js");
	require("field_integer.js");
	require("context_menu.js");
}
function data_list(container, root_table, show_fields, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	t.addHeader = function(html) {
		var item = document.createElement("DIV");
		if (typeof html == 'string')
			item.innerHTML = html;
		else
			item.appendChild(html);
		if (t.header_center.widget)
			t.header_center.widget.addItem(item);
		else
			t.header_center.appendChild(item);
	};
	
	t._init_list = function() {
		// analyze and remove container content
		while (container.childNodes.length > 0) {
			var e = container.childNodes[0];
			// TODO get headers
			container.removeChild(e);
		}
		// init header
		t.header = document.createElement("DIV");
		t.header.setAttribute("layout","25");
		t.header.className = "data_list_header";
		t.header_left = document.createElement("DIV");
		t.header_left.setAttribute("layout","fixed");
		t.header_left.style.borderRight = "1px solid #808080";
		t.header.appendChild(t.header_left);
		t.header_center = document.createElement("DIV");
		t.header_center.setAttribute("layout","fill");
		t.header.appendChild(t.header_center);
		t.header_right = document.createElement("DIV");
		t.header_right.setAttribute("layout","fixed");
		t.header_right.style.borderLeft = "1px solid #808080";
		t.header.appendChild(t.header_right);
		container.appendChild(t.header);
		// init header buttons
		var div, img;
		// + previous page
		t.prev_page_div = div = document.createElement("DIV"); div.className = "button disabled";
		img = document.createElement("IMG"); img.onload = function() { fireLayoutEventFor(t.header); };
		div.title = "Previous page";
		img.src = "/static/data_model/left.png";
		div.doit = function() {
			t.page_num--;
			t._load_data();
		};
		div.appendChild(img);
		t.header_left.appendChild(div);
		// + page number
		t.page_num_div = div = document.createElement("DIV");
		div.style.display = "inline-block";
		t.header_left.appendChild(div);
		// + next page
		t.next_page_div = div = document.createElement("DIV"); div.className = "button disabled";
		img = document.createElement("IMG"); img.onload = function() { fireLayoutEventFor(t.header); };
		div.title = "Next page";
		div.disabled = "disabled";
		img.src = "/static/data_model/right.png";
		div.doit = function() { 
			t.page_num++;
			t._load_data();
		};
		div.appendChild(img);
		t.header_left.appendChild(div);
		// + page size
		div = document.createElement("DIV");
		div.style.display = "inline-block";
		div.innerHTML = "Per page:";
		t.header_left.appendChild(div);
		var page_size_div = div;
		require("typed_field.js",function(){
			require("field_integer.js",function(){
				var onc = function(){
					t.page_size = t.page_size_field.getCurrentData();
					t._load_data();
				};
				t.page_size_field = new field_integer(t.page_size, true, onc, onc, {can_be_null:false,min:1,max:100000});
				page_size_div.appendChild(t.page_size_field.getHTMLElement());
				t.header.widget.layout();
			});
		});
		// + refresh
		div = document.createElement("DIV"); div.className = "button";
		img = document.createElement("IMG"); img.onload = function() { fireLayoutEventFor(t.header); };
		div.title = "Refresh";
		img.src = theme.icons_16.refresh;
		div.onclick = function() { t._load_data(); };
		div.appendChild(img);
		t.header_left.appendChild(div);
		// + select column
		div = document.createElement("DIV"); div.className = "button";
		img = document.createElement("IMG"); img.onload = function() { fireLayoutEventFor(t.header); };
		div.title = "Select columns to display";
		img.src = get_script_path("data_list.js")+"/table_column.png";
		div.onclick = function() { t._select_columns_dialog(this); };
		div.appendChild(img);
		t.header_right.appendChild(div);
		// + export
		div = document.createElement("DIV"); div.className = "button";
		img = document.createElement("IMG"); img.onload = function() { fireLayoutEventFor(t.header); };
		div.title = "Export list";
		img.src = theme.icons_16["export"];
		div.onclick = function() { t._export_menu(this); };
		div.appendChild(img);
		t.header_right.appendChild(div);
		// + more button for horizontal menu
		div = document.createElement("DIV"); div.className = "button";
		img = document.createElement("IMG"); img.onload = function() { fireLayoutEventFor(t.header_center); };
		img.src = theme.icons_16.more_menu;
		div.appendChild(img);
		t.header_center.appendChild(div);
		t.more_menu = div;
		// init grid
		t.grid_container = document.createElement("DIV");
		t.grid_container.setAttribute("layout","fill");
		t.grid_container.style.overflow = "auto";
		container.appendChild(t.grid_container);
		require("grid.js",function(){
			t.grid = new grid(t.grid_container);
			t._ready();
		});
		// layout
		require("vertical_layout.js",function(){
			new vertical_layout(container);
			fireLayoutEventFor(container);
		});
		require("horizontal_layout.js",function(){
			new horizontal_layout(t.header);
			fireLayoutEventFor(container);
		});
		require("horizontal_menu.js",function(){
			new horizontal_menu(t.header_center);
			fireLayoutEventFor(container);
		});
	};
	t._load_fields = function() {
		service.json("data_model","get_available_fields",{table:root_table},function(result){
			if (result) {
				t.available_fields = result;
				for (var i = 0; i < t.available_fields.length; ++i)
					t.available_fields[i].path = new DataPath(t.available_fields[i].path);
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
				if (t.show_fields[i].path == field.path)
					return;
			t.show_fields.push(field);
		};
		for (var i = 0; i < show_fields.length; ++i)
			for (var j = 0; j < t.available_fields.length; ++j)
				if (show_fields[i] == t.available_fields[j].path.path ||
					(show_fields[i] == t.available_fields[j].path.table+'.'+t.available_fields[j].path.column)) {
					add_field(t.available_fields[j]);
					show_fields.splice(i,1);
					i--;
					break;
				}
		for (var i = 0; i < show_fields.length; ++i)
			window.top.status_manager.add_status(new window.top.StatusMessageError(null, "DataList: unknown field '"+show_fields[i]+"'"));
		// initialize grid
		t._load_typed_fields(function(){
			for (var i = 0; i < t.show_fields.length; ++i) {
				var f = t.show_fields[i];
				var col = t._create_column(f);
				t.grid.addColumn(col);
			}
			// get data
			t._load_data();
			// signal ready
			if (onready) onready(t);
		});
	};
	t._load_typed_fields = function(handler) {
		require("typed_field.js",function() {
			var fields = ["field_text.js","field_html.js","field_enum.js","field_date.js","field_integer.js"];
			var nb = fields.length;
			for (var i = 0; i < fields.length; ++i)
				require(fields[i],function(){if (--nb == 0) handler(); });
		});
	};
	t.grid = null;
	t.available_fields = null;
	t.page_num = 1;
	t.page_size = 1000;
	t.sort_column = null;
	t.sort_order = 3;
	
	t._init_list();
	t._load_fields();
	
	t._create_column = function(f) {
		var col = new GridColumn(f.path, f.name, null, f.field_classname, false, null, null, f.field_args, f);
		if (f.sortable)
			col.addExternalSorting(function(sort_order){
				t.sort_column = col;
				t.sort_order = sort_order;
				t._load_data();
			});
		//col.addFiltering(); // TODO better + if paged need external filtering
		col.onchanged = function(field, data) {
			t._cell_changed(field);
		};
		col.onunchanged = function(field) {
			t._cell_unchanged(field);
		};
		if (f.editable) {
			col.addAction(new GridColumnAction(theme.icons_16.edit,function(ev,action,col){
				var edit_col = function() {
					col.editable = !col.editable;
					action.icon = col.editable ? theme.icons_16.no_edit : theme.icons_16.edit;
					t.grid.rebuildColumn(col);
					fireLayoutEventFor(container);
				};
				t.grid.startLoading();
				if (col.editable) {
					for (var j = 0; j < col.locks.length; ++j) {
						window.database_locks.remove_lock(col.locks[j]);
						service.json("data_model","unlock",{lock:col.locks[j]},function(result){});
					}
					col.locks = null;
					t._cancel_column_changes(col);
					edit_col();
					t.grid.endLoading();
				} else {
					var locks = [];
					var done = 0;
					for (var i = 0; i < f.locks.length; ++i) {
						var service_name;
						if (f.locks[i].column) {
							if (f.locks[i].row_key)
								service_name = "lock_cell";
							else
								service_name = "lock_column";
						} else if (f.locks[i].row_key)
							service_name = "lock_row";
						else
							service_name = "lock_table";
						service.json("data_model",service_name,f.locks[i],function(result){
							done++;
							if (result) locks.push(result.lock);
							if (done == f.locks.length) {
								if (locks.length < done) {
									// errors occured, cancel all locks
									for (var j = 0; j < locks.length; ++j)
										service.json("data_model","unlock",{lock:locks[j]});
								} else {
									// success
									for (var j = 0; j < locks.length; ++j)
										window.database_locks.add_lock(locks[j]);
									col.locks = locks;
									edit_col();
								}
								t.grid.endLoading();
							}
						});
					}
				}
			}));
		}
		return col;
	};
	t._col_actions = null;
	t._load_data = function() {
		t.grid.startLoading();
		var fields = [];
		for (var i = 0; i < t.show_fields.length; ++i)
			fields.push(t.show_fields[i].path.path);
		var params = {table:root_table,fields:fields,actions:true,page:t.page_num,page_size:t.page_size};
		if (t.sort_column && t.sort_order != 3) {
			params.sort_field = t.sort_column.attached_data.path.path;
			params.sort_order = t.sort_order == 1 ? "ASC" : "DESC";
		}
		service.json("data_model","get_data_list",params,function(result){
			if (!result) {
				t.grid.endLoading();
				return;
			}
			var start = (t.page_num-1)*t.page_size+1;
			var end = (t.page_num-1)*t.page_size+result.data.length;
			t.page_num_div.innerHTML = start+"-"+end+"/"+result.count;
			if (start > 1) {
				t.prev_page_div.className = "button";
				t.prev_page_div.onclick = t.prev_page_div.doit;
			} else {
				t.prev_page_div.className = "button disabled";
				t.prev_page_div.onclick = null;
			}
			if (end < result.count) {
				t.next_page_div.className = "button";
				t.next_page_div.onclick = t.next_page_div.doit;
			} else {
				t.next_page_div.className = "button disabled";
				t.next_page_div.onclick = null;
			}
			t.header.widget.layout();
			t.data = result.data;
			var has_actions = false;
			var data = [];
			var col_id = [];
			for (var i = 0; i < t.show_fields.length; ++i)
				for (var j = 0; j < t.grid.columns.length; ++j)
					if (t.grid.columns[j].attached_data.path.path == t.show_fields[i].path.path) {
						col_id.push(t.grid.columns[j].id);
						break;
					}
			for (var i = 0; i < t.data.length; ++i) {
				var row = {row_id:i,row_data:[]};
				for (var j = 0; j < t.data[i].values.length; ++j) {
					if (t.data[i].values[j].invalid)
						row.row_data.push({col_id:col_id[j],data_id:null,data:grid_deactivated_cell});
					else
						row.row_data.push({col_id:col_id[j],data_id:t.data[i].values[j].k,data:t.data[i].values[j].v});
					if (t.data[i].actions)
						has_actions = true;
				}
				data.push(row);
			}
			if (has_actions) {
				if (!t._col_actions) {
					t._col_actions = new GridColumn('actions', "", null, "field_html", false, null, null, {}, null);
					t.grid.addColumn(t._col_actions);
				}
				for (var i = 0; i < t.data.length; ++i) {
					var row = data[i];
					var content = "";
					if (t.data[i].actions)
						for (var j = 0; j < t.data[i].actions.length; ++j) {
							var a = t.data[i].actions[j];
							content += "<a href=\""+a.link+"\"><img src='"+a.icon+"'/></a> ";
						}
					row.row_data.push({col_id:'actions',data_id:null,data:content});
				}
			} else {
				if (t._col_actions) {
					t.grid.removeColumn(t.grid.getColumnIndex(t._col_actions));
					t._col_actions = null;
				}
			}
			t.grid.setData(data);
			t.grid.endLoading();
		});
	};
	t.reload_data = function() {
		t._load_data();
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
					if (t.show_fields[k].path == t.available_fields[j].path) { found = true; break; }
				if (found) cb.checked = 'checked';
				cb.onclick = function() {
					if (this.checked) {
						t.show_fields.push(this.data);
						var col = t._create_column(this.data);
						t.grid.addColumn(col, t._col_actions != null ? t.grid.getColumnIndex(t._col_actions) : t.grid.getNbColumns());
						// TODO handle case if not yet loaded...
						t._load_data();
					} else {
						for (var i = 0; i < t.show_fields.length; ++i) {
							if (t.show_fields[i].path == this.data.path) {
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
	t._export_menu = function(button) {
		require("context_menu.js",function(){
			var menu = new context_menu();
			menu.addTitleItem(null, "Export Format");
			menu.addIconItem('/static/data_model/excel_16.png', 'Excel 2007 (.xlsx)', function() { t.export_list('excel2007'); });
			menu.addIconItem('/static/data_model/excel_16.png', 'Excel 5 (.xls)', function() { t.export_list('excel5'); });
			menu.addIconItem('/static/data_model/pdf_16.png', 'PDF', function() { t.export_list('pdf'); });
			menu.addIconItem('/static/data_model/csv.gif', 'CSV', function() { t.export_list('csv'); });
			menu.showBelowElement(button);
		});
	};
	t.export_list = function(format) {
		var fields = [];
		for (var i = 0; i < t.show_fields.length; ++i)
			fields.push(t.show_fields[i].path.path);

		var form = document.createElement("FORM");
		var input;
		form.appendChild(input = document.createElement("INPUT"));
		form.action = "/dynamic/data_model/service/get_data_list";
		form.method = 'POST';
		input.type = 'hidden';
		input.name = 'table';
		input.value = root_table;
		form.appendChild(input = document.createElement("INPUT"));
		input.type = 'hidden';
		input.name = 'fields';
		input.value = service.generate_input(fields);
		form.appendChild(input = document.createElement("INPUT"));
		input.type = 'hidden';
		input.name = 'export';
		input.value = format;
		document.body.appendChild(form);
		form.submit();
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
			t.save_button.style.verticalAlign = "bottom";
			t.save_button.onload = function() { t.header.widget.layout(); };
			t.save_button.src = theme.icons_16.save;
			t.save_button.onclick = function() { t._save(); };
			t.header_left.appendChild(t.save_button);
			t.header.widget.layout();
		}
	};
	t._cell_unchanged = function(typed_field) {
		t._changed_cells.remove(typed_field);
		if (t._changed_cells.length == 0 && t.save_button) {
			// no more change: remove save button
			t.header_left.removeChild(t.save_button);
			t.save_button = null;
			t.header.widget.layout();
		}
	};
	t._save = function() {
		t.grid.startLoading();
		var to_save = [];
		for (var i = 0; i < t._changed_cells.length; ++i) {
			var value = t._changed_cells[i].getCurrentData();
			var td = t._changed_cells[i].getHTMLElement().parentNode;
			var col_id = td.col_id;
			var data_id = td.data_id;
			var f = null;
			for (var j = 0; j < t.grid.columns.length; ++j)
				if (t.grid.columns[j].id == col_id) {
					f = t.grid.columns[j].attached_data;
					break;
				}
			to_save.push({
				table: f.edit.table,
				sub_model: f.edit.sub_model,
				column: f.edit.column,
				row_key_name: data_id[0],
				row_key_value: data_id[1],
				value: value
			});
			t._changed_cells[i].setOriginalData(value);
		}
		service.json("data_model","save_data",{to_save:to_save},function(result){
			t.grid.endLoading();
		});
	};
	
	t.getRowData = function(row, table, column) {
		// search a column where we can get it
		for (var i = 0; i < t.tables.length; ++i) {
			if (t.tables[i].name != table) continue;
			for (var j = 0; j < t.tables[i].keys.length; ++j) {
				if (t.tables[i].keys[j] == column) {
					// we found it as a key
					return t.data[row].values[i].k[j];
				}
			}
		}
		for (var i = 0; i < t.show_fields.length; ++i) {
			if (t.show_fields[i].path.table != table) continue;
			if (t.show_fields[i].path.column != column) continue;
			return t.data[row].values[i].v;
		}
		return null;
	};
}

function DataPath(s) {
	this.path = s;
	this.parseElement = function(s) {
		var i = s.indexOf('(');
		if (i != -1) {
			this.table = s.substring(0,i);
			var j = s.indexOf(')');
			var join = s.substring(i+1,j).split(",");
			this.join = {};
			for (i = 0; i < join.length; ++i) {
				var k = join[i].split("=");
				this.join[k[0]] = k[1];
			}
			if (j < s.length-1 && s.charAt(j+1) == '.')
				this.column = s.substring(j+2);
			return;
		}
		i = s.indexOf('.');
		this.table = s.substring(0,i);
		this.column = s.substring(i+1);
	};
	
	var i = s.lastIndexOf('>');
	var j = s.lastIndexOf('<');
	if (i == -1 || (j != -1 && j > i)) i = j;
	if (i != -1) {
		var dir = s.charAt(i);
		var element = s.substring(i+1);
		var multiple = false;
		if (s.charAt(i-1) == dir) {
			multiple = true;
			i--;
		}
		s = s.substring(0,i);
		this.parseElement(element);
		this.parent = new DataPath(s);
		this.parent.multiple = multiple;
		this.parent.direction = dir == '>' ? 1 : 2;
	} else {
		this.parent = null;
		this.multiple = false;
		this.direction = 0;
		this.parseElement(s);
	}
}