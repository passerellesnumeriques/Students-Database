if (typeof require != 'undefined') {
	require("DataDisplay.js");
	require("grid.js");
	require("vertical_layout.js");
	require("horizontal_layout.js");
	require("horizontal_menu.js");
	require("typed_field.js",function(){
		require("field_text.js");
		require("field_html.js");
		require("field_integer.js");
	});
	require("context_menu.js");
}
function data_list(container, root_table, initial_data_shown, filters, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;

	/* Public properties */
	
	t.grid = null;
	
	/* Public methods */
	
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
	t.resetHeader = function() {
		if (t.header_center.widget)
			t.header_center.widget.removeAll();
		else
			while (t.header_center.childNodes.length > 0) t.header_center.removeChild(t.header_center.childNodes[0]);
	};
	t.addTitle = function(icon, text) {
		var div = document.createElement("DIV");
		div.style.display = "inline-block";
		div.className = "data_list_title";
		if (icon) {
			var img = document.createElement("IMG");
			img.src = icon;
			div.appendChild(img);
		}
		if (text) {
			var span = document.createElement("SPAN");
			span.appendChild(document.createTextNode(text));
			div.appendChild(span);
		}
		div.setAttribute("layout", "fixed");
		t.header.insertBefore(div, t.header_left);
		fireLayoutEventFor(t.header);
	};
	t.setTitle = function(html) {
		if (typeof html == 'string') {
			var div = document.createElement("DIV");
			div.style.display = "inline-block";
			div.innerHTML = html;
			html = div;
		}
		html.className = "data_list_title";
		html.setAttribute("layout", "fixed");
		t.header.insertBefore(html, t.header_left);
		fireLayoutEventFor(t.header);
	};
	t.reload_data = function() {
		t._load_data();
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
	t.resetFilters = function() {
		t._filters = [];
	};
	t.addFilter = function(filter) {
		t._filters.push(filter);
	};
	t.setRootTable = function(root_table, filters, onready) {
		t._root_table = root_table;
		t._onready = onready;
		t.grid = null;
		t._available_fields = null;
		t._page_num = 1;
		t._page_size = 1000;
		t._sort_column = null;
		t._sort_order = 3;
		t._filters = filters ? filters : [];
		t._col_actions = null;
		t._init_list();
		t._load_fields();
	};
	t.getRootTable = function() { return t._root_table; };
	
	/* Private properties */
	t._root_table = root_table;
	t._onready = onready;
	t._available_fields = null;
	t._page_num = 1;
	t._page_size = 1000;
	t._sort_column = null;
	t._sort_order = 3;
	t._filters = filters ? filters : [];
	t._col_actions = null;

	/* Private methods */
	
	t._init_list = function() {
		// analyze and remove container content
		while (container.childNodes.length > 0) {
			var e = container.childNodes[0];
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
			t._page_num--;
			t._load_data();
		};
		div.appendChild(img);
		t.header_left.appendChild(div);
		// + page number
		t._page_num_div = div = document.createElement("DIV");
		div.style.display = "inline-block";
		t.header_left.appendChild(div);
		// + next page
		t.next_page_div = div = document.createElement("DIV"); div.className = "button disabled";
		img = document.createElement("IMG"); img.onload = function() { fireLayoutEventFor(t.header); };
		div.title = "Next page";
		div.disabled = "disabled";
		img.src = "/static/data_model/right.png";
		div.doit = function() { 
			t._page_num++;
			t._load_data();
		};
		div.appendChild(img);
		t.header_left.appendChild(div);
		// + page size
		div = document.createElement("DIV");
		div.style.display = "inline-block";
		div.innerHTML = "Per page:";
		t.header_left.appendChild(div);
		var _page_size_div = div;
		require("typed_field.js",function(){
			require("field_integer.js",function(){
				t._page_size_field = new field_integer(t._page_size, true, {can_be_null:false,min:1,max:100000});
				t._page_size_field.onchange.add_listener(function() {
					t._page_size = t._page_size_field.getCurrentData();
					t._load_data();
				});
				_page_size_div.appendChild(t._page_size_field.getHTMLElement());
				if (t.header && t.header.widget && t.header.widget.layout)
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
		// + filter
		div = document.createElement("DIV"); div.className = "button";
		img = document.createElement("IMG"); img.onload = function() { fireLayoutEventFor(t.header); };
		div.title = "Filters";
		img.src = get_script_path("data_list.js")+"/filter.gif";
		div.onclick = function() { t._filters_dialog(this); };
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
		require("DataDisplay.js",function() {
			service.json("data_model","get_available_fields",{table:t._root_table},function(result){
				if (result) {
					t._available_fields = [];
					for (var i = 0; i < result.length; ++i) {
						result[i].data.path = new DataPath(result[i].path);
						t._available_fields.push(result[i].data);
					}
					t._ready();
				}
			});
		});
	};
	t._ready = function() {
		if (t.grid == null) return;
		if (t._available_fields == null) return;
		// compute visible fields
		t.show_fields = [];
		for (var i = 0; i < initial_data_shown.length; ++i) {
			var j = initial_data_shown[i].indexOf('.');
			var cat, name = null;
			if (j == -1) cat = initial_data_shown[i];
			else {
				cat = initial_data_shown[i].substring(0,j);
				name = initial_data_shown[i].substring(j+1);
			}
			var found = false;
			for (var j = 0; j < t._available_fields.length; ++j) {
				if (t._available_fields[j].category != cat) continue;
				if (name == null || t._available_fields[j].name == name) {
					t.show_fields.push(t._available_fields[j]);
					found = true;
				}
			}
			if (!found) alert("Data '"+initial_data_shown[i]+"' does not exist in the list of available data");
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
			// signal ready
			if (t._onready) t._onready(t);
		});
	};
	t._load_typed_fields = function(handler) {
		require("typed_field.js",function() {
			var fields = [];
			for (var i = 0; i < t._available_fields.length; ++i)
				if (!fields.contains(t._available_fields[i].field_classname))
					fields.push(t._available_fields[i].field_classname+".js");
			var nb = fields.length;
			for (var i = 0; i < fields.length; ++i)
				require(fields[i],function(){if (--nb == 0) handler(); });
		});
	};
	
	t._init_list();
	t._load_fields();
	
	t._create_column = function(f) {
		var col = new GridColumn(f.category+'.'+f.name, f.name, null, f.field_classname, false, null, null, f.field_config, f);
		if (f.sortable)
			col.addExternalSorting(function(_sort_order){
				t._sort_column = col;
				t._sort_order = _sort_order;
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
					action.icon = col.editable ? theme.icons_16.edit : theme.icons_16.no_edit;
					col.toggleEditable();
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
					for (var i = 0; i < f.edit_locks.length; ++i) {
						var service_name;
						if (f.edit_locks[i].column)
							service_name = "lock_column";
						else
							service_name = "lock_table";
						service.json("data_model",service_name,f.edit_locks[i],function(result){
							done++;
							if (result) locks.push(result.lock);
							if (done == f.edit_locks.length) {
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
	t._load_data = function() {
		t.grid.startLoading();
		var fields = [];
		for (var i = 0; i < t.show_fields.length; ++i)
			fields.push({path:t.show_fields[i].path.path,name:t.show_fields[i].name});
		var params = {table:t._root_table,fields:fields,actions:true,page:t._page_num,_page_size:t._page_size};
		if (t._sort_column && t._sort_order != 3) {
			params.sort_field = t._sort_column.id;
			params._sort_order = t._sort_order == 1 ? "ASC" : "DESC";
		}
		params.filters = t._filters;
		service.json("data_model","get_data_list",params,function(result){
			if (!result) {
				t.grid.endLoading();
				return;
			}
			var start = (t._page_num-1)*t._page_size+1;
			var end = (t._page_num-1)*t._page_size+result.data.length;
			if (end == 0)
				t._page_num_div.innerHTML = "0";
			else
				t._page_num_div.innerHTML = start+"-"+end+"/"+result.count;
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
					if (t.grid.columns[j].attached_data.path.path == t.show_fields[i].path.path &&
						t.grid.columns[j].attached_data.name == t.show_fields[i].name) {
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
			// register data events
			for (var i = 0; i < t.grid.table.childNodes.length; ++i) {
				var row = t.grid.table.childNodes[i];
				for (var j = 0; j < row.childNodes.length; ++j) {
					var td = row.childNodes[j];
					if (!td.field) continue;
					var col = null;
					for (var k = 0; k < t.grid.columns.length; ++k) if (t.grid.columns[k].id == td.col_id) { col = t.grid.columns[k]; break; }
					if (!col || !col.attached_data) continue;
					var closure = {
						field:td.field,
						register: function(data_display, data_key) {
							var t=this;
							window.top.datamodel.register_data_widget(window, data_display, data_key, function() {
								return t.field.getCurrentData();
							}, function(data) {
								t.field.setData(data);
							}, function(listener) {
								t.field.onchange.add_listener(listener);
							});
						}
					};
					closure.register(col.attached_data, td.data_id);
				}
			}
			
			t.grid.endLoading();
		});
	};
	t._select_columns_dialog = function(button) {
		var categories = [];
		for (var i = 0; i < t._available_fields.length; ++i)
			if (!categories.contains(t._available_fields[i].category))
				categories.push(t._available_fields[i].category);
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
			for (var j = 0; j < t._available_fields.length; ++j) {
				var f = t._available_fields[j];
				if (f.category != categories[i]) continue;
				var cb = document.createElement("INPUT");
				cb.type = 'checkbox';
				cb.data = f;
				var found = false;
				for (var k = 0; k < t.show_fields.length; ++k)
					if (t.show_fields[k].path.path == t._available_fields[j].path.path &&
						t.show_fields[k].name == t._available_fields[j].name) { found = true; break; }
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
							if (t.show_fields[i].path.path == this.data.path.path &&
								t.show_fields[i].name == this.data.name) {
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
			menu.removeOnClose = true;
			menu.addItem(dialog, true);
			menu.showBelowElement(button);
		});
	};
	t._filters_dialog = function(button) {
		require("context_menu.js");
		require("typed_filter.js");
		var dialog = document.createElement("DIV");
		dialog.style.padding = "5px";
		var table = document.createElement("TABLE"); dialog.appendChild(table);
		table.style.borderCollapse = "collapse";
		table.style.borderSpacing = "0px";
		var filter_classes = [];
		for (var i = 0; i < t._filters.length; ++i) {
			var dd = null;
			for (var j = 0; j < t._available_fields.length; ++j)
				if (t._available_fields[j].category == t._filters[i].category && t._available_fields[j].name == t._filters[i].name) {
					dd = t._available_fields[j];
					break;
				}
			if (dd != null && dd.filter_classname != null)
				filter_classes.push(dd.filter_classname+".js");
		}
		var create_filter = function(filter) {
			var tr = document.createElement("TR"); table.appendChild(tr);
			var td;
			tr.appendChild(td = document.createElement("TD"));
			td.style.borderBottom = "1px solid #808080";
			if (t._filters.indexOf(filter) > 0) td.innerHTML = "And";
			tr.appendChild(td = document.createElement("TD"));
			td.style.borderBottom = "1px solid #808080";
			td.appendChild(document.createTextNode(filter.category+": "+filter.name));
			tr.appendChild(td = document.createElement("TD"));
			td.style.borderBottom = "1px solid #808080";
			var dd = null;
			for (var j = 0; j < t._available_fields.length; ++j)
				if (t._available_fields[j].category == filter.category && t._available_fields[j].name == filter.name) {
					dd = t._available_fields[j];
					break;
				}
			if (dd == null) {
				td.innerHTML = "Unknown data";
			} else {
				if (dd.filter_classname == null) {
					td.innerHTML = "No filter available";
				} else {
					var f = new window[dd.filter_classname](filter.data, dd.filter_config, !filter.force);
					td.appendChild(f.getHTMLElement());
					f.onchange.add_listener(function (f) {
						filter.data = f.getCurrentData();
						t._load_data();
					});
					// TODO button or
				}
			}
			while (filter.or) {
				tr = document.createElement("TR"); table.appendChild(tr);
				tr.appendChild(td = document.createElement("TD"));
				td.style.borderBottom = "1px solid #808080";
				tr.appendChild(td = document.createElement("TD"));
				td.style.borderBottom = "1px solid #808080";
				td.style.textAlign = "right";
				td.appendChild(document.createTextNode("Or"));
				tr.appendChild(td = document.createElement("TD"));
				td.style.borderBottom = "1px solid #808080";
				var f = new window[dd.filter_classname](filter.or.data, dd.filter_config);
				td.appendChild(f.getHTMLElement());
				f.filter_or = filter.or;
				f.onchange.add_listener(function (f) {
					f.filter_or.data = f.getCurrentData();
					t._load_data();
				});
				// TODO button or
				filter = filter.or;
			}
		};
		require([["typed_filter.js",filter_classes]], function() {
			for (var i = 0; i < t._filters.length; ++i)
				create_filter(t._filters[i]);
		});
		var add = document.createElement("DIV");
		add.style.whiteSpace = 'nowrap';
		add.appendChild(document.createTextNode("Add filter on "));
		var select = document.createElement("SELECT"); add.appendChild(select);
		var o;
		o = document.createElement("OPTION"); o.value = 0; o.TEXT_NODE = ""; select.add(o);
		for (var i = 0; i < t._available_fields.length; ++i) {
			if (t._available_fields[i].filter_classname == null) continue;
			var filter_forced = false;
			for (var j = 0; j < t._filters.length; ++j) {
				if (!t._filters[j].force) continue;
				if (t._filters[j].category != t._available_fields[i].category) continue;
				if (t._filters[j].name != t._available_fields[i].name) continue;
				filter_forced = true;
				break;
			}
			if (filter_forced) continue;
			o = document.createElement("OPTION");
			o.value = t._available_fields[i].category+"."+t._available_fields[i].name;
			o.text = t._available_fields[i].category+": "+t._available_fields[i].name;
			select.add(o);
		}
		var add_go = document.createElement("IMG"); add.appendChild(add_go);
		add_go.className = "button";
		add_go.src = "/static/application/icon.php?main=/static/data_model/filter.gif&small="+theme.icons_10.add+"&where=right_bottom";
		add_go.style.verticalAlign = 'bottom';
		dialog.appendChild(add);
		require("context_menu.js",function(){
			var menu = new context_menu();
			menu.removeOnClose = true;
			menu.addItem(dialog, true);
			menu.showBelowElement(button);
			add_go.onload = function() { menu.resize(); };
			add_go.onclick = function() {
				var field = select.value;
				if (field == 0) return;
				for (var i = 0; i < t._available_fields.length; ++i)
					if (field == t._available_fields[i].category+"."+t._available_fields[i].name) {
						var filter = {category: t._available_fields[i].category, name: t._available_fields[i].name, data:null};
						t._filters.push(filter);
						require([["typed_filter.js",t._available_fields[i].filter_classname+".js"]], function() {
							create_filter(filter);
							menu.resize();
							t._load_data();
						});
					}
			};
		});
	};
	t._export_menu = function(button) {
		require("context_menu.js",function(){
			var menu = new context_menu();
			menu.removeOnClose = true;
			menu.addTitleItem(null, "Export Format");
			menu.addIconItem('/static/data_model/excel_16.png', 'Excel 2007 (.xlsx)', function() { t._export_list('excel2007'); });
			menu.addIconItem('/static/data_model/excel_16.png', 'Excel 5 (.xls)', function() { t._export_list('excel5'); });
			menu.addIconItem('/static/data_model/pdf_16.png', 'PDF', function() { t._export_list('pdf'); });
			menu.addIconItem('/static/data_model/csv.gif', 'CSV', function() { t._export_list('csv'); });
			menu.showBelowElement(button);
		});
	};
	t._export_list = function(format) {
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
		input.value = t._root_table;
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
		if (!t._changed_cells.contains(typed_field))
			t._changed_cells.push(typed_field);
		if (t._changed_cells.length > 0) {
			// there are changes, we may display the save button
			var errors = false;
			for (var i = 0; i < t._changed_cells.length && !errors; ++i)
				errors |= t._changed_cells[i].getError() != null;
			if (errors) {
				// do not display save button
				if (t.save_button) {
					t.header_left.removeChild(t.save_button);
					t.save_button = null;
					t.header.widget.layout();
				}
			} else {
				// display save button
				if (t.save_button == null) {
					t.save_button = document.createElement("IMG");
					t.save_button.className = "button";
					t.save_button.style.verticalAlign = "bottom";
					t.save_button.onload = function() { t.header.widget.layout(); };
					t.save_button.src = theme.icons_16.save;
					t.save_button.onclick = function() { t._save(); };
					t.header_left.appendChild(t.save_button);
					t.header.widget.layout();
				}
			}
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
				path: f.path.path,
				name: f.name,
				key: data_id,
				value: value
			});
			t._changed_cells[i].setOriginalData(value);
		}
		service.json("data_model","save_data",{root_table:t._root_table,to_save:to_save},function(result){
			if (result) {
				for (var i = 0; i < t._changed_cells.length; ++i) {
					var value = t._changed_cells[i].getCurrentData();
					t._changed_cells[i].setOriginalData(value);
				}
				t._changed_cells = [];
				if (t.save_button) {
					t.header_left.removeChild(t.save_button);
					t.save_button = null;
					t.header.widget.layout();
				}
			}
			t.grid.endLoading();
		});
	};
}
