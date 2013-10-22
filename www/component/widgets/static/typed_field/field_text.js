if (typeof require != 'undefined') require("autoresize_input.js");
/** Text field: if editable, it will be an autoresize text input, else only a simple text node
 * @constructor
 * @param config can contain: <code>max_length</code> (maximum number of characters), <code>min_size</code> (minimum size for autoresize) or <code>fixed_size</code> (no autoresize)
 */
function field_text(data,editable,onchanged,onunchanged,config) {
	if (data == null) data = "";
	typed_field.call(this, data, editable, onchanged, onunchanged);
	if (editable) {
		var t=this;
		var input = document.createElement("INPUT");
		input.type = "text";
		if (config && config.max_length) input.maxLength = config.max_length;
		if (data) input.value = data;
		input.style.margin = "0px";
		input.style.padding = "0px";
		if (config && config.fixed_size)
			input.size = config.fixed_size;
		else
			require("autoresize_input.js",function(){autoresize_input(input,config && config.min_size ? config.min_size : 0);});
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
		input.onkeyup = f;
		input.onblur = f;
		this.element = input;
		this.element.typed_field = this;
		this.getCurrentData = function() { return input.value; };
		this.setData = function(data) {
			input.value = data;
			f();
		};
		this.signal_error = function(error) {
			input.style.border = error ? "1px solid red" : "";
		};
	} else {
		this.element = document.createTextNode(data);
		this.element.typed_field = this;
		this.setData = function(data) {
			if (this.element.nodeValue == data) return;
			this.element.nodeValue = data;
			if (data == this.originalData) {
				if (onunchanged) onunchanged(this);
			} else {
				if (onchanged) onchanged(this, data);
			}
		};
		this.getCurrentData = function() {
			return this.element.nodeValue;
		};
		this.signal_error = function(error) {
			this.element.style.color = error ? "red" : "";
		};
	}
}
if (typeof typed_field != 'undefined') {
	field_text.prototype = new typed_field();
	field_text.prototype.constructor = field_text;		
}
