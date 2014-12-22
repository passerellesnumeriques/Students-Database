if (typeof require != 'undefined') {
	require("datadisplay.js");
	require("grid.js");
	require("horizontal_menu.js");
	require("typed_field.js",function(){
		require("field_text.js");
		require("field_html.js");
		require("field_integer.js");
	});
	require("context_menu.js");
	theme.css("data_list.css");
}
/** A data list is a generic view of data: starting from a table, the user can choose what data to display, apply filters, sort data...
 * It provides an additional layer on top of a grid, adding the capacity to automatically retrieve the list of columns we can display,
 * and to automatically get the data from the back-end.
 * @param {Element} container where to put it
 * @param {String} root_table starting point in the data model
 * @param {Number} sub_model sub model of the root table, or null
 * @param {Array} initial_data_shown list of data to show at the beginning, with format 'Category'.'Name' where Category is the category of the DataDisplayHandler, and Name is the display name of the DataDisplay
 * @param {Array} filters list of {category:a,name:b,force:c,data:d,or:e}: category = from DataDisplayHandler; name = display name of the DataDisplay; force = true if the user cannot remove it; data = data of the filter, format depends on filter type; or=another filter data to do a 'or' condition
 * @param {Number} page_size maximum number of rows to display, or -1 to disable paging.
 * @param {String} default_sort if specified, at the beginning the data will be sorted by the given column
 * @param {Boolean} default_sort_asc if a default sort is given, it specifies if it should be ascending order or descending order
 * @param {Function} onready called when everything is ready, and we can start to use this object
 */
function data_list(container, root_table, sub_model, initial_data_shown, filters, page_size, default_sort, default_sort_asc, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	if (!page_size) page_size = -1;
	var t=this;
	window.to_cleanup.push(t);
	/** Clean all parameters to avoid memory leaks */
	t.cleanup = function() {
		container = null;
		t.data = null;
		t.more_menu = null;
		t.next_page_button = null;
		t.prev_page_button = null;
		t._changed_cells = null;
		t._loading_hidder = null;
		t._page_size_field = null;
		t._picture_provider = null;
		t._rowOnclick = null;
		t.refresh_button = null;
		t.grid_container = null;
		t.header_center = null;
		t.header_left = null;
		t.header_right = null;
		t.header = null;
		t._filterNumber = null;
		t._page_num_div = null;
		t.footer = null;
		t.footer_tools = null;
		t.container = null;
		t.grid = null;
		t.show_fields = null;
		t.always_fields = null;
		t.save_button = null;
		t._available_fields = null;
		t._sort_column = null;
		t._filters = null;
		t._action_providers = null;
		t._col_actions = null;
		t = null;
	};
	t.container = container;
	t.container.className = t.container.className ? "data_list "+t.container.className : "data_list";
	if (!t.container.id) t.container.id = generateID();

	/* Public properties */
	
	/** {grid} the data list use the grid widget to display data, we can access it directly here */
	t.grid = null;
	/** List of fields shown. This is not directly the DataDisplay, but objects {field(DataDisplay),sub_index(-1 if no sub data),forced(the user cannot remove it)} */
	t.show_fields = [];
	/** List of fields that should always retrieved from the back-end, even if it is not displayed */
	t.always_fields = [];
	/** Event fired when data has been loaded/refreshed */
	t.ondataloaded = new Custom_Event();
	/** Event fired when something changed in the filters, and we should refresh */
	t.onfilterschanged = new Custom_Event();
	
	/* Public methods */
	
	/** Add some html in the header of the data list
	 * @param {Element} html element or string
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
		layout.changed(t.header);
	};
	/** Remove everything in the header, previously added through addHeader */
	t.resetHeader = function() {
		if (t.header_center.widget)
			t.header_center.widget.removeAll();
		else
			while (t.header_center.childNodes.length > 0) t.header_center.removeChild(t.header_center.childNodes[0]);
		layout.changed(t.header);
	};
	/** Add an HTML element at the bottom, after the grid.
	 * The difference with addFooter is that we will ensure all tools will be together, even some other footers have been added
	 * @param {Element|String} html the element, or its HTML in a string
	 * @returns {Element} the element added
	 */
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
			layout.changed(container);
		}
		t.footer_tools.appendChild(item);
		layout.changed(t.footer_tools);
		return item;
	};
	/** Remove an element from the footer tools
	 * @param {Element} item the element to remove
	 */
	t.removeFooterTool = function(item) {
		if (!t.footer_tools) return;
		t.footer_tools.removeChild(item);
		layout.changed(t.footer_tools);
	};
	/** Add an HTML element at the bottom, after the grid
	 * @param {Element|String} html the element, or its HTML in a string
	 */
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
			layout.changed(container);
		}
		t.footer.appendChild(item);
		layout.changed(t.footer);
	};
	/** Remove everything in the footer */
	t.resetFooter = function() {
		if (!t.footer) return;
		container.removeChild(t.footer);
		t.footer = null;
		layout.changed(container);
	};
	/** Set a title, with optionally an icon
	 * @param {String} icon URL of the icon 16x16, or null if no icon
	 * @param {String} text the title
	 */
	t.addTitle = function(icon, text) {
		var div = document.createElement("DIV");
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
		t.header.insertBefore(div, t.header_left);
		layout.changed(t.header);
	};
	/** Set the title, with some html
	 * @param {Element} html the html element, or a string
	 */
	t.setTitle = function(html) {
		if (typeof html == 'string') {
			var div = document.createElement("DIV");
			div.innerHTML = html;
			html = div;
		}
		html.className = "data_list_title";
		t.header.insertBefore(html, t.header_left);
		layout.changed(t.header);
	};
	/** Force to refresh the data from the server
	 * @param {Function} ondone if given, called when data is ready 
	 */
	t.reloadData = function(ondone) {
		t._loadData(ondone);
	};
	/** Remove all filters, and optionally add new ones
	 * @param {Boolean} remove_forced true to remove also the filters which cannot be remove by the user
	 * @param {Array} new_filters filters to add
	 */
	t.resetFilters = function(remove_forced, new_filters) {
		if (!new_filters) new_filters = [];
		if (remove_forced) {
			t._filters = new_filters;
			t.onfilterschanged.fire();
		} else {
			var changed = false;
			for (var i = 0; i < t._filters.length; ++i)
				if (!t._filters[i].force) {
					t._filters.splice(i,1);
					i--;
					changed = true;
				}
			if (new_filters.length > 0) {
				for (var i = 0; i < new_filters.length; ++i)
					t._filters.push(new_filters[i]);
				changed = true;
			}
			if (changed) t.onfilterschanged.fire();
		}
	};
	/** Add a new filter
	 * @param {Object} filter {category,name,force,data,or}
	 */
	t.addFilter = function(filter) {
		t._filters.push(filter);
		t.onfilterschanged.fire();
	};
	/** Remove the given filter
	 * @param {Object} filter the filter to remove
	 */
	t.removeFilter = function(filter) {
		for (var i = 0; i < t._filters.length; ++i) {
			if (t._filters[i] == filter) {
				t._filters.splice(i,1);
				break;
			}
			var found = false;
			var pos = t._filters[i];
			while (pos.or) {
				if (pos.or == filter) {
					found = true;
					pos.or = pos.or.or;
					break;
				}
				pos = pos.or;
			}
			if (found) break;
		}
		t.onfilterschanged.fire();
	};
	/** Return the list of filters currently active
	 * @returns {Array} list of filters {category,name,force,data,or}
	 */
	t.getFilters = function() { return t._filters; };
	/** Check if it exists a filter on the given field
	 * @param {String} category the category to search
	 * @param {String} name the name to search
	 * @returns {Boolean} true if a filter exists
	 */
	t.hasFilterOn = function(category, name) {
		for (var i = 0; i < t._filters.length; ++i) {
			if (t._filters[i].category == category && t._filters[i].name == name) return true;
			var pos = t._filters[i];
			while (pos.or) {
				if (pos.or.category == category && pos.or.name == name) return true;
				pos = pos.or;
			}
		}
		return false;
	};
	/** Remove any filter for the given field
	 * @param {String} category the category to search
	 * @param {String} name the name to search
	 */
	t.removeFiltersOn = function(category, name) {
		var changed = false;
		for (var i = 0; i < t._filters.length; ++i) {
			if (t._filters[i].category == category && t._filters[i].name == name) {
				t._filters.splice(i,1);
				i--;
				changed = true;
				continue;
			}
			var pos = t._filters[i];
			while (pos.or) {
				if (pos.or.category == category && pos.or.name == name) {
					t._filters.splice(i,1);
					i--;
					changed = true;
					break;
				}
				pos = pos.or;
			}
		}
		if (changed) t.onfilterschanged.fire();
	}
	/** Return the DataDisplay of the given field
	 * @param {String} category the category to search
	 * @param {String} name the name to search
	 * @returns {DataDisplay} the field if found, or null
	 */
	t.getField = function(category, name) {
		for (var i = 0; i < t._available_fields.length; ++i)
			if (t._available_fields[i].category == category && t._available_fields[i].name == name)
				return t._available_fields[i];
		return null;
	};
	/** Check if a field is displayed
	 * @param {DataDisplay} field the field
	 * @returns {Boolean} true if it is displayed
	 */
	t.isShown = function(field) {
		for (var i = 0; i < t.show_fields.length; ++i)
			if (t.show_fields[i].field == field)
				return true;
		return false;
	};
	/** Show the given field
	 * @param {DataDisplay} field the field to display
	 * @param {Boolean} forced if true, the user won't be able to remove it
	 * @param {Function} onready called done and data is ready
	 * @param {Boolean} no_reload if true, the data won't be loaded, the function onready will be immediately called, and a call to reloadData will be needed. This may be useful if we want to do several changes, before to reload.
	 */
	t.showField = function(field, forced, onready,no_reload) {
		t.showFields([field],forced,onready,no_reload);
	};
	/** Show the given fields
	 * @param {Array} fields the list of DataDisplay of the fields to display
	 * @param {Boolean} forced if true, the user won't be able to remove it
	 * @param {Function} onready called done and data is ready
	 * @param {Boolean} no_reload if true, the data won't be loaded, the function onready will be immediately called, and a call to reloadData will be needed. This may be useful if we want to do several changes, before to reload.
	 */	
	t.showFields = function(fields, forced, onready, no_reload) {
		var changed = false;
		for (var i = 0; i < fields.length; ++i) {
			var found = false;
			for (var j = 0; j < t.show_fields.length; ++j)
				if (t.show_fields[j].field == fields[i]) {
					if (forced && !t.show_fields[j].forced)
						t.show_fields[j].forced = true;
					found = true;
					break;
				};
			if (found) continue;
			var f = {field:fields[i],sub_index:-1,forced:forced};
			t.show_fields.push(f);
			var col = t._createColumn(f);
			t.grid.addColumn(col, t._col_actions != null ? t.grid.getColumnIndex(t._col_actions) : t.grid.getNbColumns());
			changed = true;
		}
		if (!changed || no_reload) {
			if (onready) onready();
			return;
		}
		t._loadData(onready);
	};
	/** Remove the given field
	 * @param {DataDisplay} field the field to hide
	 */
	t.hideField = function(field) {
		for (var i = 0; i < t.show_fields.length; ++i) {
			if (t.show_fields[i].field.path.path == field.path.path &&
				t.show_fields[i].field.name == field.name) {
				var col_id = t.getColumnIdFromField(t.show_fields[i],true)
				t.show_fields.splice(i,1);
				t.grid.removeColumn(t.grid.getColumnIndexById(col_id));
				break;
			}
		}
	};
	/** Show a sub-data from a field
	 * @param {DataDisplay} field the field
	 * @param {Number} sub_index index of the sub-data to display inside the field
	 */
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
				if (found_field == null) continue; // no data
				var new_field = t.grid.getCellField(i, col_index);
				if (new_field == null) continue; // no data
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
							t.field.onchange.addListener(listener);
						}, function(listener) {
							t.field.onchange.removeListener(listener);
						});
					}
				};
				closure.register(t.show_fields[found], data_id);
			}
		}
	};
	/** Remove a sub-data from a field
	 * @param {DataDisplay} field the field containing the sub-data
	 * @param {Number} sub_index index of the sub-data
	 */
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
	/** Ask to always retrieve the given data, even it is not displayed.
	 * This may be useful, if we need this data to do some additional process, or to modify the display of a row according to this data.
	 * @param {String} category the category of the data to always retrieve
	 * @param {String} name the name of the data to always retrieve
	 */
	t.alwaysGetField = function(category, name) {
		var f = t.getField(category,name);
		if (!f) return;
		if (t.always_fields.indexOf(f) >= 0) return;
		t.always_fields.push(f);
	};
	/** Retrieve the ID of the column corresponding to the given field
	 * @param {Object} f field from show_fields with format {field,sub_index,forced}
	 * @param {Boolean} skip_check_visible if true, the id will be returned even this field is not visible, meaning the column doesn't actually exist
	 * @returns {String} the id, or null if the field is not visible, and skip_check_visible was false
	 */
	t.getColumnIdFromField = function(f, skip_check_visible) {
		var id = f.field.category+'.'+f.field.name+'.'+f.sub_index;
		if (skip_check_visible) return id;
		var found = false;
		for (var i = 0; i < t.show_fields.length; ++i) if (t.show_fields[i] == f) { found = true; break; }
		if (!found) return null;
		return id;
	};
	
	/** Reset everything in the data list
	 * @param {String} root_table the new starting point
	 * @param {Number} sub_model the sub model of the root table
	 * @param {Array} filters the new filters
	 * @param {Number} page_size the new maximum number of rows, or -1 to disable paging
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
	/** Make a specific row not selectable
	 * @param {String} table name of the table
	 * @param {Number} key the key in the table, allowing to identify which row in the grid
	 */
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
	/** An action provider can provide some HTML elements to put in the actions column, for every row
	 * @param {Function} provider the action provider
	 */
	t.addActionProvider = function(provider) {
		t._action_providers.push(provider);
		if (!t._col_actions) {
			t._col_actions = new GridColumn('actions', "", null, "right", "field_html", false, null, null, {}, null);
			t.grid.addColumn(t._col_actions);
		}
		t._populateActions();
	};
	/** If pictures are supported, the user will have the possibility to switch between list, list with picture, and thumbnail
	 * @param {String} table name of the table the provider will need to know which picture to display
	 * @param {Function} picture_provider if specified, it provides the capacity to show a picture for every row. It will be called, for every row, with 4 parameters: container (where to put the picture), key (the key of the given table, identifying the row), width (width of the picture), height (height of the picture)
	 * @param {Function} thumbnail_provider if specified, it provides a thumbnail view.
	 */
	t.addPictureSupport = function(table, picture_provider, thumbnail_provider) {
		t._picture_table = table;
		t._picture_provider = picture_provider;
		require(["mac_tabs.js","field_html.js","pictures_list.js"], function() {
			var header = document.createElement("DIV");
			header.className = "header_right";
			header.style.display = "flex";
			header.style.height = "100%";
			header.style.justifyContent = "center";
			header.style.alignItems = "center";
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
			layout.changed(t.header);
			
			var col_picture = new GridColumn('data_list_picture', "Picture", null, "left", "field_html", false, null, null, {}, null);
			var thumb_container = document.createElement("DIV");
			thumb_container.style.overflow = "auto";
			thumb_container.style.flex = "1 1 100%";
			thumb_container.style.display = "none";
			t.thumb_container = thumb_container;

			var div_picture_size = document.createElement("DIV");
			div_picture_size.appendChild(document.createTextNode("Pic.size"));
			div_picture_size.style.marginRight = "3px";
			div_picture_size.style.fontSize = "11px";
			div_picture_size.style.display = "none";
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
				if (tabs.selected == "detail") {
					for (var i = 0; i < t.grid.getNbRows(); ++i) {
						var field = t.grid.getCellField(i, 0);
						if (field == null) continue;
						field.getHTMLElement().picture.setSize(t._pic_width, t._pic_height);
					}
				}
			};
			
			thumb_container.style.overflow = "auto";
			container.insertBefore(thumb_container, t.header.nextSibling);
			header.appendChild(div_picture_size);

			var original_load = t._loadData;
			tabs.onselect = function(id) {
				switch (id) {
				case "text":
					t._loadData = original_load;
					thumb_container.style.display = "none";
					div_picture_size.style.display = "none";
					t.grid_container.style.display = "flex";
					if (t.grid.getColumnById("data_list_picture") != null)
						t.grid.removeColumn(t.grid.getColumnIndex(col_picture));
					break;
				case "detail":
					t._loadData = function() {
						original_load(function() {
							t.grid.onallrowsready(function() {
								if (t.grid.getColumnById("data_list_picture") == null) return;
								for (var i = 0; i < t.grid.getNbRows(); ++i) {
									var field = t.grid.getCellField(i, 0);
									if (field == null) continue;
									picture_provider(field.getHTMLElement(), t.getTableKeyForRow(table, i), t._pic_width, t._pic_height);
								}
							});
						});
					};
					thumb_container.style.display = "none";
					div_picture_size.style.display = "inline-block";
					t.grid_container.style.display = "flex";
					if (t.grid.getColumnById("data_list_picture") == null)
						t.grid.addColumn(col_picture, 0);
					t._loadData();
					break;
				case "thumb":
					thumb_container.style.display = "block";
					div_picture_size.style.display = "inline-block";
					t.grid_container.style.display = "none";
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
					break;
				};
				layout.changed(container);
			};
		});
	};
	/** Print the content of the grid, or the thumbnail */
	t.print = function() {
		if (t.grid_container.style.display != "none")
			t.grid.print();
		else
			printContent(t.thumb_container);
	};
	/** Set the sort
	 * @param {String} field_category the category
	 * @param {String} field_name the name
	 * @param {Number} field_sub_index index of the sub-data, or -1
	 * @param {Boolean} asc true for ascending order, false for descending order
	 */
	t.orderBy = function(field_category, field_name, field_sub_index, asc) {
		for (var i = 0; i < t.grid.columns.length; ++i) {
			if (t.grid.columns[i].id == field_category+'.'+field_name+'.'+field_sub_index) {
				t.grid.columns[i].sort_order = asc ? 1 : 2;
				t.grid.columns[i]._refreshTitle();
				t._sort_column = t.grid.columns[i];
				t._sort_order = asc ? 1 : 2;
				if (t._data_loaded) t._loadData(); // reload to apply the sorting
				return;
			}
		}
		// no corresponding column: TODO support it ?
	};
	/** Provide a CSS class for rows
	 * @param {Function} provider called with 2 parameters: sent_fields, received_values, and returns a CSS class for the row, or null if no CSS
	 */
	t.setRowClassProvider = function(provider) {
		t._row_class_provider = provider;
	};
	
	/* Private properties */
	t._root_table = root_table;
	t._sub_model = sub_model;
	t._onready = onready;
	/** Indicates if we already loaded data */
	t._data_loaded = false;
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
	/** List of actions providers */
	t._action_providers = [];
	/** {GridColumn} last column for actions, or null if there is no such a column */
	t._col_actions = null;
	/** Provides CSS class for rows */
	t._row_class_provider = null;

	/* Private methods */
	
	/** Get the CSS class for a row
	 * @param {Array} sent_fields the fields requested
	 * @param {Array} received_values the values received for each field
	 */
	t._getRowClass = function(sent_fields, received_values) {
		if (!t._row_class_provider) return null;
		return t._row_class_provider(sent_fields, received_values);
	},
	
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
		t.header.appendChild(t.header_left);
		t.header_center = document.createElement("DIV");
		t.header_center.className = "header_center";
		t.header.appendChild(t.header_center);
		t.header_right = document.createElement("DIV");
		t.header_right.className = "header_right";
		t.header.appendChild(t.header_right);
		container.appendChild(t.header);
		// init header buttons
		var div, img;
		if (t._page_size > 0) {
			// + previous page
			t.prev_page_button = div = document.createElement("BUTTON");
			div.disabled = "disabled";
			img = document.createElement("IMG"); img.onload = function() { layout.changed(t.header); };
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
			img = document.createElement("IMG"); img.onload = function() { layout.changed(t.header); };
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
					t._page_size_field.onchange.addListener(function() {
						t._page_size = t._page_size_field.getCurrentData();
						t._loadData();
					});
					_page_size_div.appendChild(t._page_size_field.getHTMLElement());
				});
			});
		} else div.className = "results_number";
		// + refresh
		div = document.createElement("BUTTON");
		div.style.marginLeft = "2px";
		img = document.createElement("IMG"); img.onload = function() { layout.changed(t.header); };
		div.title = "Refresh";
		img.src = theme.icons_16.refresh;
		div.onclick = function() { t._loadData(); };
		div.appendChild(img);
		t.header_left.appendChild(div);
		t.refresh_button = div;
		// + select column
		div = document.createElement("BUTTON");
		img = document.createElement("IMG"); img.onload = function() { layout.changed(t.header); };
		div.title = "Select columns to display";
		img.src = getScriptPath("data_list.js")+"/table_column.png";
		div.onclick = function() { t._selectColumnsDialog(this); };
		div.appendChild(img);
		t.header_right.appendChild(div);
		// + filter
		div = document.createElement("BUTTON");
		img = document.createElement("IMG"); img.onload = function() { layout.changed(t.header); };
		div.title = "Filters";
		img.src = getScriptPath("data_list.js")+"/filter.gif";
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
		if (t._filters.length > 0) {
			t._filterNumber.innerHTML = t._filters.length;
			t.onfilterschanged.fire();
		} else
			t._filterNumber.style.visibility = "hidden";
		div.appendChild(t._filterNumber);
		t.onfilterschanged.addListener(function() {
			if (t._filters.length > 0) {
				t._filterNumber.style.visibility = "visible";
				t._filterNumber.innerHTML = t._filters.length;
			} else
				t._filterNumber.style.visibility = "hidden";
		});
		// + import
		div = document.createElement("BUTTON");
		img = document.createElement("IMG"); img.onload = function() { layout.changed(t.header); };
		div.title = "Import additional data from file";
		img.src = theme.icons_16["_import"];
		div.onclick = function(ev) { 
			if (t.grid._import_with_match) return;
			require("import_with_match.js",function() {
				new import_with_match(new import_with_match_provider_data_list(t), ev);
			});
		};
		div.appendChild(img);
		t.header_right.appendChild(div);
		// + export
		div = document.createElement("BUTTON");
		img = document.createElement("IMG"); img.onload = function() { layout.changed(t.header); };
		div.title = "Export list";
		img.src = theme.icons_16["_export"];
		div.onclick = function() { t._exportMenu(this); };
		div.appendChild(img);
		t.header_right.appendChild(div);
		// + print
		div = document.createElement("BUTTON");
		img = document.createElement("IMG"); img.onload = function() { layout.changed(t.header); };
		div.title = "Print";
		img.src = theme.icons_16["print"];
		div.onclick = function() { t.print(); };
		div.appendChild(img);
		t.header_right.appendChild(div);
		// + more button for horizontal menu
		div = document.createElement("BUTTON");
		img = document.createElement("IMG"); img.onload = function() { layout.changed(t.header_center); };
		img.src = theme.icons_16.more_menu;
		div.appendChild(img);
		t.header_center.appendChild(div);
		t.more_menu = div;
		div = null;
		img = null;
		// init grid
		t.grid_container = document.createElement("DIV");
		t.grid_container.className = "grid_container";
		container.appendChild(t.grid_container);
		require("grid.js",function(){
			t.grid = new grid(t.grid_container);
			t.grid.columns_movable = true;
			t.grid.on_column_moved.addListener(function(move) {
				// update the order of show_fields, so we can save it
				if (move.column instanceof GridColumn) {
					// final column
					var field = move.column.attached_data;
					var prev_index = t.show_fields.indexOf(field);
					t.show_fields.remove(field);
					t.show_fields.splice(move.index-(move.index > prev_index ? 1 : 0),0,field);
					t._updateFieldsCookie();
					return;
				}
				// column container
				var field = move.column.attached_data;
				var prev_index;
				var sub = [];
				for (prev_index = 0; prev_index < t.show_fields.length; ++prev_index) {
					if (t.show_fields[prev_index].field != field) continue;
					while (t.show_fields[prev_index].field == field) {
						sub.push(t.show_fields[prev_index]);
						t.show_fields.splice(prev_index,1);
					}
				}
				for (var i = 0; i < sub.length; ++i)
					t.show_fields.splice(move.index-(move.index > prev_index ? sub.length : 0)+i,0,sub[i]);
				t._updateFieldsCookie();
			});
			t._ready();
		});
		// layout
		require("horizontal_menu.js",function(){
			new horizontal_menu(t.header_center, "middle");
			layout.changed(container);
		});
	};
	/** Load the available fields for the root table */
	t._loadFields = function() {
		require("datadisplay.js",function() {
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
		var list = getCookie("data_list_fields");
		if (list != "") {
			list = list.split(",");
			for (var i = 0; i < list.length; ++i) {
				var j = list[i].indexOf('.');
				if (j<0) continue;
				var cat = list[i].substring(0,j);
				var name = list[i].substring(j+1);
				var sub_data_index = -1;
				j = name.indexOf('.');
				if (j != -1) {
					sub_data_index = parseInt(name.substring(j+1));
					name = name.substring(0,j);
				}
				for (var j = 0; j < t._available_fields.length; ++j) {
					if (t._available_fields[j].category != cat) continue;
					if (t._available_fields[j].name != name) continue;
					t.show_fields.push({field:t._available_fields[j],sub_index:sub_data_index});
				}
			}
		} 
		if (t.show_fields.length == 0)
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
			if (default_sort) {
				var i = default_sort.indexOf('.');
				if (i > 0) {
					var cat = default_sort.substr(0,i);
					var name = default_sort.substr(i+1);
					var sub_index = -1;
					i = name.indexOf('.');
					if (i > 0) {
						var s = name.substr(i+1);
						if (!isNaN(parseInt(s))) {
							sub_index = parseInt(s);
							name = name.substr(0,i);
						}
					}
					t.orderBy(cat, name, sub_index, default_sort_asc);
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
			t.getColumnIdFromField(f,true), // id
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
			col.addAction(new GridColumnAction('edit', theme.icons_10.edit,function(ev,action,col){
				if (t.isLoading()) {
					t.onNotLoading(function() {
						action.onclick(ev,action,col);
					});
					return;
				}
				var edit_col = function() {
					action.icon = col.editable ? theme.icons_10.edit : theme.icons_10.no_edit;
					action.tooltip = col.editable ? "Edit data on this column" : "Cancel modifications and stop editing this column";
					col.toggleEditable();
					layout.changed(container);
				};
				t.startLoading();
				if (col.editable) {
					service.json("data_model","unlock",{locks:col.locks},function(result){});
					for (var j = 0; j < col.locks.length; ++j)
						window.databaselock.removeLock(col.locks[j]);
					col.locks = null;
					t._cancelColumnChanges(col);
					edit_col();
					t.endLoading();
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
									// additional actions
									// TODO
									/*
									col._setAllAction = new GridColumnAction('edit',theme.icons_10.edit,function(ev,action,col){
										
									}, "Set all to a specific value");
									*/
									// change editable
									edit_col();
								}
								t.endLoading();
							}
						});
					}
				}
			}, "Edit data on this column"));
		}
		if (f.field.filter_classname) {
			var has = t.hasFilterOn(f.field.category, f.field.name);
			var update_action = function(action) {
				var has = t.hasFilterOn(f.field.category, f.field.name);
				action.icon = has ? "/static/widgets/grid/filter_active.png" : "/static/widgets/grid/filter.gif";
				action.tooltip = has ? "Edit filters (this column is currently filtered)" : "Filter";
				col._refreshTitle();
				layout.changed(container);
			};
			var a = new GridColumnAction('filter',has ? "/static/widgets/grid/filter_active.png" : "/static/widgets/grid/filter.gif",function(ev,action,col){
				var has = t.hasFilterOn(f.field.category, f.field.name);
				if (has)
					t._filtersDialog(ev.target);
				else {
					require(["context_menu.js","position.js",["typed_filter.js",f.field.filter_classname+".js"]], function() {
						var menu = new context_menu();
						var div = document.createElement("DIV");
						var filter = {category:f.field.category,name:f.field.name,data:null};
						var tf = t._createFilter(filter, div, false, true);
						menu.addItem(div,true);
						menu.showBelowElement(ev.target, false, function() {
							tf.focus();
						});
						t.addFilter(filter);
						menu.onclose = function() {
							if (!tf.isActive())
								t.removeFilter(filter);
						};
					});
				}
			}, has ? "Edit filters (this column is currently filtered)" : "Filter");
			col.addAction(a);
			t.onfilterschanged.addListener(function() {
				update_action(a);
			});
		}
		return col;
	};
	/** @no_doc */
	t._end_loading_event = null;
	/** @no_doc */
	t._loading_count = 0;
	/** Indicates we start loading data, and display 'Loading' on top of the grid */
	t.startLoading = function() {
		t._loading_count++;
		if (t._loading_hidder) return;
		t._end_loading_event = new Custom_Event();
		t._loading_hidder = new LoadingHidder(container);
		t._loading_hidder.setContent("<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Loading data...");
	};
	/** Indicates we are done loading data */
	t.endLoading = function() {
		t._loading_count--;
		if (!t._loading_hidder) return;
		if (t._loading_count > 0) return;
		t._loading_hidder.remove();
		t._loading_hidder = null;
		var ev = t._end_loading_event;
		t._end_loading_event = null;
		ev.fire();
	};
	/** Give a function to call when we are done loading data, or to call now if we are not currently loading data
	 * @param {Function} listener the function to be called
	 */
	t.onNotLoading = function(listener) {
		if (!t._loading_hidder) { listener(); return; }
		t._end_loading_event.addListener(listener);
	};
	/** Indicates if we are currently loading data
	 * @returns {Boolean} true if we are currently loading data
	 */
	t.isLoading = function() {
		return (typeof t._loading_hidder != 'undefined') && t._loading_hidder != null;
	};
	/** @no_doc */
	t._already_reloading = false;
	/** (Re)load the data from the server
	 * @param {Function} onready if given, the function is called when data is ready  
	 */
	t._loadData = function(onready) {
		t._data_loaded = true;
		if (t.isLoading()) {
			if (!t._already_reloading) {
				t._already_reloading = true;
				t.onNotLoading(function() {
					t._loadData(onready);
					t._already_reloading = false;
				});
			}
			return;
		}
		t.startLoading();
		var fields = [];
		var sent_fields = [];
		for (var i = 0; i < t.show_fields.length; ++i) {
			var found = false;
			for (var j = 0; j < fields.length; ++j)
				if (fields[j].path == t.show_fields[i].field.path.path && fields[j].name == t.show_fields[i].field.name) { found = true; break; }
			if (!found) {
				fields.push({path:t.show_fields[i].field.path.path,name:t.show_fields[i].field.name});
				sent_fields.push(t.show_fields[i].field);
			}
		}
		for (var i = 0; i < t.always_fields.length; ++i) {
			var f = t.always_fields[i];
			var found = false;
			for (var j = 0; j < t.show_fields.length; ++j)
				if (t.show_fields[j].field == f) { found = true; break; };
			if (!found) {
				fields.push({path:f.path.path,name:f.name});
				sent_fields.push(f);
			}
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
				t.endLoading();
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
			layout.changed(t.header);
			//window.console.log("request time: "+result.time+"s.");
			if (result.data.length == 0) {
				t.grid.setData([]);
				t.grid.addTitleRow("No result found", {textAlign:"center",padding:"2px 5px",fontStyle:"italic"});
				t.data = [];
			} else {
				var data = [];
				var cols = [];
				for (var i = 0; i < t.show_fields.length; ++i)
					for (var j = 0; j < t.grid.columns.length; ++j)
						if (t.grid.columns[j].attached_data && t.grid.columns[j].attached_data == t.show_fields[i]) {
							cols.push(t.grid.columns[j]);
							break;
						}
				for (var i = 0; i < result.data.length; ++i) {
					var row = {row_id:i,row_data:[]};
					var classname = t._getRowClass(sent_fields, result.data[i].values);
					if (classname) row.classname = classname;
					for (var j = 0; j < t.show_fields.length; ++j)
						row.row_data.push({col_id:cols[j].id,data_id:null,css:"disabled"});
					for (var j = 0; j < fields.length; ++j) {
						var value = result.data[i].values[j];
						for (var k = 0; k < t.show_fields.length; ++k) {
							if (t.show_fields[k].field.path.path == fields[j].path && t.show_fields[k].field.name == fields[j].name) {
								row.row_data[k].col_id = cols[k].id;
								if (value.k == null && (cols[j].editable || value.v == null)) {
									row.row_data[k].data_id = null;
									row.row_data[k].css = "disabled";
								} else {
									row.row_data[k].data_id = value.k;
									row.row_data[k].data = value.v;
									if (cols[k].attached_data) row.row_data[k].data_display = cols[k].attached_data.field;
									row.row_data[k].css = null;
								}
							}
						}
					}
					data.push(row);
					if (t._col_actions)
						row.row_data.push({col_id:'actions',data_id:null,data:""});
				}
				t.grid.setData(data);
				t.data = result.data;
				if (t._col_actions)
					t._populateActions();
				// register events
				for (var i = 0; i < t.grid.table.childNodes.length; ++i) {
					var row = t.grid.table.childNodes[i];
					if (t._rowOnclick)
						t._makeClickable(row);
				}
			}
			t.ondataloaded.fire(t);
			t.endLoading();
			if (onready) onready();
			layout.resume();
		});
		// empty the grid while sending request, so the javascript work begins
		layout.pause();
		t.grid.setData([]);
	};
	/** Internal function to ask the actions providers to create them for every row */
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
	/** Internal function called when a column is added or removed, to save it in a cookie so next time the user comes back to this screen, the same columns will be displayed */
	t._updateFieldsCookie = function() {
		var s = "";
		for (var i = 0; i < t.show_fields.length; ++i) {
			if (s != "") s += ",";
			s += t.show_fields[i].field.category+"."+t.show_fields[i].field.name;
			if (t.show_fields[i].sub_index != -1)
				s += "."+t.show_fields[i].sub_index;
		}
		var u = new URL(location.href);
		setCookie("data_list_fields",s,15*24*60,u.path);
	};
	/** Internal function to diplsay the list of available columns
	 * @param {Element} button the button clicked by the user
	 * @param {Function} createFieldContent called to create the HTML for the given field
	 * @param {Function} createSubFieldContent called to create the HTML for the given sub-data
	 * @param {Boolean} keep_on_click if true, the context menu won't be closed when the user click on it
	 */
	t._columnDialogLayout = function(button, createFieldContent, createSubFieldContent, keep_on_click) {
		require("context_menu.js");
		window.top.theme.css("data_list.css"); // the context menu goes to window.top, we need the stylesheet
		var categories = [];
		for (var i = 0; i < t._available_fields.length; ++i) {
			var cat = null;
			for (var j = 0; j < categories.length; ++j) if (categories[j].name == t._available_fields[i].category) { cat = categories[j]; break; }
			if (!cat) {
				cat = {name:t._available_fields[i].category,fields:[],nb:0};
				categories.push(cat);
			}
			cat.fields.push(t._available_fields[i]);
			cat.nb++;
			if (t._available_fields[i].sub_data) cat.nb += t._available_fields[i].sub_data.names.length;
		}
		// organize big categories to be in several columns
		for (var i = 0; i < categories.length; ++i) {
			if (categories[i].nb > 20) {
				// more than 20 fields: make it on several columns
				categories[i].cols = [];
				var max = 0;
				for (var j = 0; j < categories[i].fields.length; ++j) {
					var f = categories[i].fields[j];
					var nb = 1;
					if (f.sub_data) nb += f.sub_data.names.length;
					// search a column with enough space
					if (nb >= 20)
						categories[i].cols.push({nb:nb,fields:[f]});
					else {
						var found = false;
						for (var k = 0; k < categories[i].cols.length; ++k)
							if (categories[i].cols[k].nb + nb <= 20) {
								found = true;
								categories[i].cols[k].nb += nb;
								categories[i].cols[k].fields.push(f);
								break;
							}
						if (!found)
							categories[i].cols.push({nb:nb,fields:[f]});
					}
				}
				// try to balance as much as possible
				for (var j = categories[i].cols.length-1; j > 0; --j) {
					var c1 = categories[i].cols[j];
					var c2 = categories[i].cols[j-1];
					while (c1.nb <= c2.nb-2) {
						var found = false;
						for (var k = c2.fields.length-1; k >= 0; --k) {
							var f = c2.fields[k];
							var nb = 1;
							if (f.sub_data) nb += f.sub_data.names.length;
							if (c2.nb-nb >= c1.nb+nb) {
								found = true;
								c2.fields.remove(f);
								c1.fields.splice(0,0,f);
								c2.nb -= nb;
								c1.nb += nb;
							}
						}
						if (!found) break;
					}
				}
				// get the bigger column size
				var max = 0;
				for (var j = 0; j < categories[i].cols.length; ++j)
					if (categories[i].cols[j].nb > max) max = categories[i].cols[j].nb;
				categories[i].nb = max;
			}
		}
		// organize columns to minimize space
		var cols = [];
		if (categories.length <= 5) {
			// up to 5, we do not optimize
			for (var i = 0; i < categories.length; ++i)
				cols.push([categories[i]]);
		} else {
			// more than 5: try to optimize
			var max_size = 0;
			for (var i = 0; i < categories.length; ++i)
				if (categories[i].nb > max_size) max_size = categories[i].nb;
			if (max_size < 15) max_size = 15;
			for (var i = 0; i < categories.length; ++i) {
				// check if we have enough space in an existing column
				var found = false;
				for (var j = 0; j < cols.length; ++j) {
					var col_size = 0;
					for (var k = 0; k < cols[j].length; ++k) col_size += cols[j][k].nb + 1;
					if (col_size + categories[i].nb + 1 <= max_size) {
						// found one
						cols[j].push(categories[i]);
						found = true;
						break;
					}
				}
				if (!found) cols.push([categories[i]]);
			}
		}
		var dialog = document.createElement("DIV");
		var table = document.createElement("TABLE"); dialog.appendChild(table);
		table.className = "data_list_select_fields_menu";
		var tr = document.createElement("TR"); table.appendChild(tr);
		for (var i = 0; i < cols.length; ++i) {
			var td = document.createElement("TD"); tr.appendChild(td);
			for (var cat_i = 0; cat_i < cols[i].length; ++cat_i) {
				var cat = cols[i][cat_i];
				var div = document.createElement("DIV");
				div.className = "header";
				div.appendChild(document.createTextNode(cat.name));
				td.appendChild(div);
				if (cat.cols) {
					var sub_table = document.createElement("TABLE");
					sub_table.className = "sub_table";
					td.appendChild(sub_table);
					var sub_tr = document.createElement("TR");
					sub_table.appendChild(sub_tr);
					for (var col_i = 0; col_i < cat.cols.length; ++col_i) {
						var sub_td = document.createElement("TD");
						sub_tr.appendChild(sub_td);
						for (var j = 0; j < cat.cols[col_i].fields.length; ++j) {
							var f = cat.cols[col_i].fields[j];
							var div = document.createElement("DIV");
							div.className = "content";
							sub_td.appendChild(div);
							var parent_info = createFieldContent(f, div);
							if (f.sub_data) {
								var sub_div = document.createElement("DIV");
								sub_div.className = "sub_data";
								for (k = 0; k < f.sub_data.names.length; ++k)
									createSubFieldContent(f, k, sub_div, parent_info);
								div.appendChild(sub_div);
							}
						}
					}
				} else {
					for (var j = 0; j < cat.fields.length; ++j) {
						var f = cat.fields[j];
						var div = document.createElement("DIV");
						div.className = "content";
						td.appendChild(div);
						var parent_info = createFieldContent(f, div);
						if (f.sub_data) {
							var sub_div = document.createElement("DIV");
							sub_div.className = "sub_data";
							for (k = 0; k < f.sub_data.names.length; ++k)
								createSubFieldContent(f, k, sub_div, parent_info);
							div.appendChild(sub_div);
						}
					}
				}
			}
		}
		require("context_menu.js",function(){
			var menu = new context_menu();
			menu.removeOnClose = true;
			menu.addItem(dialog, keep_on_click);
			menu.showBelowElement(button);
		});
	};
	/** Show the menu to select the columns/fields to display
	 * @param {Element} button the menu will be display below this element
	 */
	t._selectColumnsDialog = function(button) {
		t._columnDialogLayout(button, function(f,div){
			var cb = document.createElement("INPUT"); cb.type = 'checkbox';
			cb.data = f;
			var found = -1;
			for (var k = 0; k < t.show_fields.length; ++k)
				if (t.show_fields[k].field.path.path == f.path.path &&
					t.show_fields[k].field.name == f.name) { found = k; break; }
			if (found != -1 && t.show_fields[found].sub_index == -1)
				cb.checked = 'checked';
			if (found != -1 && t.show_fields[found].forced)
				cb.disabled = 'disabled';
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
				t._updateFieldsCookie();
			};
			div.appendChild(cb);
			div.appendChild(document.createTextNode(f.name));
			return [cb,found];
		},function(f, sub_data_index, sub_div, parent_info) {
			var cb = parent_info[0];
			var found = parent_info[1];
			var sub_cb = document.createElement("INPUT");
			sub_cb.type = "checkbox";
			if (found != -1 && t.show_fields[found].sub_index == -1)
				sub_cb.disabled = "disabled";
			else {
				var sub_found = false;
				for (var l = 0; l < t.show_fields.length; ++l)
					if (t.show_fields[l].field.path.path == f.path.path &&
						t.show_fields[l].field.name == f.name &&
						t.show_fields[l].sub_index == sub_data_index) { sub_found = true; break; }
				if (sub_found)
					sub_cb.checked = "checked";
			}
			sub_div.appendChild(sub_cb);
			sub_div.appendChild(document.createTextNode(f.sub_data.names[sub_data_index]));
			sub_div.appendChild(document.createElement("BR"));
			sub_cb._parent_cb = cb;
			sub_cb._index = sub_data_index;
			if (!cb._sub_cb) cb._sub_cb = [];
			cb._sub_cb.push(sub_cb);
			sub_cb.onchange = function() {
				if (this.checked) {
					t.showSubField(this._parent_cb.data, this._index);
				} else {
					t.hideSubField(this._parent_cb.data, this._index);
				}
				t._updateFieldsCookie();
			};
		}, true);
	};
	/** Show the menu to choose on which column to add a filter
	 * @param {Element} button the button clicked by the user
	 * @param {Function} onselect called when the user selected a field
	 */
	t._contextMenuAddFilter = function(button, onselect) {
		t._columnDialogLayout(button, function(f,div){
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
			div.appendChild(document.createTextNode(f.name));
			addClassName(div, "context_menu_item");
			div.field = f;
			if (f.filter_classname && (window[f.filter_classname].prototype.can_multiple || !filter_exists)) {
				div.onclick = function() {
					var filter = {
						category: this.field.category,
						name: this.field.name,
						force: false,
						data: null
					};
					onselect(this.field, filter);
				};
			} else {
				addClassName(div, "disabled");
			}
		},function(f, sub_data_index, sub_div, parent_info) {
			// check if a filter already exists on that field
			var filter_exists = false;
			for (var l = 0; l < t._filters.length; ++l) {
				if (t._filters[l].category == f.category && t._filters[l].name == f.name && t._filters[l].sub_index == sub_data_index) { filter_exists = true; break; }
				var o = t._filters[l].or;
				while (o) {
					if (o.category == f.category && o.name == f.name && o.sub_index == k) { filter_exists = true; break; }
					o = o.or;
				}
				if (filter_exists) break;
			}
			// create the item
			addClassName(sub_div, "context_menu_item");
			sub_div.appendChild(document.createTextNode(f.sub_data.names[sub_data_index]));
			sub_div.appendChild(document.createElement("BR"));
			sub_div.sub_index = sub_data_index;
			sub_div.field = f;
			if (f.sub_data.filters && f.sub_data.filters[sub_data_index].classname && (window[f.sub_data.filters[sub_data_index].classname].prototype.can_multiple || !filter_exists)) {
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
				addClassName(sub_div, "disabled");
			}
		}, false);
	};
	/** Create the display for a filter, inside the given table
	 * @param {Object} filter the filter
	 * @param {Element} container where to display the filter
	 * @param {Boolean} is_or indicates if this is in a Or condition
	 * @param {Boolean} simple if true, no remove button and no name is displayed
	 */
	t._createFilter = function(filter, container, is_or, simple) {
		var div = document.createElement("DIV");
		container.appendChild(div);
		div.className = "data_list_filter";
		div.style.verticalAlign = "bottom";
		div.style.whiteSpace = "nowrap";
		
		// find the corresponding field
		var field = null;
		for (var i = 0; i < t._available_fields.length; ++i)
			if (t._available_fields[i].category == filter.category && t._available_fields[i].name == filter.name) {
				field = t._available_fields[i];
				break;
			}
		
		// remove button
		if (!filter.force && !simple) {
			var remove = document.createElement("BUTTON");
			remove.className = "flat";
			remove.style.verticalAlign = "bottom";
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
				layout.changed(container);
			};
		}
		
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
		if (!simple) {
			var div_name = document.createElement("DIV");
			div_name.style.display = "inline-block";
			div_name.style.verticalAlign = "middle";
			div_name.style.marginRight = "4px";
			div_name.appendChild(document.createTextNode(field.name+(typeof filter.sub_index != 'undefined' && filter.sub_index >= 0 ? "/"+field.sub_data.names[filter.sub_index] : "")+" "));
			div.appendChild(div_name);
		}
		
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
		f.getHTMLElement().style.verticalAlign = "middle";
		f.onchange.addListener(function (f) {
			filter.data = f.getData();
			t._loadData();
		});
		
		if (!is_or && !simple) {
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
			if (!filter.force) {
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
						layout.changed(container);
						t._loadData();
					});
				};
			}
		}
		return f;
	},
	/** Display the menu to edit/add/remove filters
	 * @param {Element} button the menu will be displayed below this element
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
						var tf = t._createFilter(filter, filters_content);
						layout.changed(filters_content);
						if (tf.isActive())
							t._loadData();
					});
				});
				popup.show();
				popup.onclose = function() {
					// check inactive filters
					var fake = document.createElement("DIV");
					for (var i = 0; i < t._filters.length; ++i) {
						var tf = t._createFilter(t._filters[i], fake);
						var f = t._filters[i];
						var change = false;
						while (f.or) {
							var or = t._createFilter(f.or, fake);
							if (!or.isActive()) {
								// remove it
								change = true;
								f.or = f.or.or;
							} else
								f = f.or;
						}
						if (!tf.isActive()) {
							if (t._filters[i].or) {
								t._filters[i] = t._filters[i].or;
								change = true;
							} else {
								t.removeFilter(t._filters[i]);
								change = false;
							}
						}
						if (change)
							t.onfilterschanged.fire();
					}
				};
			});
		});
	};
	/** Display the menu to export the list
	 * @param {Element} button the menu will be displayed below this element
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
	/** @no_doc */
	t._download_frame = null;
	/** Launch the export in the given format
	 * @param {String} format format to export
	 */
	t._exportList = function(format) {
		var format_name;
		switch (format) {
		case 'excel2007':
		case 'excel5': format_name = "Excel"; break;
		case 'pdf': format_name = "PDF"; break;
		case 'csv': format_name = "CSV"; break;
		}
		var locker = lock_screen();
		set_lock_screen_content_progress(locker, 100, "Generating your "+format_name+" file...", null, function(span, pb, sub) {
			service.json("application","create_temp_data",{value:'0'},function(res) {
				var temp_data_id = res.id;
				var fields = [];
				for (var i = 0; i < t.show_fields.length; ++i)
					fields.push({path:t.show_fields[i].field.path.path,name:t.show_fields[i].field.name,sub_index:t.show_fields[i].sub_index});
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
				form.appendChild(input = document.createElement("INPUT"));
				input.type = 'hidden';
				input.name = 'progress_id';
				input.value = temp_data_id;
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
				var refresh = function() {
					service.json("application","get_temp_data",{id:temp_data_id},function(res) {
						if (res.value == 'done' || res.value === null || isNaN(parseInt(res.value))) {
							unlock_screen(locker);
							return;
						}
						pb.setPosition(parseInt(res.value));
						refresh();
					});
				};
				refresh();
			});
		});
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
					layout.changed(t.header_left);
				}
			} else {
				// display save button
				if (t.save_button == null) {
					t.save_button = document.createElement("BUTTON");
					var img = document.createElement("IMG"); img.onload = function() { layout.changed(t.header); };
					t.save_button.title = "Save Changes";
					img.src = theme.icons_16.save;
					t.save_button.onclick = function() { t._save(); };
					t.save_button.appendChild(img);
					if (t.refresh_button.nextSibling)
						t.header_left.insertBefore(t.save_button, t.refresh_button.nextSibling);
					else
						t.header_left.appendChild(t.save_button);
					window.pnapplication.dataUnsaved(container.id);
					layout.changed(t.header_left);
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
			layout.changed(t.header_left);
		}
	};
	/** Save all edited data */
	t._save = function() {
		if (t.isLoading()) {
			t.onNotLoading(function() {
				t._save();
			});
			return;
		}
		t.startLoading();
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
					layout.changed(t.header_left);
					window.pnapplication.dataSaved(container.id);
				}
			}
			t.endLoading();
		});
	};
	/** Make the row clickable
	 * @param {Element} row the TR element corresponding to the row in the grid
	 */
	t._makeClickable = function(row) {
		row.onmouseover = function() { addClassName(this, "selected"); };
		row.onmouseout = function() { if (t.grid.isSelected(row.row_id)) addClassName(this, "selected"); else removeClassName(this, "selected"); };
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
