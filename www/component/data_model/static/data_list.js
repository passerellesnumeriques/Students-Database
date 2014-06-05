if (typeof require != 'undefined') {
	require("DataDisplay.js");
	require("grid.js");
	require("vertical_layout.js");
	require("horizontal_layout.js");
	require("horizontal_menu.js");
	require("vertical_align.js");
	require("typed_field.js",function(){
		require("field_text.js");
		require("field_html.js");
		require("field_integer.js");
	});
	require("context_menu.js");
	theme.css("data_list.css");
}
/** A data list is a generic view of data: starting from a table, the user can choose what data to display, apply filters, sort data...
 * @param {DOMNode} container where to put it
 * @param {String} root_table starting point in the data model
 * @param {Array} initial_data_shown list of data to show at the beginning, with format 'Category'.'Name' where Category is the category of the DataDisplayHandler, and Name is the display name of the DataDisplay
 * @param {Array} filters list of {category:a,name:b,force:c,data:d,or:e}: category = from DataDisplayHandler; name = display name of the DataDisplay; force = true if the user cannot remove it; data = data of the filter, format depends on filter type; or=another filter data to do a 'or' condition
 * @param {Function} onready called when everything is ready, and we can start to use this object
 */
function data_list(container, root_table, sub_model, initial_data_shown, filters, page_size, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	if (!page_size) page_size = -1;
	var t=this;
	t.container = container;
	t.container.className = t.container.className ? "data_list "+t.container.className : "data_list";
	if (!t.container.id) t.container.id = generateID();

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
		layout.invalidate(t.header);
	};
	/** Remove everything in the header, previously added through addHeader */
	t.resetHeader = function() {
		if (t.header_center.widget)
			t.header_center.widget.removeAll();
		else
			while (t.header_center.childNodes.length > 0) t.header_center.removeChild(t.header_center.childNodes[0]);
		layout.invalidate(t.header);
	};
	t.addFooterTool = function(html) {
		var item = document.createElement("DIV");
		if (typeof html == 'string')
			item.innerHTML = html;
		else
			item.appendChild(html);
		if (!t.footer_tools) {
			t.footer_tools = document.createElement("DIV");
			t.footer_tools.className = "footer_tools";
			t.addFooter(t.footer_tools);
			t.footer.appendChild(t.footer_tools);
			new horizontal_layout(t.footer_tools);
			layout.invalidate(container);
		}
		t.footer_tools.appendChild(item);
		layout.invalidate(t.footer_tools);
	};
	t.addFooter = function(html) {
		var item = document.createElement("DIV");
		if (typeof html == 'string')
			item.innerHTML = html;
		else
			item.appendChild(html);
		if (!t.footer) {
			t.footer = document.createElement("DIV");
			t.footer.className = "footer";
			container.appendChild(t.footer);
			layout.invalidate(container);
		}
		t.footer.appendChild(item);
		layout.invalidate(t.footer);
	};
	t.resetFooter = function() {
		if (!t.footer) return;
		container.removeChild(t.footer);
		t.footer = null;
		layout.invalidate(container);
	};
	/** Set a title, with optionally an icon
	 * @param {String} icon URL of the icon 16x16, or null if no icon
	 * @param {String} text the title
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
		layout.invalidate(t.header);
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
		layout.invalidate(t.header);
	};
	/** Force to refresh the data from the server */
	t.reloadData = function(ondone) {
		t._loadData(ondone);
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
			if (t.show_fields[i].field.path.table != table) continue;
			if (t.show_fields[i].field.path.column != column) continue;
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
		if (t._filterNumber) t._filterNumber.style.visibility = "hidden";
	};
	/** Add a new filter
	 * @param {Object} filter {category,name,force,data,or}
	 */
	t.addFilter = function(filter) {
		t._filters.push(filter);
		if (t._filterNumber) {
			t._filterNumber.style.visibility = "visible";
			t._filterNumber.innerHTML = t._filters.length;
		}
	};
	t.removeFilter = function(filter) {
		for (var i = 0; i < t._filters.length; ++i) {
			if (t._filters[i] == filter) {
				t._filters.splice(i,1);
				break;
			}
			var found = false;
			var pos = filter;
			while (pos.or) {
				if (pos.or == filter) {
					found = true;
					pos.or = pos.or.or;
					break;
				}
			}
			if (found) break;
		}
		if (t._filterNumber) {
			t._filterNumber.style.visibility = t._filters.length > 0 ? "visible" : "hidden";
			t._filterNumber.innerHTML = t._filters.length;
		}
	};
	t.getFilters = function() { return t._filters; };
	
	t.getField = function(category, name) {
		for (var i = 0; i < t._available_fields.length; ++i)
			if (t._available_fields[i].category == category && t._available_fields[i].name == name)
				return t._available_fields[i];
		return null;
	};
	t.showField = function(field, onready) {
		t.showFields([field],onready);
	};
	t.showFields = function(fields, onready) {
		var changed = false;
		for (var i = 0; i < fields.length; ++i) {
			var found = false;
			for (var j = 0; j < t.show_fields.length; ++j) if (t.show_fields[j].field == fields[i]) { found = true; break; };
			if (found) continue;
			var f = {field:fields[i],sub_index:-1};
			t.show_fields.push(f);
			var col = t._createColumn(f);
			t.grid.addColumn(col, t._col_actions != null ? t.grid.getColumnIndex(t._col_actions) : t.grid.getNbColumns());
			changed = true;
		}
		if (!changed) {
			if (onready) onready();
			return;
		}
		t._loadData(onready);
	};
	t.hideField = function(field) {
		for (var i = 0; i < t.show_fields.length; ++i) {
			if (t.show_fields[i].field.path.path == field.path.path &&
				t.show_fields[i].field.name == field.name) {
				t.show_fields.splice(i,1);
				t.grid.removeColumn(i);
				break;
			}
		}
	};
	t.showSubField = function(field, sub_index) {
		var found = -1;
		for (var j = 0; j < t.show_fields.length; ++j) if (t.show_fields[j].field == field) { found = j; break; };
		var f = {field:field,sub_index:sub_index};
		t.show_fields.push(f);
		var col = t._createColumn(f);
		if (found == -1) {
			// first time we have a sub_index for this field: create the parent column
			var container = new GridColumnContainer(field.name, [col], field);
			t.grid.addColumnContainer(container, t._col_actions != null ? t.grid.getColumnIndex(t._col_actions) : t.grid.getNbColumns());
			t._loadData();
		} else {
			// we already have the parent field
			// we need to get its container
			var container = t.grid.getColumnContainerByAttachedData(field);
			container.addSubColumn(col);
			// set data of new column
			var found_col = t.grid.getColumnByAttachedData(t.show_fields[found]);
			var found_col_index = t.grid.getColumnIndex(found_col);
			var col_index = t.grid.getColumnIndex(col);
			for (var i = 0; i < t.grid.getNbRows(); ++i) {
				var found_field = t.grid.getCellField(i, found_col_index);
				var new_field = t.grid.getCellField(i, col_index);
				var data_id = t.grid.getCellDataId(i, found_col_index);
				t.grid.setCellDataId(i, col_index, data_id);
				new_field.setData(found_field.getCurrentData());
				// register data
				var closure = {
					field:new_field,
					register: function(data_display, data_key) {
						var t=this;
						window.top.datamodel.registerDataWidget(window, data_display.field, data_key, this.field.element, function() {
							return t.field.getCurrentData();
						}, function(data) {
							t.field.setData(data);
						}, function(listener) {
							t.field.onchange.add_listener(listener);
						}, function(listener) {
							t.field.onchange.remove_listener(listener);
						});
					}
				};
				closure.register(t.show_fields[found], data_id);
			}
		}
	};
	t.hideSubField = function(field, sub_index) {
		for (var i = 0; i < t.show_fields.length; ++i) {
			if (t.show_fields[i].field == field && t.show_fields[i].sub_index == sub_index) {
				var col = t.grid.getColumnByAttachedData(t.show_fields[i]);
				t.grid.removeColumn(t.grid.getColumnIndex(col));
				t.show_fields.splice(i,1);
				break;
			}
		}
	};
	
	/** Reset everything in the data list
	 * @param {String} root_table the new starting point
	 * @param {Array} filters the new filters
	 * @param {Function} onready called when everything is ready with the new parameters
	 */
	t.setRootTable = function(root_table, sub_model, filters, page_size, onready) {
		t._root_table = root_table;
		t._sub_model = sub_model;
		t._onready = onready;
		t.grid = null;
		t._available_fields = null;
		t._page_num = 1;
		t._page_size = page_size;
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
	/** Select a row, if available, corresponding to the given key in the given table
	 * @param {String} table table name
	 * @param {Number} key the key in the table identifying the row to search
	 */
	t.selectByTableKey = function(table, key) {
		for (var col = 0; col < t.show_fields.length; ++col) {
			if (t.show_fields[col].field.table == table) {
				for (var row = 0; row < t.data.length; ++row) {
					if (t.data[row].values[col].k == key) {
						t.grid.selectByIndex(row, true);
						break;
					}
				}
			}
		}
	};
	
	t.disableSelectByTableKey = function(table, key){
		for (var col = 0; col < t.show_fields.length; ++col) {
			if (t.show_fields[col].field.table == table) {
				for (var row = 0; row < t.data.length; ++row) {
					if (t.data[row].values[col].k == key) {
						t.grid.disableByIndex(row, true);
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
			if (t.show_fields[col].field.table == table) {
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
	
	t.addActionProvider = function(provider) {
		t._action_providers.push(provider);
		if (!t._col_actions) {
			t._col_actions = new GridColumn('actions', "", null, "right", "field_html", false, null, null, {}, null);
			t.grid.addColumn(t._col_actions);
		}
		t._populateActions();
	};
	
	t.addPictureSupport = function(table, picture_provider, thumbnail_provider) {
		t._picture_table = table;
		t._picture_provider = picture_provider;
		require(["mac_tabs.js","field_html.js","pictures_list.js"], function() {
			var header = document.createElement("DIV");
			header.className = "header_right";
			header.style.display = "inline-block";
			header.style.height = "100%";
			header.style.verticalAlign = "middle";
			var tabs = new mac_tabs('compressed');
			tabs.addItem("<img src='/static/data_model/list_text_16.png'/>","text");
			if (picture_provider)
				tabs.addItem("<img src='/static/data_model/list_detail_16.png'/>","detail");
			if (thumbnail_provider) {
				tabs.addItem("<img src='/static/data_model/list_thumb_16.png'/>","thumb");
				theme.css("picture_thumbnail.css");
			}
			tabs.select("text");
			header.appendChild(tabs.element);
			t.header_left.appendChild(header);
			layout.invalidate(t.header);
			
			var col_picture = new GridColumn('data_list_picture', "Picture", null, "center", "field_html", false, null, null, {}, null);
			var thumb_container = document.createElement("DIV");
			thumb_container.setAttribute("layout","fill");
			thumb_container.style.overflow = "auto";

			var div_picture_size = document.createElement("DIV");
			div_picture_size.appendChild(document.createTextNode("Pic.size"));
			div_picture_size.style.marginRight = "3px";
			div_picture_size.style.fontSize = "11px";
			div_picture_size.style.display = "inline-block";
			var select_size = document.createElement("SELECT");
			select_size.style.fontSize = "11px";
			div_picture_size.appendChild(select_size);
			var pic_list = new pictures_list(thumb_container);
			var o;
			o = document.createElement("OPTION"); o.text = "35x35"; select_size.add(o);
			o = document.createElement("OPTION"); o.text = "38x50"; select_size.add(o);
			o = document.createElement("OPTION"); o.text = "75x100"; select_size.add(o);
			o = document.createElement("OPTION"); o.text = "150x150"; select_size.add(o);
			o = document.createElement("OPTION"); o.text = "150x200"; select_size.add(o);
			o = document.createElement("OPTION"); o.text = "225x300"; select_size.add(o);
			o = document.createElement("OPTION"); o.text = "300x300"; select_size.add(o);
			select_size.selectedIndex = 2;
			t._pic_width = 75;
			t._pic_height = 100;
			pic_list.setSize(t._pic_width, t._pic_height);
			select_size.onchange = function() {
				switch (select_size.selectedIndex) {
				case 0: t._pic_width = 35; t._pic_height = 35; break;
				case 1: t._pic_width = 38; t._pic_height = 50; break;
				case 2: t._pic_width = 75; t._pic_height = 100; break;
				case 3: t._pic_width = 150; t._pic_height = 150; break;
				case 4: t._pic_width = 150; t._pic_height = 200; break;
				case 5: t._pic_width = 225; t._pic_height = 300; break;
				case 6: t._pic_width = 300; t._pic_height = 300; break;
				};
				pic_list.setSize(t._pic_width, t._pic_height);
				t._loadData();
			};

			var original_load = t._loadData;
			tabs.onselect = function(id) {
				switch (id) {
				case "text":
					t._loadData = original_load;
					if (thumb_container.parentNode) container.removeChild(thumb_container);
					if (div_picture_size.parentNode) header.removeChild(div_picture_size);
					//if (!t.header_right.parentNode) t.header.appendChild(t.header_right);
					if (!t.grid_container.parentNode) container.insertBefore(t.grid_container, t.header.nextSibling);
					if (t.grid.getColumnById("data_list_picture") != null)
						t.grid.removeColumn(t.grid.getColumnIndex(col_picture));
					break;
				case "detail":
					t._loadData = original_load;
					if (thumb_container.parentNode) container.removeChild(thumb_container);
					if (!div_picture_size.parentNode) header.appendChild(div_picture_size);
					//if (!t.header_right.parentNode) t.header.appendChild(t.header_right);
					if (!t.grid_container.parentNode) container.insertBefore(t.grid_container, t.header.nextSibling);
					if (t.grid.getColumnById("data_list_picture") == null) {
						t.grid.addColumn(col_picture, 0);
						for (var i = 0; i < t.grid.getNbRows(); ++i) {
							var field = t.grid.getCellField(i, 0);
							if (field == null) continue;
							picture_provider(field.getHTMLElement(), t.getTableKeyForRow(table, i), t._pic_width, t._pic_height);
						}
					} else
						t._loadData();
					break;
				case "thumb":
					if (t.grid_container.parentNode) container.removeChild(t.grid_container);
					//if (t.header_right.parentNode) t.header.removeChild(t.header_right);
					if (!div_picture_size.parentNode) header.appendChild(div_picture_size);
					if (!thumb_container.parentNode) {
						t._loadData = function(onready) {
							original_load(function() {
								thumbnail_provider(function(pics) {
									pic_list.setPictures(pics);
									if (onready) onready();
								});								
							});
						};
						thumbnail_provider(function(pics) {
							pic_list.setPictures(pics);
						});
						thumb_container.setAttribute("layout","fill");
						thumb_container.style.overflow = "auto";
						container.insertBefore(thumb_container, t.header.nextSibling);
					}
					break;
				};
				layout.invalidate(container);
			};
		});
	};
	
	t.print = function() {
		printContent(t.header.nextSibling);
	};
	
	/* Private properties */
	t._root_table = root_table;
	t._sub_model = sub_model;
	t._onready = onready;
	/** {Array} List of available fields retrieved through the service get_available_fields */
	t._available_fields = null;
	/** Page number */
	t._page_num = 1;
	/** Maximum rows per page or -1 if no paging */
	t._page_size = page_size;
	/** {GridColumn} column on which a sort is applied, or null if no sort */
	t._sort_column = null;
	/** 1 for ASC, 2 for DESC, 3 for no sort */
	t._sort_order = 3;
	/** List of filters to apply */
	t._filters = filters ? filters : [];
	/** {Function} called when rows are clickable, and the user clicks on a row */
	t._rowOnclick = null;
	t._action_providers = [];
	/** {GridColumn} last column for actions, or null if there is no such a column */
	t._col_actions = null;

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
		t.header.className = "header";
		t.header_left = document.createElement("DIV");
		t.header_left.className = "header_left";
		t.header_left.setAttribute("layout","fixed");
		t.header.appendChild(t.header_left);
		t.header_center = document.createElement("DIV");
		t.header_center.className = "header_center";
		t.header_center.setAttribute("layout","fill");
		t.header.appendChild(t.header_center);
		t.header_right = document.createElement("DIV");
		t.header_right.className = "header_right";
		t.header_right.setAttribute("layout","fixed");
		t.header.appendChild(t.header_right);
		container.appendChild(t.header);
		// init header buttons
		var div, img;
		if (t._page_size > 0) {
			// + previous page
			t.prev_page_button = div = document.createElement("BUTTON");
			div.disabled = "disabled";
			img = document.createElement("IMG"); img.onload = function() { layout.invalidate(t.header); };
			div.title = "Previous page";
			img.src = "/static/data_model/left.png";
			div.doit = function() {
				t._page_num--;
				t._loadData();
			};
			div.appendChild(img);
			t.header_left.appendChild(div);
		}
		// + page number
		t._page_num_div = div = document.createElement("DIV");
		div.style.display = "inline-block";
		t.header_left.appendChild(div);
		if (t._page_size > 0) {
			// + next page
			t.next_page_button = div = document.createElement("BUTTON");
			div.disabled = "disabled";
			img = document.createElement("IMG"); img.onload = function() { layout.invalidate(t.header); };
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
					t._page_size_field = new field_integer(t._page_size, true, {can_be_null:false,min:1,max:2000});
					t._page_size_field.onchange.add_listener(function() {
						t._page_size = t._page_size_field.getCurrentData();
						t._loadData();
					});
					_page_size_div.appendChild(t._page_size_field.getHTMLElement());
					if (t.header && t.header.widget && t.header.widget.layout)
						t.header.widget.layout();
				});
			});
		}
		// + refresh
		div = document.createElement("BUTTON");
		img = document.createElement("IMG"); img.onload = function() { layout.invalidate(t.header); };
		div.title = "Refresh";
		img.src = theme.icons_16.refresh;
		div.onclick = function() { t._loadData(); };
		div.appendChild(img);
		t.header_left.appendChild(div);
		t.refresh_button = div;
		// + select column
		div = document.createElement("BUTTON");
		img = document.createElement("IMG"); img.onload = function() { layout.invalidate(t.header); };
		div.title = "Select columns to display";
		img.src = get_script_path("data_list.js")+"/table_column.png";
		div.onclick = function() { t._selectColumnsDialog(this); };
		div.appendChild(img);
		t.header_right.appendChild(div);
		// + filter
		div = document.createElement("BUTTON");
		img = document.createElement("IMG"); img.onload = function() { layout.invalidate(t.header); };
		div.title = "Filters";
		img.src = get_script_path("data_list.js")+"/filter.gif";
		div.onclick = function() { t._filtersDialog(this); };
		div.appendChild(img);
		t.header_right.appendChild(div);
		t._filterNumber = document.createElement("DIV");
		t._filterNumber.style.display = "inline-block";
		t._filterNumber.style.position = "absolute";
		div.style.position = "relative";
		t._filterNumber.style.bottom = "1px";
		t._filterNumber.style.right = "1px";
		t._filterNumber.style.backgroundColor = "#00A000";
		t._filterNumber.style.color = "#FFFFFF";
		t._filterNumber.style.fontSize = "8px";
		t._filterNumber.style.padding = "0px 2px 0px 2px";
		setBorderRadius(t._filterNumber,2,2,2,2,2,2,2,2);
		if (t._filters.length > 0)
			t._filterNumber.innerHTML = t._filters.length;
		else
			t._filterNumber.style.visibility = "hidden";
		div.appendChild(t._filterNumber);
		// + export
		div = document.createElement("BUTTON");
		img = document.createElement("IMG"); img.onload = function() { layout.invalidate(t.header); };
		div.title = "Export list";
		img.src = theme.icons_16["_export"];
		div.onclick = function() { t._exportMenu(this); };
		div.appendChild(img);
		t.header_right.appendChild(div);
		// + print
		div = document.createElement("BUTTON");
		img = document.createElement("IMG"); img.onload = function() { layout.invalidate(t.header); };
		div.title = "Print";
		img.src = theme.icons_16["print"];
		div.onclick = function() { t.print(); };
		div.appendChild(img);
		t.header_right.appendChild(div);
		// + more button for horizontal menu
		div = document.createElement("BUTTON");
		img = document.createElement("IMG"); img.onload = function() { layout.invalidate(t.header_center); };
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
		if (container.style.height)
			require("vertical_layout.js",function(){
				new vertical_layout(container);
				layout.invalidate(container);
			});
		require("horizontal_layout.js",function(){
			new horizontal_layout(t.header);
			layout.invalidate(container);
		});
		require("horizontal_menu.js",function(){
			new horizontal_menu(t.header_center, "middle");
			layout.invalidate(container);
		});
		require("vertical_align.js",function(){
			new vertical_align(t.header_left, "middle");
			new vertical_align(t.header_right, "middle");
			layout.invalidate(container);
		});
	};
	/** Load the available fields for the root table */
	t._loadFields = function() {
		require("DataDisplay.js",function() {
			service.json("data_model","get_available_fields",{table:t._root_table,sub_model:t._sub_model},function(result){
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
		// add border radius if needed
		var radius = getBorderRadius(t.container);
		setBorderRadius(t.header, radius[0],radius[0], radius[1], radius[1], 0,0, 0,0);
		setBorderRadius(t.header.childNodes[0], radius[0],radius[0], 0,0, 0,0, 0,0);
		setBorderRadius(t.header.childNodes[t.header.childNodes.length-1], 0,0, radius[1], radius[1], 0,0, 0,0);
		setBorderRadius(t.grid.element, 0,0, 0,0, radius[2],radius[2], radius[3], radius[3]);
		// compute visible fields
		t.show_fields = [];
		for (var i = 0; i < initial_data_shown.length; ++i) {
			var j = initial_data_shown[i].indexOf('.');
			var cat, name = null, sub_data_index = -1;
			if (j == -1) cat = initial_data_shown[i];
			else {
				cat = initial_data_shown[i].substring(0,j);
				name = initial_data_shown[i].substring(j+1);
				j = name.indexOf('.');
				if (j != -1) {
					sub_data_index = parseInt(name.substring(j+1));
					name = name.substring(0,j);
				}
			}
			var found = false;
			for (var j = 0; j < t._available_fields.length; ++j) {
				if (t._available_fields[j].category != cat) continue;
				if (name == null || t._available_fields[j].name == name) {
					t.show_fields.push({field:t._available_fields[j],sub_index:sub_data_index});
					found = true;
				}
			}
			if (!found) alert("Data '"+initial_data_shown[i]+"' does not exist in the list of available data");
		}
		// initialize grid
		t._loadTypedFields(function(){
			for (var i = 0; i < t.show_fields.length; ++i) {
				var f = t.show_fields[i];
				var found = -1;
				for (var j = 0; j < i; ++j) if (t.show_fields[j].field == f.field) { found = j; break; };
				var col = t._createColumn(f);
				if (found == -1) {
					if (f.sub_index < 0) {
						t.grid.addColumn(col);
					} else {
						// first time we have a sub_index for this field: create the parent column
						var container = new GridColumnContainer(f.field.name, [col], f.field);
						t.grid.addColumnContainer(container);
					}
				} else {
					if (f.sub_index < 0) {
						// duplicate
						t.show_fields.splice(i,1);
						i--;
						continue;
					}
					var container = t.grid.getColumnContainerByAttachedData(f.field);
					container.addSubColumn(col);
				}
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
		var args = objectCopy(f.field.field_config);
		if (f.sub_index != -1)
			args.sub_data_index = f.sub_index;
		var col = new GridColumn(
			f.field.category+'.'+f.field.name+'.'+f.sub_index, //id
			f.sub_index == -1 ? f.field.name : f.field.sub_data.names[f.sub_index], // title
			null, // width
			f.field.horiz_align, // align
			f.field.field_classname, // field_type 
			false, // editable
			null, // onchanged
			null, // onunchanged
			args, // field_args
			f // attached_data
		);
		if (f.field.sortable)
			col.addExternalSorting(function(_sort_order){
				t._sort_column = col;
				t._sort_order = _sort_order;
				t._loadData();
			});
		col.onchanged = function(field, data) {
			t._cellChanged(field);
		};
		col.onunchanged = function(field) {
			t._cellUnchanged(field);
		};
		if (f.field.editable) {
			col.addAction(new GridColumnAction(theme.icons_16.edit,function(ev,action,col){
				var edit_col = function() {
					action.icon = col.editable ? theme.icons_16.edit : theme.icons_16.no_edit;
					action.tooltip = col.editable ? "Edit data on this column" : "Cancel modifications and stop editing this column";
					col.toggleEditable();
					layout.invalidate(container);
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
					for (var i = 0; i < f.field.edit_locks.length; ++i) {
						var service_name;
						if (f.field.edit_locks[i].column)
							service_name = "lock_column";
						else
							service_name = "lock_table";
						service.json("data_model",service_name,f.field.edit_locks[i],function(result){
							done++;
							if (result) locks.push(result.lock);
							if (done == f.field.edit_locks.length) {
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
			}, "Edit data on this column"));
		}
		return col;
	};
	t.startLoading = function() {
		if (t._loading_hidder) return;
		t._loading_hidder = new LoadingHidder(container);
		t._loading_hidder.setContent("<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Loading data...");
	};
	t.endLoading = function() {
		if (!t._loading_hidder) return;
		t._loading_hidder.remove();
		t._loading_hidder = null;
	};

	/** (Re)load the data from the server */
	t._loadData = function(onready) {
		t.startLoading();
		var fields = [];
		for (var i = 0; i < t.show_fields.length; ++i) {
			var found = false;
			for (var j = 0; j < fields.length; ++j)
				if (fields[j].path == t.show_fields[i].field.path.path && fields[j].name == t.show_fields[i].field.name) { found = true; break; }
			if (!found)
				fields.push({path:t.show_fields[i].field.path.path,name:t.show_fields[i].field.name});
		}
		var params = {table:t._root_table,sub_model:t._sub_model,fields:fields,actions:true};
		if (t._page_size > 0) {
			params.page = t._page_num;
			params.page_size = t._page_size;
		}
		if (t._sort_column && t._sort_order != 3) {
			params.sort_field = t._sort_column.id;
			var i = params.sort_field.lastIndexOf('.');
			params.sort_field = params.sort_field.substring(0,i);
			params.sort_order = t._sort_order == 1 ? "ASC" : "DESC";
		}
		params.filters = t._filters;
		service.json("data_model","get_data_list",params,function(result){
			if (!result) {
				if (onready) onready();
				t.grid.endLoading();
				return;
			}
			if (t._page_size > 0) {
				var start = (t._page_num-1)*t._page_size+1;
				var end = (t._page_num-1)*t._page_size+result.data.length;
				if (end == 0)
					t._page_num_div.innerHTML = "0";
				else
					t._page_num_div.innerHTML = start+"-"+end+"/"+result.count;
				if (start > 1) {
					t.prev_page_button.disabled = "";
					t.prev_page_button.onclick = t.prev_page_button.doit;
				} else {
					t.prev_page_button.disabled = "disabled";
					t.prev_page_button.onclick = null;
				}
				if (end < result.count) {
					t.next_page_button.disabled = "";
					t.next_page_button.onclick = t.next_page_button.doit;
				} else {
					t.next_page_button.disabled = "disabled";
					t.next_page_button.onclick = null;
				}
			} else
				t._page_num_div.innerHTML = result.data.length;
			layout.invalidate(t.header);
			t.data = result.data;
			if (t.data.length == 0) {
				t.grid.setData([]);
				t.grid.addTitleRow("No result found", {textAlign:"center",padding:"2px 5px",fontStyle:"italic"});
			} else {
				var data = [];
				var cols = [];
				for (var i = 0; i < t.show_fields.length; ++i)
					for (var j = 0; j < t.grid.columns.length; ++j)
						if (t.grid.columns[j].attached_data && t.grid.columns[j].attached_data == t.show_fields[i]) {
							cols.push(t.grid.columns[j]);
							break;
						}
				var col_pic = t.grid.getColumnById("data_list_picture"); 
				for (var i = 0; i < t.data.length; ++i) {
					var row = {row_id:i,row_data:[]};
					for (var j = 0; j < t.show_fields.length; ++j)
						row.row_data.push({col_id:cols[j].id,data_id:null,css:"disabled"});
					for (var j = 0; j < fields.length; ++j) {
						var value = t.data[i].values[j];
						for (var k = 0; k < t.show_fields.length; ++k) {
							if (t.show_fields[k].field.path.path == fields[j].path && t.show_fields[k].field.name == fields[j].name) {
								row.row_data[k].col_id = cols[k].id;
								if (value.k == null && (cols[j].editable || value.v == null)) {
									row.row_data[k].data_id = null;
									row.row_data[k].css = "disabled";
								} else {
									row.row_data[k].data_id = value.k;
									row.row_data[k].data = value.v;
									row.row_data[k].css = null;
								}
							}
						}
					}
					if (col_pic != null)
						row.row_data.splice(0,0,{col_id:"data_list_picture",data_id:null,data:null});
					data.push(row);
					if (t._col_actions)
						row.row_data.push({col_id:'actions',data_id:null,data:""});
				}
				t.grid.setData(data);
				if (t._col_actions)
					t._populateActions();
				if (col_pic != null) {
					for (var i = 0; i < t.grid.getNbRows(); ++i) {
						var field = t.grid.getCellField(i, 0);
						if (field == null) return;
						t._picture_provider(field.getHTMLElement(), t.getTableKeyForRow(t._picture_table, i), t._pic_width, t._pic_height);
					}
				}
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
								window.top.datamodel.registerDataWidget(window, data_display.field, data_key, this.field.element, function() {
									return t.field.getCurrentData();
								}, function(data) {
									t.field.setData(data);
								}, function(listener) {
									t.field.onchange.add_listener(listener);
								}, function(listener) {
									t.field.onchange.remove_listener(listener);
								});
							}
						};
						closure.register(col.attached_data, td.data_id);
					}
				}
			}
			t.ondataloaded.fire(t);
			t.endLoading();
			if (onready) onready();
		});
	};
	t._populateActions = function() {
		if (t._col_actions == null) return;
		for (var i = 0; i < t.grid.getNbRows(); ++i) {
			var row = t.grid.getRow(i);
			for (var j = 0; j < row.childNodes.length; ++j)
				if (row.childNodes[j].col_id == "actions") {
					var html_field = row.childNodes[j].field;
					var container = document.createElement("DIV");
					for (var k = 0; k < t._action_providers.length; ++k)
						t._action_providers[k](t, row, container);
					html_field.setData(container);
					break;
				}
		}
	};
	/** Show the menu to select the columns/fields to display
	 * @param {DOMNode} button the menu will be display below this element
	 */
	t._selectColumnsDialog = function(button) {
		require("context_menu.js");
		window.top.theme.css("data_list.css"); // the context menu goes to window.top, we need the stylesheet
		var categories = [];
		for (var i = 0; i < t._available_fields.length; ++i)
			if (!categories.contains(t._available_fields[i].category))
				categories.push(t._available_fields[i].category);
		var dialog = document.createElement("DIV");
		var table = document.createElement("TABLE"); dialog.appendChild(table);
		table.className = "data_list_select_fields_menu";
		var tr_head = document.createElement("TR"); table.appendChild(tr_head);
		tr_head.className = "header";
		var tr_content = document.createElement("TR"); table.appendChild(tr_content);
		tr_content.className = "content";
		for (var i = 0; i < categories.length; ++i) {
			var td = document.createElement("TD");
			tr_head.appendChild(td);
			td.appendChild(document.createTextNode(categories[i]));
			td.style.whiteSpace = "nowrap";
			td = document.createElement("TD");
			td.style.whiteSpace = "nowrap";
			tr_content.appendChild(td);
			for (var j = 0; j < t._available_fields.length; ++j) {
				var f = t._available_fields[j];
				if (f.category != categories[i]) continue;
				var cb = document.createElement("INPUT"); cb.type = 'checkbox';
				cb.data = f;
				var found = -1;
				for (var k = 0; k < t.show_fields.length; ++k)
					if (t.show_fields[k].field.path.path == t._available_fields[j].path.path &&
						t.show_fields[k].field.name == t._available_fields[j].name) { found = k; break; }
				if (found != -1 && t.show_fields[found].sub_index == -1)
					cb.checked = 'checked';
				cb.onclick = function() {
					if (this.checked) {
						if (this._sub_cb)
							for (var k = 0; k < this._sub_cb.length; ++k) {
								this._sub_cb[k].checked = "";
								this._sub_cb[k].disabled = "disabled";
								t.hideSubField(this.data, k);
							}
						t.showField(this.data);
					} else {
						t.hideField(this.data);
						if (this._sub_cb)
							for (var k = 0; k < this._sub_cb.length; ++k) {
								this._sub_cb[k].disabled = "";
							}
					}
				};
				td.appendChild(cb);
				td.appendChild(document.createTextNode(f.name));
				td.appendChild(document.createElement("BR"));
				if (f.sub_data) {
					cb._sub_cb = [];
					var sub_div = document.createElement("DIV");
					sub_div.className = "sub_data";
					for (k = 0; k < f.sub_data.names.length; ++k) {
						var sub_cb = document.createElement("INPUT");
						sub_cb.type = "checkbox";
						if (found != -1 && t.show_fields[found].sub_index == -1)
							sub_cb.disabled = "disabled";
						else {
							var sub_found = false;
							for (var l = 0; l < t.show_fields.length; ++l)
								if (t.show_fields[l].field.path.path == t._available_fields[j].path.path &&
									t.show_fields[l].field.name == t._available_fields[j].name &&
									t.show_fields[l].sub_index == k) { sub_found = true; break; }
							if (sub_found)
								sub_cb.checked = "checked";
						}
						sub_div.appendChild(sub_cb);
						sub_div.appendChild(document.createTextNode(f.sub_data.names[k]));
						sub_div.appendChild(document.createElement("BR"));
						sub_cb._parent_cb = cb;
						sub_cb._index = k;
						cb._sub_cb.push(sub_cb);
						sub_cb.onchange = function() {
							if (this.checked) {
								t.showSubField(this._parent_cb.data, this._index);
							} else {
								t.hideSubField(this._parent_cb.data, this._index);
							}
						};
					}
					td.appendChild(sub_div);
				}
			}
		}
		require("context_menu.js",function(){
			var menu = new context_menu();
			menu.removeOnClose = true;
			menu.addItem(dialog, true);
			menu.showBelowElement(button);
		});
	};
	t._contextMenuAddFilter = function(button, onselect) {
		require("context_menu.js");
		window.top.theme.css("data_list.css"); // the context menu goes to window.top, we need the stylesheet
		var categories = [];
		for (var i = 0; i < t._available_fields.length; ++i)
			if (!categories.contains(t._available_fields[i].category))
				categories.push(t._available_fields[i].category);
		var fields_menu = document.createElement("DIV");
		var table = document.createElement("TABLE"); fields_menu.appendChild(table);
		table.className = "data_list_select_fields_menu";
		var tr_head = document.createElement("TR"); table.appendChild(tr_head);
		tr_head.className = "header";
		var tr_content = document.createElement("TR"); table.appendChild(tr_content);
		tr_content.className = "content";
		for (var i = 0; i < categories.length; ++i) {
			var td = document.createElement("TD");
			tr_head.appendChild(td);
			td.appendChild(document.createTextNode(categories[i]));
			td.style.whiteSpace = "nowrap";
			td = document.createElement("TD");
			td.style.whiteSpace = "nowrap";
			tr_content.appendChild(td);
			for (var j = 0; j < t._available_fields.length; ++j) {
				var f = t._available_fields[j];
				if (f.category != categories[i]) continue;
				// check if a filter already exists on that field
				var filter_exists = false;
				for (var k = 0; k < t._filters.length; ++k) {
					if (t._filters[k].category == f.category && t._filters[k].name == f.name) { filter_exists = true; break; }
					var o = t._filters[k].or;
					while (o) {
						if (o.category == f.category && o.name == f.name) { filter_exists = true; break; }
						o = o.or;
					}
					if (filter_exists) break;
				}
				// create the menu item
				var field_div = document.createElement("DIV"); td.appendChild(field_div);
				field_div.appendChild(document.createTextNode(f.name));
				field_div.className = "context_menu_item";
				field_div.field = f;
				if (f.filter_classname && (window[f.filter_classname].prototype.can_multiple || !filter_exists)) {
					field_div.onclick = function() {
						var filter = {
							category: this.field.category,
							name: this.field.name,
							force: false,
							data: null
						};
						onselect(this.field, filter);
					};
				} else {
					field_div.className += " disabled";
				}
				if (f.sub_data) {
					for (var k = 0; k < f.sub_data.names.length; ++k) {
						// check if a filter already exists on that field
						var filter_exists = false;
						for (var l = 0; l < t._filters.length; ++l) {
							if (t._filters[l].category == f.category && t._filters[l].name == f.name && t._filters[l].sub_index == k) { filter_exists = true; break; }
							var o = t._filters[l].or;
							while (o) {
								if (o.category == f.category && o.name == f.name && o.sub_index == k) { filter_exists = true; break; }
								o = o.or;
							}
							if (filter_exists) break;
						}
						// create the item
						var sub_div = document.createElement("DIV"); td.appendChild(sub_div);
						sub_div.className = "sub_data context_menu_item";
						sub_div.appendChild(document.createTextNode(f.sub_data.names[k]));
						sub_div.sub_index = k;
						sub_div.field = f;
						if (f.sub_data.filters && f.sub_data.filters[k].classname && (window[f.sub_data.filters[k].classname].prototype.can_multiple || !filter_exists)) {
							sub_div.onclick = function() {
								var filter = {
									category: this.field.category,
									name: this.field.name,
									sub_data: this.sub_index,
									force: false,
									data: null
								};
								onselect(this.field, filter);
							};
						} else {
							sub_div.className += " disabled";
						}
					}
				}
			}
		}
		require("context_menu.js", function() {
			var menu = new context_menu();
			menu.addItem(fields_menu);
			menu.showBelowElement(button);
		});
	};
	/** Create the display for a filter, inside the given table
	 * @param {Object} filter the filter
	 * @param {DOMNode} container where to display the filter
	 */
	t._createFilter = function(filter, container, is_or) {
		var div = document.createElement("DIV");
		container.appendChild(div);
		div.className = "data_list_filter";
		div.style.verticalAlign = "bottom";
		
		// find the corresponding field
		var field = null;
		for (var i = 0; i < t._available_fields.length; ++i)
			if (t._available_fields[i].category == filter.category && t._available_fields[i].name == filter.name) {
				field = t._available_fields[i];
				break;
			}
		
		// remove button
		var remove = document.createElement("BUTTON");
		remove.className = "flat";
		remove.title = "Remove this filter on "+field.name;
		remove.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
		if (filter.force) remove.disabled = 'disabled';
		div.appendChild(remove);
		remove.filter = filter;
		remove.onclick = function() {
			t.removeFilter(filter);
			container.removeChild(div);
			t._loadData();
			if (t._filters.length == 0)
				container.innerHTML = "<center><i>No filter</i></center>";
			layout.invalidate(container);
		};
		
		if (is_or) {
			var div_or_text = document.createElement("DIV");
			div_or_text.style.display = "inline-block";
			div_or_text.style.verticalAlign = "bottom";
			div_or_text.style.marginRight = "4px";
			div_or_text.style.fontStyle = "italic";
			div_or_text.appendChild(document.createTextNode("Or"));
			div.appendChild(div_or_text);
		}
		
		// field name
		var div_name = document.createElement("DIV");
		div_name.style.display = "inline-block";
		div_name.style.verticalAlign = "bottom";
		div_name.style.marginRight = "4px";
		div_name.appendChild(document.createTextNode(field.name+(typeof filter.sub_index != 'undefined' && filter.sub_index >= 0 ? "/"+field.sub_data.names[filter.sub_index] : "")+" "));
		div.appendChild(div_name);
		
		// filter
		var classname, config;
		if (typeof filter.sub_index != 'undefined' && filter.sub_index >= 0) {
			classname = field.sub_data.filters[filter.sub_index].classname;
			config = field.sub_data.filters[filter.sub_index].config;
		} else {
			classname = field.filter_classname;
			config = field.filter_config;
		}
		var f = new window[classname](filter.data, config, !filter.force);
		div.appendChild(f.getHTMLElement());
		f.getHTMLElement().style.verticalAlign = "bottom";
		f.onchange.add_listener(function (f) {
			filter.data = f.getData();
			t._loadData();
		});
		
		if (!is_or) {
			// or
			var or_div = document.createElement("DIV");
			or_div.className = "or_container";
			div.appendChild(or_div);
			
			var last_or = filter;
			while (last_or.or) {
				t._createFilter(last_or.or, or_div, true);
				last_or = last_or.or;
			}
			
			// add or button
			var add_or = document.createElement("BUTTON");
			add_or.innerHTML = "Or...";
			or_div.appendChild(add_or);
			add_or.onclick = function() {
				t._contextMenuAddFilter(this, function(field, new_filter) {
					last_or = filter;
					while (last_or.or) last_or = last_or.or;
					last_or.or = new_filter;
					t._createFilter(new_filter, or_div, true);
					or_div.removeChild(add_or);
					or_div.appendChild(add_or);
					layout.invalidate(container);
					t._loadData();
				});
			};
		}
	},
	/** Display the menu to edit/add/remove filters
	 * @param {DOMNode} button the menu will be displayed below this element
	 */
	t._filtersDialog = function(button) {
		require("typed_filter.js");
		require("popup_window.js");
		
		// get all typed filter classes, to load javascripts
		var filter_classes = [];
		for (var i = 0; i < t._available_fields.length; ++i) {
			var f = t._available_fields[i];
			if (!f.filter_classname) continue;
			filter_classes.push(f.filter_classname+".js");
		}
		require([["typed_filter.js",filter_classes]]);

		// content of the popup window
		var filters_content = document.createElement("DIV");
		filters_content.style.padding = "5px";
		
		// load what will be needed for the context menu, so it is ready already
		require("context_menu.js");
		window.top.theme.css("data_list.css"); // the context menu goes to window.top, we need the stylesheet
		
		// create filters to put in the dialog
		require([["typed_filter.js",filter_classes]], function() {
			if (t._filters.length == 0) {
				filters_content.innerHTML = "<center><i>No filter</i></center>";
			} else {
				for (var i = 0; i < t._filters.length; ++i)
					t._createFilter(t._filters[i], filters_content);
			}
			require("popup_window.js", function() {
				var popup = new popup_window("Filters", "/static/data_model/filter.gif", filters_content);
				popup.addIconTextButton(theme.build_icon("/static/data_model/filter.gif",theme.icons_10.add),"Add filter",'add_filter', function() {
					t._contextMenuAddFilter(this, function(field, filter) {
						if (t._filters.length == 0) filters_content.removeAllChildren();
						t.addFilter(filter);
						t._createFilter(filter, filters_content);
						layout.invalidate(filters_content);
						t._loadData();
					});
				});
				popup.show();
			});
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
	t._download_frame = null;
	/** Launch the export in the given format
	 * @param {String} format format to export
	 */
	t._exportList = function(format) {
		// TODO support sub fields
		var fields = [];
		for (var i = 0; i < t.show_fields.length; ++i)
			fields.push({path:t.show_fields[i].field.path.path,name:t.show_fields[i].field.name});
		var form = document.createElement("FORM");
		var input;
		form.appendChild(input = document.createElement("INPUT"));
		form.action = "/dynamic/data_model/service/get_data_list";
		form.method = 'POST';
		input.type = 'hidden';
		input.name = 'table';
		input.value = t._root_table;
		if (t._sub_model) {
			form.appendChild(input = document.createElement("INPUT"));
			input.type = 'hidden';
			input.name = 'sub_model';
			input.value = t._sub_model;
		}
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
		if (t._download_frame) document.body.removeChild(t._download_frame);
		var frame = document.createElement("IFRAME");
		frame.style.position = "absolute";
		frame.style.top = "-10000px";
		frame.style.visibility = "hidden";
		frame.name = "data_list_download";
		document.body.appendChild(frame);
		form.target = "data_list_download";
		document.body.appendChild(form);
		form.submit();
		t._download_frame = frame;
		window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_INFO,"Your file is being generated, and the download will start soon...",[{action:"close"}],5000));
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
			var f = t.grid.getCellField(i, index);
			if (f)
				f.setData(f.getOriginalData());
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
					layout.invalidate(t.header_left);
				}
			} else {
				// display save button
				if (t.save_button == null) {
					t.save_button = document.createElement("BUTTON");
					var img = document.createElement("IMG"); img.onload = function() { layout.invalidate(t.header); };
					t.save_button.title = "Save Changes";
					img.src = theme.icons_16.save;
					t.save_button.onclick = function() { t._save(); };
					t.save_button.appendChild(img);
					if (t.refresh_button.nextSibling)
						t.header_left.insertBefore(t.save_button, t.refresh_button.nextSibling);
					else
						t.header_left.appendChild(t.save_button);
					window.pnapplication.dataUnsaved(container.id);
					layout.invalidate(t.header_left);
					setTimeout(function() { if (!t.save_button) return; t.save_button.style.borderColor = "red"; t.save_button.style.backgroundColor = "red"; },100);
					setTimeout(function() { if (!t.save_button) return; t.save_button.style.borderColor = ""; t.save_button.style.backgroundColor = ""; },400);
					setTimeout(function() { if (!t.save_button) return; t.save_button.style.borderColor = "red"; t.save_button.style.backgroundColor = "red"; },600);
					setTimeout(function() { if (!t.save_button) return; t.save_button.style.borderColor = ""; t.save_button.style.backgroundColor = ""; },900);
					setTimeout(function() { if (!t.save_button) return; t.save_button.style.borderColor = "red"; t.save_button.style.backgroundColor = "red"; },1100);
					setTimeout(function() { if (!t.save_button) return; t.save_button.style.borderColor = ""; t.save_button.style.backgroundColor = ""; },1400);
					setTimeout(function() { if (!t.save_button) return; t.save_button.style.borderColor = "red"; t.save_button.style.backgroundColor = "red"; },1600);
					setTimeout(function() { if (!t.save_button) return; t.save_button.style.borderColor = ""; t.save_button.style.backgroundColor = ""; },1900);
					setTimeout(function() { if (!t.save_button) return; t.save_button.style.borderColor = "red"; t.save_button.style.backgroundColor = "red"; },2100);
					setTimeout(function() { if (!t.save_button) return; t.save_button.style.borderColor = ""; t.save_button.style.backgroundColor = ""; },2400);
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
			window.pnapplication.dataSaved(container.id);
			layout.invalidate(t.header_left);
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
			var found = false;
			for (var i = 0; i < to_save.length; ++i)
				if (to_save[i].path == f.field.path.path && to_save[i].name == f.field.name && to_save[i].key == data_id) { found = true; break; }
			if (found) continue; // already there, probably 2 sub data display from the same parent data
			to_save.push({
				path: f.field.path.path,
				name: f.field.name,
				key: data_id,
				value: value
			});
			t._changed_cells[i].setOriginalData(value);
		}
		service.json("data_model","save_data",{root_table:t._root_table,sub_model:t._sub_model,to_save:to_save},function(result){
			if (result) {
				for (var i = 0; i < t._changed_cells.length; ++i) {
					var value = t._changed_cells[i].getCurrentData();
					t._changed_cells[i].setOriginalData(value);
				}
				t._changed_cells = [];
				if (t.save_button) {
					t.header_left.removeChild(t.save_button);
					t.save_button = null;
					layout.invalidate(t.header_left);
					window.pnapplication.dataSaved(container.id);
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
		row.onmouseout = function() { this.className = t.grid.isSelected(row.row_id) ? "selected" : ""; };
		row.style.cursor = 'pointer';
		row.onclick = function(ev) {
			if (ev.target.nodeType == 1 && (ev.target.nodeName == 'INPUT' || ev.target.nodeName == 'SELECT')) return;
			var e = ev.target;
			do {
				if (e.nodeType == 1) {
					if (e.nodeName == "TR") break;
					if (e.nodeName == "TD") break;
					if (e.nodeName == "TH") return; // this is the cell with the checkbox
				}
				e = e.parentNode;
			} while (e && e.nodeName != "TR");
			t._rowOnclick(this);
		};
	};
}
