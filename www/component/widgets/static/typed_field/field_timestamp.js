/* #depends[typed_field.js] */
if (typeof require != 'undefined') require("input_utils.js");

function field_timestamp(data,editable,config) {
	typed_field.call(this, data, editable, config);
}
field_timestamp.prototype = new typed_field();
field_timestamp.prototype.constructor = field_timestamp;		
field_timestamp.prototype.canBeNull = function() {
	if (!this.config) return true;
	if (this.config.can_be_null) return true;
	return false;
};
field_timestamp.prototype.exportCell = function(cell) {
	var t = this.getCurrentData();
	if (t == null)
		cell.value = "";
	else {
		cell.value = t;
		cell.format = "timestamp";
	}
};
field_timestamp.prototype._create = function(data) {
	if (this.editable) {
		var t=this;
		require(this.config && this.config.show_time ? ["field_date.js","field_time.js"] : "field_date.js", function() {
			var d = t._data == null ? null : new Date(t._data*(t.config && t.config.data_is_seconds ? 1000  : 1));
			var date = d == null ? null : dateToSQL(d);
			t._field_date = new field_date(date,true,{});
			t._field_date.onchange.add_listener(function() { t._datachange(); });
			t.element.appendChild(t._field_date.getHTMLElement());
			t._field_date.getHTMLElement().style.verticalAlign = "bottom";
			t._field_date.onfocus.add_listener(function(){t.onfocus.fire();});
			if (t.config && t.config.show_time) {
				var time = 0;
				if (d != null) time = d.getMinutes()+d.getHours()*60;
				var span = document.createElement("SPAN");
				span.appendChild(document.createTextNode(" at "));
				span.style.verticalAlign = "bottom";
				t.element.appendChild(span);
				t._field_time = new field_time(time,true,{});
				t._field_time.onchange.add_listener(function() { t._datachange(); });
				t.element.appendChild(t._field_time.getHTMLElement());
				t._field_time.getHTMLElement().style.verticalAlign = "bottom";
				t._field_time.onfocus.add_listener(function(){t.onfocus.fire();});
			}
		});
		this._setData = function(data) {
			if (data == null) {
				if (t._field_date) t._field_date.setData(null);
				if (t._field_time) t._field_time.setData(null);
			} else {
				var d = new Date(data*(t.config && t.config.data_is_seconds ? 1000  : 1));
				if (t._field_date) t._field_date.setData(d);
				if (t._field_time) t._field_time.setData(d.getMinutes()+d.getHours()*60);
			}
		};
		this._getEditedData = function() {
			if (!t._field_date) return t._data;
			var d = t._field_date._getEditedData();
			if (d == null) return null;
			d = parseSQLDate(d);
			d.setHours(0,0,0,0);
			if (t._field_time) {
				var time = t._field_time.getCurrentMinutes();
				if (time != null) d.setHours(0,time,0,0);
			}
			var timestamp = d.getTime();
			if (t.config && t.config.data_is_seconds) timestamp = Math.floor(timestamp/1000);
			return timestamp;
		};
		this.signal_error = function(error) {
			this.error = error;
			this.element.style.border = error ? "1px solid red" : "";
		};
	} else {
		if (this.config && this.config.style)
			for (var s in this.config.style)
				this.element.style[s] = this.config.style[s];
		this._setData = function(data) {
			if (data != null) data = parseInt(data);
			if (data == 0) data = null;
			this.element.removeAllChildren();
			var text;
			if (data == null) text = "";
			else {
				if (this.config && this.config.data_is_seconds)
					data *= 1000;
				var d = new Date(data);
				text = getDayShortName(d.getDay(), true)+" "+d.getDate()+" "+getMonthName(d.getMonth()+1)+" "+d.getFullYear();
				if (this.config && this.config.show_time) {
					var hours = d.getHours();
					var h = hours > 12 ? hours-12 : hours;
					var ampm = hours > 12 ? "PM" : "AM";
					text += " "+_2digits(h)+":"+_2digits(d.getMinutes())+ampm;
				}
			}
			this.element.appendChild(document.createTextNode(text));
		};
		this._setData(data);
		this.signal_error = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};