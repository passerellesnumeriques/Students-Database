/* #depends[typed_field.js] */
/** Date field: if editable, it will be a text input with a date picker, else only a simple text node
 * @constructor
 * @param config nothing for now
 */
function field_date(data,editable,config) {
	if (data != null && data.length == 0) data = null;
	typed_field.call(this, data, editable, config);
	
	var t=this;
	this._register_datamodel_datadisplay = this.register_datamodel_datadisplay;
	this.register_datamodel_datadisplay = function(data_display, data_key) {
		this._register_datamodel_datadisplay(data_display, data_key);
		if (data_display.cell)
			this._register_cell(data_display.cell.table,data_display.cell.column,data_key);
	};
	this._register_datamodel_cell = this.register_datamodel_cell;
	this.register_datamodel_cell = function(table, column, row_key) {
		this._register_datamodel_cell(table,column,row_key);
		this._register_cell(table,column,row_key);
	};
	this._register_cell = function(table, column, row_key) {
		if (t.config && t.config.minimum_cell)
			setTimeout(function() {
				var listener = function(value){
					t.config.minimum = value;
					if (t.select) {
						var min = t.config && t.config.minimum ? parseSQLDate(t.config.minimum) : new Date(1900,0,1);
						var max = t.config && t.config.maximum ? parseSQLDate(t.config.maximum) : new Date(new Date().getFullYear()+100,11,31);
						t.select.setLimits(min,max);
					}
				};
				window.top.datamodel.addCellChangeListener(window, table,t.config.minimum_cell,row_key, listener);
				window.top.datamodel.getCellValue(table,t.config.minimum_cell,row_key,listener);
			}, 1);
		if (t.config && t.config.maximum_cell)
			setTimeout(function() {
				var listener = function(value){
					t.config.maximum = value;
					if (t.select) {
						var min = t.config && t.config.minimum ? parseSQLDate(t.config.minimum) : new Date(1900,0,1);
						var max = t.config && t.config.maximum ? parseSQLDate(t.config.maximum) : new Date(new Date().getFullYear()+100,11,31);
						t.select.setLimits(min,max);
					}
				};
				window.top.datamodel.addCellChangeListener(window, table,t.config.maximum_cell,row_key, listener);
				window.top.datamodel.getCellValue(table,t.config.maximum_cell,row_key,listener);
			}, 1);
	};
}
field_date.prototype = new typed_field();
field_date.prototype.constructor = field_date;		
field_date.prototype.canBeNull = function() { return this.config && this.config.can_be_null; };
field_date.prototype._create = function(data) {
	this.validate = function() {
		if (this.config && !this.config.can_be_null && this.getCurrentData() == null)
			this.signal_error("Please select a valid date");
		else
			this.signal_error(null);
	};

	if (this.editable) {
		require("date_picker.js"); require("context_menu.js");
		this.element.style.whiteSpace = 'nowrap';

		var t=this;
		this.signal_error = function(error) {
			this.error = error;
			if (!t.select) { setTimeout(function(){t.signal_error(error);},10); return; }
			t.select.select_year.style.border = error ? "1px solid red" : "";
			t.select.select_month.style.border = error ? "1px solid red" : "";
			t.select.select_day.style.border = error ? "1px solid red" : "";
		};
		this._set_date = data;
		this._getEditedData = function() {
			if (!t.select) return t._set_date;
			var date = t.select.getDate();
			if (date) date = dateToSQL(date);
			return date;
		};
		require("date_select.js", function() {
			var min = t.config && t.config.minimum ? parseSQLDate(t.config.minimum) : new Date(1900,0,1);
			var max = t.config && t.config.maximum ? parseSQLDate(t.config.maximum) : new Date(new Date().getFullYear()+100,11,31);
			t.select = new date_select(t.element, parseSQLDate(t._data), min, max, false, true);
			t.select.select_day.style.verticalAlign = "top";
			t.select.select_month.style.verticalAlign = "top";
			t.select.select_year.style.verticalAlign = "top";
			listenEvent(t.select.select_day, 'focus', function() { t.onfocus.fire(); });
			listenEvent(t.select.select_month, 'focus', function() { t.onfocus.fire(); });
			listenEvent(t.select.select_year, 'focus', function() { t.onfocus.fire(); });
			t.select.onchange = function() {
				t._datachange();
			};
		});

		this._timeoutSetData = null;
		this._setData = function(data) {
			if (t.select)
				t.select.selectDate(parseSQLDate(data));
			else {
				if (t._timeoutSetData) clearTimeout(t._timeoutSetData);
				t._set_date = data;
				t._timeoutSetData = setTimeout(function() { t._timeoutSetData = null; t._setData(data); }, 10);
			}
		};
		this.setMinimum = function(min) {
			if (!min) {
				if (!t.config) return;
				if (t.config.minimum) {
					t.config.minimum = undefined;
					t.select.setLimits(new Date(1900,0,1), t.config.maximum ? parseSQLDate(t.config.maximum) : new Date(new Date().getFullYear()+100,11,31));
				}
			} else {
				if (!t.config)
					t.config = {};
				t.config.minimum = min;
				t.select.setLimits(parseSQLDate(min), t.config.maximum ? parseSQLDate(t.config.maximum) : new Date(new Date().getFullYear()+100,11,31));
			}
		};
		this.setMaximum = function(max) {
			if (!max) {
				if (!t.config) return;
				if (t.config.maximum) {
					t.config.maximum = undefined;
					t.select.setLimits(t.config.minimum ? parseSQLDate(t.config.minimum) : new Date(1900,0,1), new Date(new Date().getFullYear()+100,11,31));
				}
			} else {
				if (!t.config)
					t.config = {};
				t.config.maximum = max;
				t.select.setLimits(t.config.minimum ? parseSQLDate(t.config.minimum) : new Date(1900,0,1), parseSQLDate(max));
			}
		};
	} else {
		this._setData = function(data) {
			this.element.style.whiteSpace = "nowrap";
			if (data == null) {
				if (this.element.innerHTML == "no date") return;
				this.element.style.fontStyle = 'italic';
				this.element.innerHTML = "no date";
			} else {
				data = dateToSQL(parseSQLDate(data));
				if (this.element.innerHTML == data) return;
				this.element.style.fontStyle = 'normal';
				this.element.innerHTML = data;
			}
		};
		this._setData(data);
		this.signal_error = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};
field_date.prototype.setLimits = function(min,max) {
	if (!this.config) this.config = {};
	this.config.minimun = min;
	this.config.maximum = max;
	if (this.select) {
		min = min ? parseSQLDate(min) : new Date(1900,0,1);
		max = max ? parseSQLDate(max) : new Date(new Date().getFullYear()+100,11,31);
		this.select.setLimits(min,max);
	}
	this.setData(this._getEditedData());
};
field_date.prototype.setMinimum = function(min) {
	if (!this.config) this.config = {};
	this.config.minimun = min;
	if (this.select) {
		min = min ? parseSQLDate(min) : new Date(1900,0,1);
		this.select.setLimits(min,this.select.maximum);
	}
	this.setData(this._getEditedData());
};
field_date.prototype.setMaximum = function(max) {
	if (!this.config) this.config = {};
	this.config.maximum = max;
	if (this.select) {
		max = max ? parseSQLDate(max) : new Date(new Date().getFullYear()+100,11,31);
		this.select.setLimits(this.select.minimum, max);
	}
	this.setData(this._getEditedData());
};
