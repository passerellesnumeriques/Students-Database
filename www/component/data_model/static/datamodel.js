/* #depends[/static/application/application.js] */
datamodel = {
	_data_widgets: [],
	register_data_widget: function(win, data_display, data_key, data_getter, data_setter, register_listener) {
		this._data_widgets.push({win:win,data_display:data_display,data_key:data_key,data_getter:data_getter,data_setter:data_setter});
		register_listener(function(){
			window.top.datamodel.data_changed(data_display, data_key, data_getter());
		});
		if (data_display.cell)
			this.register_cell_widget(win, data_display.cell.table, data_display.cell.column, data_key, data_getter, data_setter, register_listener);
	},
	_cell_widgets: [],
	register_cell_widget: function(win, table, column, row_key, data_getter, data_setter, register_listener) {
		this._cell_widgets.push({win:win,table:table,column:column,row_key:row_key,data_getter:data_getter,data_setter:data_setter});
		register_listener(function(){
			window.top.datamodel.cell_changed(table, column, row_key, data_getter());
		});
	},
	register_cell_text: function(win, table, column, row_key, text_node) {
		window.top.datamodel.add_cell_change_listener(win, table, column, row_key, function(value) {
			text_node.nodeValue = value;
		});
	},
	
	_data_change_listeners: [],
	add_data_change_listener: function(win, data_display, data_key, listener) {
		this._data_change_listeners.push({win:win,data_display:data_display,data_key:data_key,listener:listener});
	},
	_cell_change_listeners: [],
	add_cell_change_listener: function(win, table, column, row_key, listener) {
		this._cell_change_listeners.push({win:win,table:table,column:column,row_key:row_key,listener:listener});
	},
	
	data_changed: function(data_display, data_key, value) {
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
	cell_changed: function(table, column, row_key, value) {
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
	
	get_cell_value: function(table, column, row_key, handler) {
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
	
	_window_closed: function(win) {
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
	}
};
window.top.pnapplication.onwindowclosed.add_listener(function(w) { datamodel._window_closed(w); });