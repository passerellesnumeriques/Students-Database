/* #depends[/static/application/application.js] */
/**
 * Functionalities to help when we display data from the data model:
 * Typically, when the same data is displayed several time on the same screen, and is editable, we need to automatically change everywhere as soon as the user makes a modification.
 * Those functionalities help to implement this, by registering listeners/modification events, so that when something changed, actions can be done automatically
 */
datamodel = {
	/** List of components registered as containing a DataDisplay */
	_data_widgets: [],
	/** Registers a widget/component as containing a DataDisplay
	 * @param {window} win the window where the widget is (given, so when the window is closed, we can unregister it automatically)
	 * @param {DataDisplay} data_display the data
	 * @param {Object} data_key key of the data
	 * @param {Function} data_getter function allowing to get the current value of the data on the widget
	 * @param {Function} data_setter function allowing to set the value of the data on the widget
	 * @param {Function} register_listener function that needs to be called to register a listener to be called when the widget is changing the value of the data
	 */
	registerDataWidget: function(win, data_display, data_key, data_getter, data_setter, register_listener) {
		this._data_widgets.push({win:win,data_display:data_display,data_key:data_key,data_getter:data_getter,data_setter:data_setter});
		register_listener(function(){
			window.top.datamodel.dataChanged(data_display, data_key, data_getter());
		});
		if (data_display.cell)
			this.registerCellWidget(win, data_display.cell.table, data_display.cell.column, data_key, data_getter, data_setter, register_listener);
	},
	/** List of components registered as containing a cell */
	_cell_widgets: [],
	/** Registers a widget/component as containing a cell
	 * @param {window} win the window where the widget is (given, so when the window is closed, we can unregister it automatically)
	 * @param {String} table table
	 * @param {String} column column name in the table
	 * @param {Object} row_key key of the row in the table
	 * @param {Function} data_getter function allowing to get the current value of the data on the widget
	 * @param {Function} data_setter function allowing to set the value of the data on the widget
	 * @param {Function} register_listener function that needs to be called to register a listener to be called when the widget is changing the value of the data
	 */
	registerCellWidget: function(win, table, column, row_key, data_getter, data_setter, register_listener) {
		this._cell_widgets.push({win:win,table:table,column:column,row_key:row_key,data_getter:data_getter,data_setter:data_setter});
		register_listener(function(){
			window.top.datamodel.cellChanged(table, column, row_key, data_getter());
		});
	},
	/** Register a text node displaying the data of a cell
	 * @param {window} win the window where the widget is (given, so when the window is closed, we can unregister it automatically)
	 * @param {String} table table
	 * @param {String} column column name in the table
	 * @param {Object} row_key key of the row in the table
	 * @param {DOMNode} text_node the text node to automatically update when the data changes
	 */
	registerCellText: function(win, table, column, row_key, text_node) {
		window.top.datamodel.addCellChangeListener(win, table, column, row_key, function(value) {
			text_node.nodeValue = value;
			if (text_node.parentNode) layout.invalidate(text_node.parentNode);
		});
	},
	inputCell: function(input, table, column, row_key) {
		var original = input.value;
		var win = getWindowFromDocument(input.ownerDocument);
		input.cellSaved = function() {
			original = input.value;
		};
		win.pnapplication.onclose.add_listener(function() {
			if (input.value != original) {
				input.value = original;
				input.onchange();
			}
		});
		datamodel.registerCellWidget(win, table, column, row_key, function() {
			return input.value;
		}, function(value) {
			input.value = value;
			input.onchange();
		}, function(listener) {
			var prev_change = input.onchange;
			input.onchange = function(ev) {
				listener();
				if (prev_change) prev_change(ev);
			};
			var prev_keyup = input.onkeyup;
			input.onkeyup = function(ev) {
				listener();
				if (prev_keyup) prev_keyup(ev);
			};
		});
	},
	dateSelectCell: function(date_select, table, column, row_key) {
		var original = date_select.getDate();
		var win = getWindowFromDocument(date_select.select_day.ownerDocument);
		date_select.cellSaved = function() {
			original = date_select.getDate();
		};
		win.pnapplication.onclose.add_listener(function() {
			var d = date_select.getDate();
			if ((d == null && original != null) || original == null || d.getTime() != original.getTime()) {
				date_select.selectDate(original);
			}
		});
		var prev = date_select.onchange;
		datamodel.registerCellWidget(win, table, column, row_key, function() {
			return dateToSQL(date_select.getDate());
		}, function(value) {
			date_select.selectDate(parseSQLDate(value));
		}, function(listener) {
			date_select.onchange = function() {
				listener();
				if (prev) prev();
			};
		});
	},
	
	/** List of listeners to be called when a DataDisplay changes */
	_data_change_listeners: [],
	/** Register a listener to be called when the given DataDisplay changes
	 * @param {window} win the window on which the listener is
	 * @param {DataDisplay} data_display the data to watch
	 * @param {Object} data_key the key of the data to watch
	 * @param {Function} listener the function to call when a change is detected
	 */
	addDataChangeListener: function(win, data_display, data_key, listener) {
		this._data_change_listeners.push({win:win,data_display:data_display,data_key:data_key,listener:listener});
	},
	/** List of listeners to be called when a cell changes */
	_cell_change_listeners: [],
	/** Register a listener to be called when the given cell changes
	 * @param {window} win the window on which the listener is
	 * @param {String} table table
	 * @param {String} column column name in the table
	 * @param {Object} row_key key of the row in the table
	 * @param {Function} listener the function to call when a change is detected
	 */
	addCellChangeListener: function(win, table, column, row_key, listener) {
		this._cell_change_listeners.push({win:win,table:table,column:column,row_key:row_key,listener:listener});
	},
	
	/** Signal the change of a DataDisplay
	 * @param {DataDisplay} data_display the data which changed
	 * @param {Object} data_key the key of the data
	 * @param {Object} value the new value
	 */
	dataChanged: function(data_display, data_key, value) {
		for (var i = 0; i < this._data_change_listeners.length; ++i) {
			var l = this._data_change_listeners[i];
			if (l.data_display.table == data_display.table && l.data_display.name == data_display.name && l.data_key == data_key)
				l.listener(value);
		}
		for (var i = 0; i < this._data_widgets.length; ++i) {
			var w = this._data_widgets[i];
			if (w.data_display.category == data_display.category && w.data_display.name == data_display.name && w.data_key == data_key) {
				if (w.data_getter() != value) w.data_setter(value);
			}
		}
	},
	/** Signal the change of a cell
	 * @param {String} table table
	 * @param {String} column column name in the table
	 * @param {Object} row_key key of the row in the table
	 * @param {Object} value the new value
	 */
	cellChanged: function(table, column, row_key, value) {
		for (var i = 0; i < this._cell_change_listeners.length; ++i) {
			var l = this._cell_change_listeners[i];
			if (l.table == table && l.column == column && l.row_key == row_key)
				l.listener(value);
		}
		for (var i = 0; i < this._cell_widgets.length; ++i) {
			var w = this._cell_widgets[i];
			if (w.table == table && w.column == column && w.row_key == row_key) {
				if (w.data_getter() != value) w.data_setter(value);
			}
		}
	},
	
	/**
	 * retrieve the vale of a cell: if it is somewhere on the screen, we take it there, else we call the server to get it
	 * @param {String} table table
	 * @param {String} column column name in the table
	 * @param {Object} row_key key of the row in the table
	 * @param {Function} handler the function to call as soon as we found the value
	 */
	getCellValue: function(table, column, row_key, handler) {
		for (var i = 0; i < this._cell_widgets.length; ++i) {
			var w = this._cell_widgets[i];
			if (w.table == table && w.column == column && w.row_key == row_key) {
				handler(w.data_getter());
				return;
			}
		}
		// we don't have, we need to request to the server
		service.json("data_model","get_cell",{table:table,column:column,row_key:row_key},function(res) {
			handler(res.value);
		});
	},
	
	/** Called when a window is closed
	 * @param {window} win the window which has been closed
	 */
	_windowClosed: function(win) {
		for (var i = 0; i < this._data_widgets.length; ++i) {
			if (this._data_widgets[i].win == win) {
				this._data_widgets.splice(i,1);
				i--;
			}
		}
		for (var i = 0; i < this._cell_widgets.length; ++i) {
			if (this._cell_widgets[i].win == win) {
				this._cell_widgets.splice(i,1);
				i--;
			}
		}
		for (var i = 0; i < this._data_change_listeners.length; ++i) {
			if (this._data_change_listeners[i].win == win) {
				this._data_change_listeners.splice(i,1);
				i--;
			}
		}
		for (var i = 0; i < this._cell_change_listeners.length; ++i) {
			if (this._cell_change_listeners[i].win == win) {
				this._cell_change_listeners.splice(i,1);
				i--;
			}
		}
	},
	
	model: null,
	_model_listeners: [],
	getModel: function(callback) {
		if (window.top.datamodel.model != null)
			callback(window.top.datamodel.model);
		else {
			window.top.datamodel._model_listeners.push(callback);
			if (window.top.datamodel._model_listeners.length == 1)
				service.json("data_model", "get_model", {}, function(model) {
					window.top.datamodel.model = model;
					objectMerge(window.top.datamodel.model, window.top.datamodel_prototype);
					for (var i = 0; i < model.tables.length; ++i) {
						objectMerge(model.tables[i], window.top.datamodel_table_prototype);
						for (var j = 0; j < model.tables[i].columns.length; ++j)
							objectMerge(model.tables[i].columns[j], window.top.datamodel_column_prototype);
					}
					for (var i = 0; i < window.top.datamodel._model_listeners.length; ++i)
						window.top.datamodel._model_listeners[i](window.top.datamodel.model);
					window.top.datamodel._model_listeners = null;
				});
		}
	},
	
	create_cell: function(table, sub_model, column, row_key, value, editable, container, onchange) {
		if (!editable) {
			var node = document.createTextNode(value);
			window.top.datamodel.addCellChangeListener(getWindowFromElement(container), table+(sub_model ? "_"+sub_model : ""), column, row_key, function(value){
				node.nodeValue = value;
				if (onchange) onchange(value);
			});
			container.appendChild(node);
			return;
		}
		require("typed_field.js");
		require("editable_cell.js");
		this.getModel(function(model) {
			var t = model.getTable(table);
			var c = t.getColumn(column);
			require([["typed_field.js",c.typed_field_classname+".js"],"editable_cell.js"], function() {
				new editable_cell(container, table+(sub_model ? "_"+sub_model : ""), column, row_key, c.typed_field_classname, c.typed_field_args, value,null,onchange);
			});
		});
	},
	
	confirm_remove: function(table,row_key,onremoved) {
		var popup_ready = false;
		var content_html = null;
		var ready = function() {
			if (!popup_ready) return;
			if (content_html == null) return;
			var content = document.createElement("DIV");
			content.style.margin = "5px";
			content.innerHTML = content_html;
			var popup = new popup_window("Confirmation", theme.icons_16.question, content);
			popup.addOkCancelButtons(function() {
				popup.freeze("<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Removing...");
				service.json("data_model", "remove_row", {table:table,row_key:row_key}, function(res) {
					if (res) {
						if (onremoved) onremoved();
						popup.close();
						return;
					}
					popup.unfreeze();
				});
			});
			popup.show();
		};
		service.customOutput("data_model","get_remove_confirmation_content",{table:table,row_key:row_key},function(html) {
			content_html = html;
			ready();
		});
		require("popup_window.js",function() { popup_ready = true; ready(); });
	}
};
window.top.pnapplication.onwindowclosed.add_listener(function(w) { datamodel._windowClosed(w); });
if (!window.top.datamodel_prototype) {
window.top.datamodel_prototype = {
	getTable: function(name) {
		for (var i = 0; i < this.tables.length; ++i)
			if (this.tables[i].name == name)
				return this.tables[i];
		return null;
	}
};
window.top.datamodel_table_prototype = {
	getColumn: function(name) {
		for (var i = 0; i < this.columns.length; ++i)
			if (this.columns[i].name == name)
				return this.columns[i];
		return null;
	}
};
window.top.datamodel_column_prototype = {
};
}
