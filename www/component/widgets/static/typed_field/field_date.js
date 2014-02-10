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
field_date.prototype.canBeNull = function() { return this.config && this.config.can_be_empty; };
field_date.prototype.parseDate = function(d) {
	var a = d.split("-");
	if (a.length == 3) return parseSQLDate(d);
	a = new Date(d);
	return a;
};
field_date.prototype._create = function(data) {
	if (this.editable) {
		require("date_picker.js"); require("context_menu.js");
		this.element.style.whiteSpace = 'nowrap';

		var t=this;
		this.data = data;
		this.signal_error = function(error) {
			this.error = error;
			if (!t.select) setTimeout(function(){t.signal_error(error);},10);
			t.select.select_year.style.border = error ? "1px solid red" : "";
			t.select.select_month.style.border = error ? "1px solid red" : "";
			t.select.select_day.style.border = error ? "1px solid red" : "";
		};
		require("date_select.js", function() {
			var min = t.config && t.config.minimum ? parseSQLDate(t.config.minimum) : new Date(1900,0,1);
			var max = t.config && t.config.maximum ? parseSQLDate(t.config.maximum) : new Date(new Date().getFullYear()+100,11,31);
			t.select = new date_select(t.element, t.data == null ? null : t.parseDate(t.data), min, max);
			t.select.select_day.style.verticalAlign = "top";
			t.select.select_month.style.verticalAlign = "top";
			t.select.select_year.style.verticalAlign = "top";
			t.select.onchange = function() {
				var date = t.select.getDate();
				if (date) date = dateToSQL(date);
				if (date == t.data) return;
				t.data = date;
				t.validate();
				setTimeout(function() { t._datachange(); },1);
			};
			t.icon = document.createElement("IMG");
			t.icon.src = theme.icons_16.date_picker;
			t.icon.style.verticalAlign = "top";
			t.icon.style.cursor = "pointer";
			t.icon.onclick = function() {
				require(["date_picker.js","context_menu.js"],function(){
					var menu = new context_menu();
					new date_picker(t.select.getDate(),t.select.minimum,t.select.maximum,function(picker){
						picker.onchange = function(picker, date) {
							t.select.selectDate(date);
						};
						picker.getElement().style.border = 'none';
						menu.addItem(picker.getElement());
						picker.getElement().onclick = null;
						menu.element.className = menu.element.className+" popup_date_picker";
						menu.showBelowElement(t.element);
					});
				});
			};
			t.element.appendChild(t.icon);
			t.validate = function() {
				if (t.config && !t.config.can_be_empty && t.data == null)
					t.signal_error("Please select a valid date");
				else
					t.signal_error(null);
			};
			t.validate();
		});

		this.getCurrentData = function() {
			return t.data;
		};
		this.setData = function(data) {
			if (data == t.data) return;
			t.data = data;
			if (t.select) {
				t.select.selectDate(data == null ? null : t.parseDate(data));
				t.validate();
			}
			if (data != t.getOriginalData()) setTimeout(function() { t._datachange(); },1);
		};
	} else {
		this.setData = function(data, first) {
			if (data != null && data.length == 0) data = null;
			if (data == null) {
				if (this.element.innerHTML == "no date") return;
				this.element.style.fontStyle = 'italic';
				this.element.innerHTML = "no date";
			} else {
				data = dateToSQL(this.parseDate(data));
				if (this.element.innerHTML == data) return;
				this.element.style.fontStyle = 'normal';
				this.element.innerHTML = data;
			}
			if (!first) this._datachange();
		};
		this.setData(data, true);
		this.getCurrentData = function() {
			return this.element.innerHTML == "no date" ? null : this.element.innerHTML;
		};
		this.signal_error = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};
