/* #depends[typed_field.js] */
/** Date field: if editable, it will be a text input with a date picker, else only a simple text node
 * @param {String} data the date
 * @param {Boolean} editable editable
 * @param {Object} config all parameters are optional: <ul><li>show_utc: if true, date will be considered as UTC, else as local</li><li>minimum: minimum date</li><li>maximum: maximum date</li></ul>
 */
function field_date(data,editable,config) {
	if (data != null && data.length == 0) data = null;
	if (!config) config = {show_utc:false};
	if (typeof data == 'number') data = dateToSQL(new Date(data*1000),config.show_utc);
	else if (typeof data == 'string') {
		var d = parseSQLDate(data,config.show_utc);
		if (d == null) {
			var ts = parseInt(data);
			if (isNaN(ts)) data = null;
			else data = dateToSQL(new Date(ts*1000),config.show_utc);
		}
	}
	typed_field.call(this, data, editable, config);
	
	var t=this;
	this._register_datamodel_datadisplay = this.register_datamodel_datadisplay;
	this.register_datamodel_datadisplay = function(data_display, data_key) {
		this._register_datamodel_datadisplay(data_display, data_key);
		if (data_display.cell)
			this._registerCell(data_display.cell.table,data_display.cell.column,data_key);
	};
	this._register_datamodel_cell = this.register_datamodel_cell;
	this.register_datamodel_cell = function(table, column, row_key) {
		this._register_datamodel_cell(table,column,row_key);
		this._registerCell(table,column,row_key);
	};
	this._registerCell = function(table, column, row_key) {
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
				if (row_key > 0 && t.editable)
					window.top.datamodel.getCellValue(table,t.config.minimum_cell,row_key,listener);
				t.element.ondomremoved(function() {
					window.top.datamodel.removeCellChangeListener(listener);
				});
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
				if (row_key > 0 && t.editable)
					window.top.datamodel.getCellValue(table,t.config.maximum_cell,row_key,listener);
				t.element.ondomremoved(function() {
					window.top.datamodel.removeCellChangeListener(listener);
				});
			}, 1);
	};
}
field_date.prototype = new typed_field();
field_date.prototype.constructor = field_date;		
field_date.prototype.canBeNull = function() { return this.config && this.config.can_be_null; };
field_date.prototype.compare = function(v1,v2) {
	if (v1 == null) return v2 == null ? 0 : -1;
	if (v2 == null) return 1;
	v1 = parseSQLDate(v1);
	v2 = parseSQLDate(v2);
	if (v1.getTime() < v2.getTime()) return -1;
	if (v1.getTime() > v2.getTime()) return 1;
	return 0;
};
field_date.prototype.exportCell = function(cell) {
	var d = this.getCurrentData();
	if (d == null)
		cell.value = "";
	else {
		cell.value = d;
		cell.format = "date";
	}
};
field_date.prototype._create = function(data) {
	this.validate = function() {
		if (this.config && !this.config.can_be_null && this.getCurrentData() == null)
			this.signalError("Please select a valid date");
		else
			this.signalError(null);
	};

	if (this.editable) {
		require("date_picker.js"); require("context_menu.js");
		this.element.style.whiteSpace = 'nowrap';

		var t=this;
		this.signalError = function(error) {
			this.error = error;
			if (!t.select) { setTimeout(function(){t.signalError(error);},10); return; }
			t.select.select_year.style.border = error ? "1px solid red" : "";
			t.select.select_month.style.border = error ? "1px solid red" : "";
			t.select.select_day.style.border = error ? "1px solid red" : "";
		};
		this._set_date = data;
		this._getEditedData = function() {
			if (!t.select) return t._set_date;
			var date = t.select.getDate();
			if (date) date = dateToSQL(date, t.config.show_utc);
			return date;
		};
		require("date_select.js", function() {
			var min = t.config && t.config.minimum ? parseSQLDate(t.config.minimum) : new Date(1900,0,1);
			var max = t.config && t.config.maximum ? parseSQLDate(t.config.maximum) : new Date(new Date().getFullYear()+100,11,31);
			t.select = new date_select(t.element, parseSQLDate(t._data, t.config.show_utc), min, max, false, true);
			t.select.select_day.style.verticalAlign = "top";
			t.select.select_month.style.verticalAlign = "top";
			t.select.select_year.style.verticalAlign = "top";
			listenEvent(t.select.select_day, 'focus', function() { t.onfocus.fire(); });
			listenEvent(t.select.select_month, 'focus', function() { t.onfocus.fire(); });
			listenEvent(t.select.select_year, 'focus', function() { t.onfocus.fire(); });
			t.select.onchange = function() {
				t._datachange();
			};
			t.focus = function() { t.select.select_day.focus(); };
		});

		this._timeoutSetData = null;
		this._setData = function(data) {
			var d = parseSQLDate(data, t.config.show_utc);
			data = dateToSQL(d, t.config.show_utc);
			if (t.select)
				t.select.selectDate(parseSQLDate(data, t.config.show_utc));
			else {
				if (t._timeoutSetData) clearTimeout(t._timeoutSetData);
				t._set_date = data;
				t._timeoutSetData = setTimeout(function() { t._timeoutSetData = null; t._setData(data); }, 10);
			}
			return data;
		};
		this.setMinimum = function(min) {
			if (!min) {
				if (!t.config) return;
				if (t.config.minimum) {
					t.config.minimum = undefined;
					if (t.select) t.select.setLimits(new Date(1900,0,1), t.config.maximum ? parseSQLDate(t.config.maximum, t.config.show_utc) : new Date(new Date().getFullYear()+100,11,31));
				}
			} else {
				if (!t.config)
					t.config = {};
				t.config.minimum = min;
				if (t.select) t.select.setLimits(parseSQLDate(min, t.config.show_utc), t.config.maximum ? parseSQLDate(t.config.maximum, t.config.show_utc) : new Date(new Date().getFullYear()+100,11,31));
			}
		};
		this.setMaximum = function(max) {
			if (!max) {
				if (!t.config) return;
				if (t.config.maximum) {
					t.config.maximum = undefined;
					if (t.select) t.select.setLimits(t.config.minimum ? parseSQLDate(t.config.minimum, t.config.show_utc) : new Date(1900,0,1), new Date(new Date().getFullYear()+100,11,31));
				}
			} else {
				if (!t.config)
					t.config = {};
				t.config.maximum = max;
				if (t.select) t.select.setLimits(t.config.minimum ? parseSQLDate(t.config.minimum, t.config.show_utc) : new Date(1900,0,1), parseSQLDate(max, t.config.show_utc));
			}
		};
	} else {
		this._setData = function(data) {
			this.element.style.whiteSpace = "nowrap";
			if (data == null) {
				if (this.element.innerHTML == "no date") return null;
				this.element.style.fontStyle = 'italic';
				this.element.innerHTML = "no date";
			} else {
				data = dateToSQL(parseSQLDate(data, this.config.show_utc), this.config.show_utc);
				var d = parseSQLDate(data, this.config.show_utc);
				var s = getDayShortName(d.getDay(),true)+" "+_2digits(d.getDate())+" "+getMonthShortName(d.getMonth()+1)+" "+d.getFullYear();
				if (this.element.innerHTML == s) return data;
				this.element.style.fontStyle = 'normal';
				this.element.innerHTML = s;
			}
			return data;
		};
		this._setData(data);
		this.signalError = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};
/** Set the minimum and maximum selectable dates
 * @param {Date} min minimum date
 * @param {Date} max maximum date
 */
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
/** Set the minimum selectable date
 * @param {Date} min minimum date
 */
field_date.prototype.setMinimum = function(min) {
	if (!this.config) this.config = {};
	this.config.minimun = min;
	if (this.select) {
		min = min ? parseSQLDate(min) : new Date(1900,0,1);
		this.select.setLimits(min,this.select.maximum);
	}
	this.setData(this._getEditedData());
};
/** Set the maximum selectable date
 * @param {Date} max maximum date
 */
field_date.prototype.setMaximum = function(max) {
	if (!this.config) this.config = {};
	this.config.maximum = max;
	if (this.select) {
		max = max ? parseSQLDate(max) : new Date(new Date().getFullYear()+100,11,31);
		this.select.setLimits(this.select.minimum, max);
	}
	this.setData(this._getEditedData());
};
