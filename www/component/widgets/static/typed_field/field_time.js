/** Time field: if editable, it will be a text input, else only a simple text node
 * @constructor
 * @param config can contain: <code>can_be_null</code>
 */
function field_time(data,editable,config) {
	if (data == null) data = "";
	if (typeof data == 'number') data = this.getTimeString(data);
	else data = this.getTimeString(this.parseTime(data));
	typed_field.call(this, data, editable, config);
}
field_time.prototype = new typed_field();
field_time.prototype.constructor = field_time;		
field_time.prototype._2digits = function(n) {
	var s = ""+n;
	while (s.length < 2) s = "0"+s;
	return s;
};
field_time.prototype.getTimeString = function(time) {
	var h = Math.floor(time/60);
	var m = time-h*60;
	return this._2digits(h)+":"+this._2digits(m);
};
field_time.prototype.parseTime = function(s) {
	var i = s.indexOf(':');
	var h,m;
	if (i < 0) {
		h = parseInt(s);
		m = 0;
	} else {
		h = parseInt(s.substring(0,i));
		m = parseInt(s.substring(i+1));
	}
	if (isNaN(h)) h = 0; else if (h > 23) h = 23; else if (h < 0) h = 0;
	if (isNaN(m)) m = 0; else if (m > 59) m = 59; else if (m < 0) m = 0;
	return h*60+m;
};
field_time.prototype._create = function(data) {
	if (this.editable) {
		var t=this;
		var input = document.createElement("INPUT");
		input.type = "text";
		input.maxlength = 5;
		if (data) input.value = data;
		input.style.margin = "0px";
		input.style.padding = "0px";
		require("autoresize_input.js",function(){autoresize_input(input);});
		var f = function() { setTimeout(function() { t._datachange(); },1); };
		input.onblur = function(ev) {
			if (input.value.length == 0 && (!t.config || !t.config.can_be_null)) input.value = "00:00";
			var time = t.parseTime(input.value);
			input.value = t.getTimeString(time);
			f();
		};
		this.element.appendChild(input);
		this.getCurrentData = function() {
			if (input.value.length == 0) return this.config && this.config.can_be_null ? null : "00:00";
			return t.getTimeString(t.parseTime(input.value));
		};
		this.getCurrentMinutes = function() {
			if (input.value.length == 0) return this.config && this.config.can_be_null ? null : 0;
			return t.parseTime(input.value);
		};
		this.setData = function(data) {
			if (data == null) input.value = "";
			else if (typeof data == 'number') input.value = this.getTimeString(data);
			else input.value = this.getTimeString(this.parseTime(data));
			f();
		};
		this.signal_error = function(error) {
			this.error = error;
			input.style.border = error ? "1px solid red" : "";
		};
	} else {
		this.element.appendChild(this.text = document.createTextNode(data));
		this.setData = function(data) {
			var text;
			if (data == null) text = "";
			else if (typeof data == 'number') text = this.getTimeString(data);
			else text = this.getTimeString(this.parseTime(data));
			if (this.text.nodeValue == text) return;
			this.text.nodeValue = text;
			this._datachange();
		};
		this.getCurrentData = function() {
			var s = this.text.nodeValue;
			if (s.length == 0) return null;
			return s;
		};
		this.getCurrentMinutes = function() {
			var s = this.text.nodeValue;
			if (s.length == 0) return this.config && this.config.can_be_null ? null : 0;
			return this.parseTime(s);
		};
		this.signal_error = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};