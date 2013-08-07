/** Text field: if editable, it will be a text input, else only a simple text node
 * @constructor
 * @param config can contain: <code>max_length</code>
 */
function field_text(data,editable,onchanged,onunchanged,config) {
	typed_field.call(this, data, editable, onchanged, onunchanged);
	if (editable) {
		var t=this;
		var input = document.createElement("INPUT");
		input.type = "text";
		if (config && config.max_length) input.max_length = config.max_length;
		if (data) input.value = data;
		input.style.margin = "0px";
		input.style.padding = "0px";
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
		this.getCurrentData = function() { return input.value; };
	} else {
		this.element = document.createTextNode(data);
	}
}
if (typeof typed_field != 'undefined')
field_text.prototype = new typed_field;
field_text.prototype.constructor = field_text;