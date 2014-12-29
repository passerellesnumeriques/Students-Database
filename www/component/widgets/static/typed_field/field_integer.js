/* #depends[typed_field.js] */
/** Integer field: if editable, it will be a text input, else only a simple text node
 * @constructor
 * @param config can contain: <code>min</code>, <code>max</code>, <code>can_be_null</code>
 */
function field_integer(data,editable,config) {
	if (typeof data == 'string') data = parseInt(data);
	if (isNaN(data)) data = null;
	if (config && typeof config.min == 'string') config.min = parseInt(config.min);
	if (config && typeof config.max == 'string') config.max = parseInt(config.max);
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
		t.input.ondomremoved(function() {
			t.input = null;
			t = null;
		});
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
			if (!t || !t.input || t.input.value.length == 0) value = null;
			else {
				value = parseInt(t.input.value);
				if (isNaN(value)) value = null;
				else {
					if (typeof t.config.min != 'undefined' && value < t.config.min) value = t.config.min;
					if (typeof t.config.max != 'undefined' && value > t.config.max) value = t.config.max;
				}
			}
			return value;
		};
		t.input.onblur = function(ev) {
			if (!t) return;
			var val = t._getEditedData();
			t.input.value = val === null ? "" : val;
			t.setData(val);
		};
		listenEvent(t.input, 'focus', function() { t.onfocus.fire(); });
		var _fw = false;
		require("input_utils.js",function(){inputAutoresize(t.input);if (_fw) t.input.setMinimumSize(-1);});
		this.element.appendChild(t.input);
		this._getEditedData = function() {
			var value = getValueFromInput();
			if (value === null) return null;
			return parseInt(value);
		};
		this._setData = function(data) {
			if (typeof data == 'string') data = parseInt(data);
			if (isNaN(data)) data = null;
			if (data === null) t.input.value = "";
			else t.input.value = data;
			if (typeof t.input.autoresize != 'undefined') t.input.autoresize();
			return data;
		};
		this.signalError = function(error) {
			this.error = error;
			t.input.style.border = error ? "1px solid red" : "";
		};
		this.validate = function() {
			var value = getValueFromInput();
			if (value === null && t.config && !t.config.can_be_null) this.signalError("Please enter a value");
			else this.signalError(null);
		};
		this.onenter = function(listener) {
			onkeyup.addListener(function(e) {
				var ev = getCompatibleKeyEvent(e);
				if (ev.isEnter) listener(t);
			});
		};
		this.focus = function() { t.input.focus(); };
		this._fillWidth = function() {
			_fw = true;
			if (typeof t.input.setMinimumSize != 'undefined') t.input.setMinimumSize(-1);
		};
		if (t.config) {
			var prev = data;
			if (typeof data == 'string') {
				data = parseInt(data);
				if (isNaN(data)) data = null;
			}
			if (data !== null && data < t.config.min) data = t.config.min;
			if (data !== null && data > t.config.max) data = t.config.max;
			if (data != prev) { t.setData(data); }
		}
		this.setMinimum = function(min) {
			if (typeof min == 'string') min = parseInt(min);
			if (isNaN(min)) min = null;
			t.config.min = min === null ? undefined : min;
			if (typeof t.config.min != 'undefined' && typeof t.config.max != 'undefined' && t.config.max < min) t.config.max = min;
			t.setData(getValueFromInput());
		};
		this.setMaximum = function(max) {
			if (typeof max == 'string') max = parseInt(max);
			if (isNaN(max)) max = null;
			t.config.max = max === null ? undefined : max;
			if (typeof t.config.min != 'undefined' && typeof t.config.max != 'undefined' && t.config.min > max) t.config.min = max;
			t.setData(getValueFromInput());
		};
	} else {
		this.element.appendChild(this.text = document.createTextNode(""));
		this._setData = function(data) {
			if (typeof data == 'string') data = parseInt(data);
			if (isNaN(data)) data = null;
			if (data === null) this.text.nodeValue = "";
			else if (this.config && this.config.pad) {
				var s = ""+data;
				while (s.length < this.config.pad) s = "0"+s;
				this.text.nodeValue = s;
			} else
				this.text.nodeValue = data;
			return data;
		};
		this.signalError = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
		this.validate = function() {
			if (this._data === null && this.config && !this.config.can_be_null) this.signalError("Please enter a value");
			else this.signalError(null);
		};
		this.setMinimum = function(min) {
			if (typeof min == 'string') min = parseInt(min);
			if (isNaN(min)) min = null;
			if (!this.config) this.config = {};
			this.config.min = min;
			this.setData(this._getEditedData());
		};
		this.setMaximum = function(max) {
			if (typeof max == 'string') max = parseInt(max);
			if (isNaN(max)) max = null;
			if (!this.config) this.config = {};
			this.config.max = max;
			this.setData(this._getEditedData());
		};
		this._setData(data);
	}
};
field_integer.prototype.setLimits = function(min,max) {
	if (!this.config) this.config = {};
	this.config.min = min;
	this.config.max = max;
	this.setData(this._getEditedData());
};
field_integer.prototype.setMinimum = function(min) {
	if (typeof min == 'string') min = parseInt(min);
	if (isNaN(min)) min = null;
	if (!this.config) this.config = {};
	this.config.min = min;
	this.setData(this._getEditedData());
};
field_integer.prototype.setMaximum = function(max) {
	if (typeof max == 'string') max = parseInt(max);
	if (isNaN(max)) max = null;
	if (!this.config) this.config = {};
	this.config.max = max;
	this.setData(this._getEditedData());
};
