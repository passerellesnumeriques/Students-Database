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
/** A data list is a generic view of data: starting from a table, the user can choose what data to display, apply filters, sort data...
 * @param {DOMNode} container where to put it
 * @param {String} root_table starting point in the data model
 * @param {Array} initial_data_shown list of data to show at the beginning, with format 'Category'.'Name' where Category is the category of the DataDisplayHandler, and Name is the display name of the DataDisplay
 * @param {Array} filters list of {category:a,name:b,force:c,data:d,or:e}: category = from DataDisplayHandler; name = display name of the DataDisplay; force = true if the user cannot remove it; data = data of the filter, format depends on filter type; or=another filter data to do a 'or' condition
 * @param {Function} onready called when everything is ready, and we can start to use this object
 */
function data_list(container, root_table, initial_data_shown, filters, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;

	/* Public properties */
	
	/** {grid} the data list use the grid widget to display data, we can access it directly here */
	t.grid = null;
	/** Event when data has been loaded/refreshed */
	t.ondataloaded = new Custom_Event();
	
	/* Public methods */
	
	/** Add some html in the header of the data list
	 * @param {DOMNode} html element or string
	 */
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
	/** Remove everything in the header, previously added through addHeader */
	t.resetHeader = function() {
		if (t.header_center.widget)
			t.header_center.widget.removeAll();
		else
			while (t.header_center.childNodes.length > 0) t.header_center.removeChild(t.header_center.childNodes[0]);
	};
	/** Set a title, with optionally an icon
	 * @param {string} icon URL of the icon 16x16, or null if no icon
	 * @param {string} text the title
	 */
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
	/** Set the title, with some html
	 * @param {DOMNode} html the html element, or a string
	 */
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
	/** Force to refresh the data from the server */
	t.reloadData = function() {
		t._loadData();
	};
	/** Get the key if we found it, or the value, for a given table.column of the data model, for the given row of the grid
	 * @param {Number} row row index
	 * @param {String} table table name
	 * @param {String} column column name
	 * @returns {Object} the key, or the value, or null
	 */
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
	/** Remove all filters
	 * @param {Boolean} remove_forced true to remove also the filters which cannot be remove by the user 
	 */
	t.resetFilters = function(remove_forced) {
		if (remove_forced)
			t._filters = [];
		else
			for (var i = 0; i < t._filters.length; ++i)
				if (!t._filters[i].forced) {
					t._filters.splice(i,1);
					i--;
				}
	};
	/** Add a new filter
	 * @param {Object} filter {category,name,force,data,or}
	 */
	t.addFilter = function(filter) {
		t._filters.push(filter);
	};
	/** Reset everything in the data list
	 * @param {String} root_table the new starting point
	 * @param {Array} filters the new filters
	 * @param {Function} onready called when everything is ready with the new parameters
	 */
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
		t._initList();
		t._loadFields();
	};
	/** Return the root table
	 * @returns {String} root table name
	 */
	t.getRootTable = function() { return t._root_table; };
	/** Select a row, if available, correspondig to the given key in the given table
	 * @param {String} table table name
	 * @param {Number} key the key in the table identifying the row to search
	 */
	t.selectByTableKey = function(table, key) {
		for (var col = 0; col < t.show_fields.length; ++col) {
			if (t.show_fields[col].table == table) {
				for (var row = 0; row < t.data.length; ++row) {
					if (t.data[row].values[col].k == key) {
						t.grid.selectByIndex(row, true);
						break;
					}
				}
			}
		}
	};
	/** Get the key of the given table, for the given row
	 * @param {String} table table name
	 * @param {Number} row_id ID of the row in the grid, which is just its index
	 * @returns {Object} the key or null if not found
	 */
	t.getTableKeyForRow = function(table, row_id) {
		for (var col = 0; col < t.show_fields.length; ++col) {
			if (t.show_fields[col].table == table) {
				return t.data[row_id].values[col].k;
			}
		}
		return null;
	};
	
	/** Allows the user to click on a row
	 * @param {Function} handler called when the user clicks on a row, with the clicked row (from the grid) as parameter
	 */
	t.makeRowsClickable = function(handler) {
		t._rowOnclick = handler;
		for (var i = 0; i < t.grid.getNbRows(); ++i)
			t._makeClickable(t.grid.getRow(i));
	};
	
	/* Private properties */
	t._root_table = root_table;
	t._onready = onready;
	/** {Array} List of available fields retrieved through the service get_available_fields */
	t._available_fields = null;
	/** Page number */
	t._page_num = 1;
	/** Maximum rows per page */
	t._page_size = 1000;
	/** {GridColumn} column on which a sort is applied, or null if no sort */
	t._sort_column = null;
	/** 1 for ASC, 2 for DESC, 3 for no sort */
	t._sort_order = 3;
	/** List of filters to apply */
	t._filters = filters ? filters : [];
	/** {GridColumn} last column for actions, or null if there is no such a column */
	t._col_actions = null;
	/** {Function} called when rows are clickable, and the user clicks on a row */
	t._rowOnclick = null;

	/* Private methods */
	
	/** Initialize the data list display */
	t._initList = function() {
		// analyze and remove container content
		while (container.childNodes.length > 0) {
			var e = container.childNodes[0];
			container.removeChild(e);
		}
		// init header
		t.header = document.createElement("DIV");
		t.header.setAttribute("layout","25");
		t.header.className = "data_list_header header";
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
			t._loadData();
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
			t._loadData();
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
					t._loadData();
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
		div.onclick = function() { t._loadData(); };
		div.appendChild(img);
		t.header_left.appendChild(div);
		// + select column
		div = document.createElement("DIV"); div.className = "button";
		img = document.createElement("IMG"); img.onload = function() { fireLayoutEventFor(t.header); };
		div.title = "Select columns to display";
		img.src = get_script_path("data_list.js")+"/table_column.png";
		div.onclick = function() { t._selectColumnsDialog(this); };
		div.appendChild(img);
		t.header_right.appendChild(div);
		// + filter
		div = document.createElement("DIV"); div.className = "button";
		img = document.createElement("IMG"); img.onload = function() { fireLayoutEventFor(t.header); };
		div.title = "Filters";
		img.src = get_script_path("data_list.js")+"/filter.gif";
		div.onclick = function() { t._filtersDialog(this); };
		div.appendChild(img);
		t.header_right.appendChild(div);
		// + export
		div = document.createElement("DIV"); div.className = "button";
		img = document.createElement("IMG"); img.onload = function() { fireLayoutEventFor(t.header); };
		div.title = "Export list";
		img.src = theme.icons_16["export"];
		div.onclick = function() { t._exportMenu(this); };
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
	/** Load the available fields for the root table */
	t._loadFields = function() {
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
	/** Called when something is ready, to continue loading */
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
		t._loadTypedFields(function(){
			for (var i = 0; i < t.show_fields.length; ++i) {
				var f = t.show_fields[i];
				var col = t._createColumn(f);
				t.grid.addColumn(col);
			}
			// signal ready
			if (t._onready) t._onready(t);
			// get data
			t._loadData();
		});
	};
	/** Load JavaScript files that may be needed for all the available fields retrieved
	 * @param {Function} handler called when everything is loaded
	 */
	t._loadTypedFields = function(handler) {
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
	
	t._initList();
	t._loadFields();
	
	/** Create the column in the grid for the given DataDisplay
	 * @param {DataDisplay} f the field
	 * @returns {GridColumn} the column created
	 */
	t._createColumn = function(f) {
		var col = new GridColumn(f.category+'.'+f.name, f.name, null, f.field_classname, false, null, null, f.field_config, f);
		if (f.sortable)
			col.addExternalSorting(function(_sort_order){
				t._sort_column = col;
				t._sort_order = _sort_order;
				t._loadData();
			});
		if (f.filter_classname) {
			var a = new GridColumnAction("/static/widgets/grid/filter.gif",function(ev,a,col){
				require(["context_menu.js","typed_filter.js",f.filter_classname+'.js'], function() {
					var menu = new context_menu();
					var filter = null;
					for (var i = 0; i < t._filters.length; ++i)
						if (t._filters[i].category == f.category && t._filters[i].name == f.name) { filter = t._filters[i]; break; }
					if (filter == null) {
						filter = {category: f.category, name: f.name, data:null};
						t._filters.push(filter);
					}
					var table = document.createElement("TABLE");
					menu.addItem(table, true);
					t._createFilter(filter, table);
					menu.showBelowElement(a.element);
				});
			});
			col.addAction(a);
		}
		col.onchanged = function(field, data) {
			t._cellChanged(field);
		};
		col.onunchanged = function(field) {
			t._cellUnchanged(field);
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
					service.json("data_model","unlock",{locks:col.locks},function(result){});
					for (var j = 0; j < col.locks.length; ++j)
						window.databaselock.removeLock(col.locks[j]);
					col.locks = null;
					t._cancelColumnChanges(col);
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
									service.json("data_model","unlock",{locks:locks});
								} else {
									// success
									for (var j = 0; j < locks.length; ++j)
										window.databaselock.addLock(locks[j]);
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
	/** (Re)load the data from the server */
	t._loadData = function() {
		t.grid.startLoading();
		var fields = [];
		for (var i = 0; i < t.show_fields.length; ++i)
			fields.push({path:t.show_fields[i].path.path,name:t.show_fields[i].name});
		var params = {table:t._root_table,fields:fields,actions:true,page:t._page_num,page_size:t._page_size};
		if (t._sort_column && t._sort_order != 3) {
			params.sort_field = t._sort_column.id;
			params.sort_order = t._sort_order == 1 ? "ASC" : "DESC";
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
					if (t.grid.columns[j].attached_data && t.grid.columns[j].attached_data.path.path == t.show_fields[i].path.path &&
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
				if (t._rowOnclick)
					t._makeClickable(row);
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
							window.top.datamodel.registerDataWidget(window, data_display, data_key, function() {
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
			t.ondataloaded.fire(t);
			t.grid.endLoading();
		});
	};
	/** Show the menu to select the columns/fields to display
	 * @param {DOMNode} button the menu will be display below this element
	 */
	t._selectColumnsDialog = function(button) {
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
						var col = t._createColumn(this.data);
						t.grid.addColumn(col, t._col_actions != null ? t.grid.getColumnIndex(t._col_actions) : t.grid.getNbColumns());
						// TODO handle case if not yet loaded...
						t._loadData();
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
	/** Create the display for a filter, inside the given table
	 * @param {Object} filter the filter
	 * @param {DOMNode} table the table where to insert a row to display the filter
	 */
	t._createFilter = function(filter, table) {
		var tr = document.createElement("TR"); table.appendChild(tr);
		var td;
		tr.appendChild(td = document.createElement("TD"));
		td.style.borderBottom = "1px solid #808080";
		if (t._filters.indexOf(filter) > 0) td.innerHTML = "And";
		tr.appendChild(td = document.createElement("TD"));
		td.style.borderBottom = "1px solid #808080";
		td.style.whiteSpace = 'nowrap';
		td.appendChild(document.createTextNode(filter.category+": "+filter.name));
		tr.appendChild(td = document.createElement("TD"));
		td.style.borderBottom = "1px solid #808080";
		td.style.whiteSpace = 'nowrap';
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
					t._loadData();
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
			td.style.whiteSpace = 'nowrap';
			td.appendChild(document.createTextNode("Or"));
			tr.appendChild(td = document.createElement("TD"));
			td.style.whiteSpace = 'nowrap';
			td.style.borderBottom = "1px solid #808080";
			var f = new window[dd.filter_classname](filter.or.data, dd.filter_config);
			td.appendChild(f.getHTMLElement());
			f.filter_or = filter.or;
			f.onchange.add_listener(function (f) {
				f.filter_or.data = f.getCurrentData();
				t._loadData();
			});
			// TODO button or
			filter = filter.or;
		}
	},
	/** Display the menu to edit/add/remove filters
	 * @param {DOMNode} button the menu will be displayed below this element
	 */
	t._filtersDialog = function(button) {
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
		require([["typed_filter.js",filter_classes]], function() {
			for (var i = 0; i < t._filters.length; ++i)
				t._createFilter(t._filters[i], table);
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
							t._createFilter(filter, table);
							menu.resize();
							t._loadData();
						});
					}
			};
		});
	};
	/** Display the menu to export the list
	 * @param {DOMNode} button the menu will be displayed below this element
	 */
	t._exportMenu = function(button) {
		require("context_menu.js",function(){
			var menu = new context_menu();
			menu.removeOnClose = true;
			menu.addTitleItem(null, "Export Format");
			menu.addIconItem('/static/data_model/excel_16.png', 'Excel 2007 (.xlsx)', function() { t._exportList('excel2007'); });
			menu.addIconItem('/static/data_model/excel_16.png', 'Excel 5 (.xls)', function() { t._exportList('excel5'); });
			menu.addIconItem('/static/data_model/pdf_16.png', 'PDF', function() { t._exportList('pdf'); });
			menu.addIconItem('/static/data_model/csv.gif', 'CSV', function() { t._exportList('csv'); });
			menu.showBelowElement(button);
		});
	};
	/** Launch the export in the given format
	 * @param {String} format format to export
	 */
	t._exportList = function(format) {
		var fields = [];
		for (var i = 0; i < t.show_fields.length; ++i)
			fields.push({path:t.show_fields[i].path.path,name:t.show_fields[i].name});
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
		input.value = service.generateInput(fields);
		if (t._sort_column && t._sort_order != 3) {
			form.appendChild(input = document.createElement("INPUT"));
			input.type = 'hidden';
			input.name = 'sort_field';
			input.value = t._sort_column.id;
			form.appendChild(input = document.createElement("INPUT"));
			input.type = 'hidden';
			input.name = 'sort_order';
			input.value = t._sort_order == 1 ? "ASC" : "DESC";
		}
		form.appendChild(input = document.createElement("INPUT"));
		input.type = 'hidden';
		input.name = 'filters';
		input.value = service.generateInput(t._filters);
		form.appendChild(input = document.createElement("INPUT"));
		input.type = 'hidden';
		input.name = 'export';
		input.value = format;
		document.body.appendChild(form);
		form.submit();
	};
	
	/** List of cells that have been edited (used for the save action) */
	t._changed_cells = [];
	/** Cancel any change made in the given column
	 * @param {GridColumn} col the column
	 */
	t._cancelColumnChanges = function(col) {
		var index = t.grid.getColumnIndex(col);
		var rows = t.grid.getNbRows();
		for (var i = 0; i < rows; ++i) {
			var f = t.grid.getCellContent(i, index);
			f.typed_field.setData(f.typed_field.getOriginalData());
		}
	};
	/** Called when a cell changed
	 * @param {typed_field} typed_field field that have been edited
	 */
	t._cellChanged = function(typed_field) {
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
	/** Called when the content of a cell come back to its original value
	 * @param {typed_field} typed_field the field
	 */
	t._cellUnchanged = function(typed_field) {
		t._changed_cells.remove(typed_field);
		if (t._changed_cells.length == 0 && t.save_button) {
			// no more change: remove save button
			t.header_left.removeChild(t.save_button);
			t.save_button = null;
			t.header.widget.layout();
		}
	};
	/** Save all edited data */
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
	/** Make the row clickable
	 * @param {DOMNode} row the TR element corresponding to the row in the grid
	 */
	t._makeClickable = function(row) {
		row.onmouseover = function() { this.className = "selected"; };
		row.onmouseout = function() { this.className = ""; };
		row.style.cursor = 'pointer';
		row.onclick = function() {
			t._rowOnclick(this);
		};
	};
}
