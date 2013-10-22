/** Integer field: if editable, it will be a text input, else only a simple text node
 * @constructor
 * @param config can contain: <code>min</code>, <code>max</code>, <code>can_be_null</code>
 */
function field_integer(data,editable,onchanged,onunchanged,config) {
	if (data == null) data = "";
	typed_field.call(this, data, editable, onchanged, onunchanged);
	if (editable) {
		var t=this;
		var input = document.createElement("INPUT");
		input.type = "text";
		if (config && config.min && config.max) {
			var m = Math.max((""+config.min).length,(""+config.max).length);
			input.maxlength = m;
		}
		if (data) input.value = data;
		input.style.margin = "0px";
		input.style.padding = "0px";
		require("autoresize_input.js",function(){autoresize_input(input);});
		var f = function() {
			setTimeout(function() {
				if (input.value != data) {
					if (onchanged)
						onchanged(t,input.value);
				} else {
					if (onunchanged)
						onunchanged(t);
				}
			},1);
		};
		input.onkeydown = function(ev) {
			ev = getCompatibleKeyEvent(ev);
			if (ev.isPrintable) {
				if (!isNaN(parseInt(ev.printableChar))) {
					// digit: ok
					f();
					return true;
				}
				// not a digit
				if (ev.printableChar == "-" && input.value.length == 0) {
					// - at the beginning: ok
					f();
					return true;
				}
				stopEventPropagation(ev);
				return false;
			}
			if (ev.isArrowLeft || ev.isArrowRight || ev.isBackspace || ev.isDelete || ev.isHome || ev.isEnd) {
				f();
				return true;
			}
			stopEventPropagation(ev);
			return false;
		};
		input.onblur = function(ev) {
			if (input.value.length == 0 && (!config || !config.can_be_null)) input.value = config && config.min ? config.min : 0;
			var i = parseInt(input.value);
			if (config && config.min && i < config.min) input.value = config.min;
			if (config && config.max && i > config.max) input.value = config.max;
			f();
		};
		this.element = input;
		this.element.typed_field = this;
		this.getCurrentData = function() {
			if (input.value.length == 0) return config && config.can_be_null ? null : config.min;
			return parseInt(input.value);
		};
		this.setData = function(data) {
			if (data == null) input.value = "";
			else input.value = data;
			f();
		};
		this.signal_error = function(error) {
			input.style.border = error ? "1px solid red" : "";
		};
	} else {
		this.element = document.createTextNode(data == null ? "" : data);
		this.element.typed_field = this;
		this.setData = function(data) {
			var text = data == null ? "" : data;
			if (this.element.nodeValue == text) return;
			this.element.nodeValue = text;
			if (data == this.originalData) {
				if (onunchanged) onunchanged(this);
			} else {
				if (onchanged) onchanged(this, data);
			}
		};
		this.getCurrentData = function() {
			var s = this.element.nodeValue;
			if (s.length == 0) return null;
			return parseInt(s);
		};
		this.signal_error = function(error) {
			this.element.style.color = error ? "red" : "";
		};
	}
}
if (typeof typed_field != 'undefined') {
	field_integer.prototype = new typed_field();
	field_integer.prototype.constructor = field_integer;		
}
