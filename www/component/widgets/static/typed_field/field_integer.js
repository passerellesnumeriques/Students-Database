/* #depends[typed_field.js] */
/** Integer field: if editable, it will be a text input, else only a simple text node
 * @constructor
 * @param config can contain: <code>min</code>, <code>max</code>, <code>can_be_null</code>
 */
function field_integer(data,editable,config) {
	if (typeof data == 'string') data = parseInt(data);
	if (isNaN(data)) data = null;
	if (config && !config.can_be_null && data == null) data = 0;
	typed_field.call(this, data, editable, config);
}
field_integer.prototype = new typed_field();
field_integer.prototype.constructor = field_integer;		
field_integer.prototype.canBeNull = function() { return this.config && this.config.can_be_null; };
field_integer.prototype.compare = function(v1,v2) {
	if (v1 == null) return v2 == null ? 0 : -1;
	if (v2 == null) return 1;
	v1 = parseInt(v1);
	if (isNaN(v1)) return 1;
	v2 = parseInt(v2);
	if (isNaN(v2)) return -1;
	if (v1 < v2) return -1;
	if (v1 > v2) return 1;
	return 0;
};
field_integer.prototype.exportCell = function(cell) {
	var val = this.getCurrentData();
	if (val == null)
		cell.value = "";
	else {
		cell.value = val;
		cell.format = "number:0";
	}
};
field_integer.prototype._create = function(data) {
	if (this.editable) {
		var t=this;
		t.input = document.createElement("INPUT");
		t.input.type = "text";
		t.input.onclick = function(ev) { this.focus(); stopEventPropagation(ev); return false; };
		if (this.config && this.config.min && this.config.max) {
			var m = Math.max((""+this.config.min).length,(""+this.config.max).length);
			t.input.maxLength = m;
		}
		if (data) t.input.value = data;
		t.input.style.margin = "0px";
		t.input.style.padding = "0px";
		var onkeyup = new Custom_Event();
		t.input.onkeyup = function(e) { 
			onkeyup.fire(e);
			setTimeout(function() {
				t.setData(t._getEditedData());
			},1);
		};
		t.input.onkeydown = function(e) {
			var ev = getCompatibleKeyEvent(e);
			if (ev.isPrintable) {
				if (!isNaN(parseInt(ev.printableChar)) || ev.ctrlKey) {
					// digit: ok
					return true;
				}
				// not a digit
				if (ev.printableChar == "-" && t.input.value.length == 0) {
					// - at the beginning: ok
					return true;
				}
				stopEventPropagation(e);
				return false;
			}
			return true;
		};
		var getValueFromInput = function() {
			var value;
			if (t.input.value.length == 0 && t.config.can_be_null) value = null;
			else {
				if (t.input.value.length == 0 && !t.config.can_be_null) value = t.config.min ? t.config.min : 0;
				var i = parseInt(t.input.value);
				if (isNaN(i)) i = t.config.min ? t.config.min : 0;
				if (typeof t.config.min != 'undefined' && i < t.config.min) i = t.config.min;
				if (typeof t.config.max != 'undefined' && i > t.config.max) i = t.config.max;
				value = i;
			}
			return value;
		};
		t.input.onblur = function(ev) {
			var val = t._getEditedData();
			t.input.value = val;
			t.setData(val);
		};
		listenEvent(t.input, 'focus', function() { t.onfocus.fire(); });
		var _fw = false;
		require("input_utils.js",function(){inputAutoresize(t.input);if (_fw) t.input.setMinimumSize(-1);});
		this.element.appendChild(t.input);
		this._getEditedData = function() {
			var value = getValueFromInput();
			if (value == null) return null;
			return parseInt(value);
		};
		this._setData = function(data) {
			if (typeof data == 'string') data = parseInt(data);
			if (isNaN(data)) data = null;
			if (t.config && !t.config.can_be_null && data === null) data = 0;
			if (data === null) t.input.value = "";
			else t.input.value = data;
			if (typeof t.input.autoresize != 'undefined') t.input.autoresize();
			return data;
		};
		this.signal_error = function(error) {
			this.error = error;
			t.input.style.border = error ? "1px solid red" : "";
		};
		this.onenter = function(listener) {
			onkeyup.add_listener(function(e) {
				var ev = getCompatibleKeyEvent(e);
				if (ev.isEnter) listener(t);
			});
		};
		this.focus = function() { t.input.focus(); };
		this.fillWidth = function() {
			_fw = true;
			this.element.style.width = "100%";
			if (typeof t.input.setMinimumSize != 'undefined') t.input.setMinimumSize(-1);
		};
		if (t.config) {
			var prev = data;
			if (typeof data == 'string') {
				data = parseInt(data);
				if (isNaN(data)) data = null;
			}
			if (!t.config.can_be_null) {
				if (data == null) data = t.config.min;
			}
			if (data != null && data < t.config.min) data = t.config.min;
			if (data != null && data > t.config.max) data = t.config.max;
			if (data != prev) { t.setData(data); }
		}
		this.setMinimum = function(min) {
			t.config.min = min === null ? undefined : min;
			if (typeof t.config.min != 'undefined' && typeof t.config.max != 'undefined' && t.config.max < min) t.config.max = min;
			t.setData(getValueFromInput());
		};
		this.setMaximum = function(max) {
			t.config.max = max === null ? undefined : max;
			if (typeof t.config.min != 'undefined' && typeof t.config.max != 'undefined' && t.config.min < max) t.config.min = max;
			t.setData(getValueFromInput());
		};
	} else {
		this.element.appendChild(this.text = document.createTextNode(data == null ? "" : data));
		this._setData = function(data) {
			if (typeof data == 'string') data = parseInt(data);
			if (isNaN(data)) data = null;
			if (this.config && !this.config.can_be_null && data === null) data = 0;
			if (data === null) this.text.nodeValue = "";
			else this.text.nodeValue = data;
			return data;
		};
		this.signal_error = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};
field_integer.prototype.setLimits = function(min,max) {
	if (!this.config) this.config = {};
	this.config.min = min;
	this.config.max = max;
	this.setData(this._getEditedData());
};
field_integer.prototype.setMinimum = function(min) {
	if (!this.config) this.config = {};
	this.config.min = min;
	this.setData(this._getEditedData());
};
field_integer.prototype.setMaximum = function(max) {
	if (!this.config) this.config = {};
	this.config.max = max;
	this.setData(this._getEditedData());
};
