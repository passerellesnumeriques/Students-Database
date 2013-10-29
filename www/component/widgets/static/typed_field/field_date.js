/** Date field: if editable, it will be a text input with a date picker, else only a simple text node
 * @constructor
 * @param config nothing for now
 */
function field_date(data,editable,config) {
	if (data != null && data.length == 0) data = null;
	typed_field.call(this, data, editable, config);
}
field_date.prototype = new typed_field();
field_date.prototype.constructor = field_date;		
field_date.prototype.parseDate = function(s) {
	var d = new Date();
	d.setHours(0,0,0,0);
	var a = s.split("-");
	if (a.length == 3) {
		d.setFullYear(parseInt(a[0]));
		d.setMonth(parseInt(a[1])-1);
		d.setDate(parseInt(a[2]));
	}
	return d;
};
field_date.prototype.dateString = function(d) {
	return d.getFullYear()+"-"+this._2digits(d.getMonth()+1)+"-"+this._2digits(d.getDate());
};
field_date.prototype._2digits = function(n) {
	var s = ""+n;
	while (s.length < 2) s = "0"+s;
	return s;
};
field_date.prototype._create = function(data) {
	if (this.editable) {
		require("date_picker.js"); require("context_menu.js");
		var t=this;
		var input = document.createElement("INPUT");
		input.type = "text";
		if (data) input.value = data;
		input.style.margin = "0px";
		input.style.padding = "0px";
		input.size = 10;
		var f = function() { setTimeout(function() { t._datachange(); },1); };
		var show = function(event) {
			if (event) stopEventPropagation(event);
			input.blur();
			require(["date_picker.js","context_menu.js"],function(){
				var menu = new context_menu();
				new date_picker(null,function(picker){
					picker.onchange = function(picker, date) {
						input.value = t.dateString(date);
						if (input.onchange) input.onchange();
						f();
					};
					picker.getElement().style.border = 'none';
					var d = t.getCurrentData();
					if (d != null)
						picker.setDate(t.parseDate(d));
					menu.addItem(picker.getElement());
					picker.getElement().onclick = null;
					menu.showBelowElement(input);
				});
			});
			return false;
		};
		input.onfocus = show;
		input.onclick = function(e) {
			stopEventPropagation(e);
			return false;
		};
		this.element.appendChild(input);
		this.getCurrentData = function() {
			if (input.value.length == 0) return null;
			return input.value; 
		};
		this.setData = function(data) {
			if (data != null && data.length == 0) data = null;
			input.value = data == null ? "" : data;
			if (input.onchange) input.onchange();
			f();
		};
		this.signal_error = function(error) {
			this.error = error;
			input.style.border = error ? "1px solid red" : "";
		};
	} else {
		this.setData = function(data, first) {
			if (data != null && data.length == 0) data = null;
			if (data == null) {
				if (this.element.innerHTML == "no date") return;
				this.element.style.fontStyle = 'italic';
				this.element.innerHTML = "no date";
			} else {
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
