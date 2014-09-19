/* #depends[/static/application/application.js] */
/**
 * Functionalities to help when we display data from the data model:
 * Typically, when the same data is displayed several time on the same screen, and is editable, we need to automatically change everywhere as soon as the user makes a modification.
 * Those functionalities help to implement this, by registering listeners/modification events, so that when something changed, actions can be done automatically
 */
window.datamodel = {
	/** List of components registered as containing a DataDisplay */
	_data_widgets: [],
	/** Registers a widget/component as containing a DataDisplay
	 * @param {window} win the window where the widget is (given, so when the window is closed, we can unregister it automatically)
	 * @param {DataDisplay} data_display the data
	 * @param {Object} data_key key of the data
	 * @param {Element} element the element representing the widget. When this element is removed, it is automatically unregistered.
	 * @param {Function} data_getter function allowing to get the current value of the data on the widget
	 * @param {Function} data_setter function allowing to set the value of the data on the widget
	 * @param {Function} register_listener function that needs to be called to register a listener to be called when the widget is changing the value of the data
	 * @param {Function} unregister_listener function that needs to be called to unregister a listener
	 */
	registerDataWidget: function(win, data_display, data_key, element, data_getter, data_setter, register_listener, unregister_listener) {
		var listener = function(){
			window.top.datamodel.dataChanged(data_display, data_key, data_getter());
		};
		window.top.datamodel._data_widgets.push({win:win,data_display:data_display,data_key:data_key,data_getter:data_getter,data_setter:data_setter,element:element,unregister_listener:unregister_listener,listener:listener});
		register_listener(listener);
		element.ondomremoved(function(element) {
			window.top.datamodel.unregisterDataWidget(element);
			listener = null;
		});
		if (data_display.cell)
			this.registerCellWidget(win, data_display.cell.table, data_display.cell.column, data_key, element, data_getter, data_setter, register_listener, unregister_listener);
	},
	unregisterDataWidget: function(element) {
		for (var i = 0; i < this._data_widgets.length; ++i)
			if (this._data_widgets[i].element == element) {
				this._data_widgets[i].unregister_listener(this._data_widgets[i].listener);
				this._data_widgets.splice(i,1);
				i--;
			}
	},
	/** List of components registered as containing a cell */
	_cell_widgets: [],
	/** Registers a widget/component as containing a cell
	 * @param {window} win the window where the widget is (given, so when the window is closed, we can unregister it automatically)
	 * @param {String} table table
	 * @param {String} column column name in the table
	 * @param {Object} row_key key of the row in the table
	 * @param {Element} element the element representing the widget. When this element is removed, it is automatically unregistered.
	 * @param {Function} data_getter function allowing to get the current value of the data on the widget
	 * @param {Function} data_setter function allowing to set the value of the data on the widget
	 * @param {Function} register_listener function that needs to be called to register a listener to be called when the widget is changing the value of the data
	 * @param {Function} unregister_listener function that needs to be called to unregister a listener
	 */
	registerCellWidget: function(win, table, column, row_key, element, data_getter, data_setter, register_listener, unregister_listener) {
		var listener = function(){
			window.top.datamodel.cellChanged(table, column, row_key, data_getter());
		};
		this._cell_widgets.push({win:win,table:table,column:column,row_key:row_key,element:element,data_getter:data_getter,data_setter:data_setter,unregister_listener:unregister_listener,listener:listener});
		register_listener(listener);
		element.ondomremoved(function(element) {
			window.top.datamodel.unregisterCellWidget(element);
		});
	},
	unregisterCellWidget: function(element) {
		for (var i = 0; i < this._cell_widgets.length; ++i)
			if (this._cell_widgets[i].element == element) {
				this._cell_widgets[i].unregister_listener(this._cell_widgets[i].listener);
				this._cell_widgets.splice(i,1);
				i--;
			}
	},
	/** Register a text node displaying the data of a cell
	 * @param {window} win the window where the widget is (given, so when the window is closed, we can unregister it automatically)
	 * @param {String} table table
	 * @param {String} column column name in the table
	 * @param {Object} row_key key of the row in the table
	 * @param {Element} text_node the text node to automatically update when the data changes
	 */
	registerCellText: function(win, table, column, row_key, text_node) {
		var n=text_node;
		var listener = function(value) {
			n.nodeValue = value;
			if (n.parentNode) layout.changed(n.parentNode);
		};
		window.top.datamodel.addCellChangeListener(win, table, column, row_key, listener);
		n.parentNode.ondomremoved(function(element) {
			window.top.datamodel.removeCellChangeListener(listener);
		});
	},
	registerCellSpan: function(win, table, column, row_key, span) {
		var s=span;
		var listener = function(value) {
			s.removeAllChildren();
			s.appendChild(document.createTextNode(value));
			if (s.parentNode) layout.changed(s.parentNode);
		};
		window.top.datamodel.addCellChangeListener(win, table, column, row_key, listener);
		s.ondomremoved(function(element) {
			window.top.datamodel.removeCellChangeListener(listener);
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
		datamodel.registerCellWidget(win, table, column, row_key, input, function() {
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
		},function(listener) {
			// no need as the input is removed
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
		datamodel.registerCellWidget(win, table, column, row_key, date_select.select_day, function() {
			return dateToSQL(date_select.getDate());
		}, function(value) {
			date_select.selectDate(parseSQLDate(value));
		}, function(listener) {
			date_select.onchange = function() {
				listener();
				if (prev) prev();
			};
		}, function(listener) {
			// no need as it is removed from the page
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
	removeCellChangeListener: function(listener) {
		for (var i = 0; i < this._cell_change_listeners.length; ++i)
			if (this._cell_change_listeners[i].listener == listener) {
				this._cell_change_listeners.splice(i,1);
				i--;
			}
	},
	
	/** Signal the change of a DataDisplay
	 * @param {DataDisplay} data_display the data which changed
	 * @param {Object} data_key the key of the data
	 * @param {Object} value the new value
	 */
	dataChanged: function(data_display, data_key, value) {
		for (var i = 0; i < this._data_change_listeners.length; ++i) {
			var l = this._data_change_listeners[i];
			if (l.win.closing) {
				this._data_change_listeners.splice(i,1);
				i--;
				continue;
			}
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
			if (l.win.closing) {
				this._cell_change_listeners.splice(i,1);
				i--;
				continue;
			}
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
		if (row_key > 0)
			service.json("data_model","get_cell",{table:table,column:column,row_key:row_key},function(res) {
				handler(res.value);
			});
		else
			handler(null);
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
	
	create_cell: function(win, table, sub_model, column, row_key, value, field_type, field_cfg, editable, container, onchange, oncreated, onsave) {
		var js = [["typed_field.js",field_type+".js"]];
		if (editable) js.push("editable_cell.js");
		win.require(js, function() {
			if (row_key > 0 && editable)
				new win.editable_cell(container, table+(sub_model ? "_"+sub_model : ""), column, row_key, field_type, field_cfg, value,onsave,onchange,function(ec){
					if (oncreated) oncreated(ec.editable_field.field);
				});
			else {
				var field = new win[field_type](value,editable,field_cfg);
				container.appendChild(field.getHTMLElement());
				if (onchange) field.onchange.add_listener(function(f) { onchange(f.getCurrentData()); });
				field.register_datamodel_cell(table,column,row_key);
				if (oncreated) oncreated(field);
			}
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
function _init_datamodel_js() {
	if (!window.top.pnapplication) {
		setTimeout(_init_datamodel_js, 25);
		return;
	}
	window.top.pnapplication.onwindowclosed.add_listener(function(c) { c.top.datamodel._windowClosed(c.win); });
}
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
