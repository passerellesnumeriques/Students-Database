/* #depends[typed_field.js] */
/** Decimal field: if editable, it will be a text input, else only a simple text node
 * @constructor
 * @param config must contain: <code>integer_digits</code>: digits before the decimal part, <code>decimal_digits</code> ; can contain: <code>min</code>, <code>max</code>, <code>can_be_null</code>
 */
function field_decimal(data,editable,config) {
	if (typeof data == 'string') data = parseFloat(data);
	if (isNaN(data)) data = null;
	if (data == null) data = "";
	typed_field.call(this, data, editable, config);
}
field_decimal.prototype = new typed_field();
field_decimal.prototype.constructor = field_decimal;		
field_decimal.prototype.canBeNull = function() { return this.config && this.config.can_be_null; };		
field_decimal.prototype._create = function(data) {
	if (this.editable) {
		var t=this;
		var input = document.createElement("INPUT");
		input.type = "text";
		input.onclick = function(ev) { this.focus(); stopEventPropagation(ev); return false; };
		input.maxLength = this.config.integer_digits + 1 + this.config.decimal_digits;
		if (data) input.value = parseFloat(data).toFixed(t.config.decimal_digits);
		input.style.margin = "0px";
		input.style.padding = "0px";
		var onkeyup = new Custom_Event();
		input.onkeyup = function(e) { onkeyup.fire(e); };
		input.onkeydown = function(e) {
			var ev = getCompatibleKeyEvent(e);
			if (ev.isPrintable) {
				if (!isNaN(parseInt(ev.printableChar)) || ev.ctrlKey) {
					// digit: ok
					return true;
				}
				// not a digit
				if (ev.printableChar == "-" && input.value.length == 0) {
					// - at the beginning: ok
					return true;
				}
				if (ev.printableChar == ",") ev.printableChar = e.keyCode = ".";
				if (ev.printableChar == ".") {
					if (input.value.length > 0 && input.value.indexOf('.') < 0) {
						return true;
					}
				}
				stopEventPropagation(e);
				return false;
			}
			return true;
		};
		var getValueFromInput = function() {
			var value;
			if (input.value.length == 0 && t.config.can_be_null) value = null;
			else {
				if (input.value.length == 0 && !t.config.can_be_null) value = t.config.min ? t.config.min : 0;
				var i = parseFloat(input.value);
				if (isNaN(i)) i = t.config.min ? t.config.min : 0;
				if (typeof t.config.min != 'undefined' && i < t.config.min) i = t.config.min;
				if (typeof t.config.max != 'undefined' && i > t.config.max) i = t.config.max;
				value = i.toFixed(t.config.decimal_digits);
			}
			return value;
		};
		input.onblur = function(ev) {
			t.setData(t._getEditedData());
		};
		listenEvent(input, 'focus', function() { t.onfocus.fire(); });
		var _fw=false;
		require("input_utils.js",function(){inputAutoresize(input);if (_fw) input.setMinimumSize(-1); });
		this.element.appendChild(input);
		this._getEditedData = function() {
			var value = getValueFromInput();
			if (value == null) return null;
			return parseFloat(value).toFixed(this.config.decimal_digits);
		};
		this._setData = function(data) {
			if (typeof data == 'string') data = parseFloat(data);
			if (isNaN(data)) data = null;
			if (data == null) input.value = "";
			else input.value = data.toFixed(t.config.decimal_digits);
		};
		this.signal_error = function(error) {
			this.error = error;
			input.style.border = error ? "1px solid red" : "";
		};
		this.onenter = function(listener) {
			onkeyup.add_listener(function(e) {
				var ev = getCompatibleKeyEvent(e);
				if (ev.isEnter) listener(t);
			});
		};
		this.fillWidth = function() {
			_fw = true;
			this.element.style.width = "100%";
			if (typeof input.setMinimumSize != 'undefined') input.setMinimumSize(-1);
		};
	} else {
		this.element.appendChild(this.text = document.createTextNode(data == null ? "" : data));
		this._setData = function(data) {
			var s = data == null ? "" : data;
			if (this.text.nodeValue == s) return;
			this.text.nodeValue = s;
		};
		this.signal_error = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};
field_decimal.prototype.setLimits = function(min,max) {
	if (!this.config) this.config = {};
	this.config.min = min;
	this.config.max = max;
	this.setData(this._getEditedData());
};
field_decimal.prototype.setMinimum = function(min) {
	if (!this.config) this.config = {};
	this.config.min = min;
	this.setData(this._getEditedData());
};
field_decimal.prototype.setMaximum = function(max) {
	if (!this.config) this.config = {};
	this.config.max = max;
	this.setData(this._getEditedData());
};
