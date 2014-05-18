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
field_date.prototype._create = function(data) {
	this.validate = function() {
		if (this.config && !this.config.can_be_empty && this.getCurrentData() == null)
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
			if (!t.select) setTimeout(function(){t.signal_error(error);},10);
			t.select.select_year.style.border = error ? "1px solid red" : "";
			t.select.select_month.style.border = error ? "1px solid red" : "";
			t.select.select_day.style.border = error ? "1px solid red" : "";
		};
		require("date_select.js", function() {
			var min = t.config && t.config.minimum ? parseSQLDate(t.config.minimum) : new Date(1900,0,1);
			var max = t.config && t.config.maximum ? parseSQLDate(t.config.maximum) : new Date(new Date().getFullYear()+100,11,31);
			t.select = new date_select(t.element, parseSQLDate(t.data), min, max);
			t.select.select_day.style.verticalAlign = "top";
			t.select.select_month.style.verticalAlign = "top";
			t.select.select_year.style.verticalAlign = "top";
			t.select.onchange = function() {
				t._datachange();
			};
			t._getEditedData = function() {
				var date = t.select.getDate();
				if (date) date = dateToSQL(date);
				return date;
			};
			t.icon = document.createElement("IMG");
			t.icon.src = theme.icons_16.date_picker;
			t.icon.style.verticalAlign = "top";
			t.icon.style.cursor = "pointer";
			t.icon.onclick = function(ev) {
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
				stopEventPropagation(ev);
			};
			t.element.appendChild(t.icon);
		});

		this._timeoutSetData = null;
		this._setData = function(data) {
			if (t.select)
				t.select.selectDate(parseSQLDate(data));
			else {
				if (t._timeoutSetData) clearTimeout(t._timeoutSetData);
				t._timeoutSetData = setTimeout(function() { t._timeoutSetData = null; t._setData(data); }, 10);
			}
		};
	} else {
		this._setData = function(data) {
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
