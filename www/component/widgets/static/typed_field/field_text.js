/* #depends[typed_field.js] */
if (typeof require != 'undefined') require("autoresize_input.js");
/** Text field: if editable, it will be an autoresize text input, else only a simple text node
 * @constructor
 * @param config can contain: <code>max_length</code> (maximum number of characters), <code>min_length</code> optional minimum length, <code>can_be_null</code> optionally if the field is empty it will means the data is null, <code>min_size</code> (minimum size for autoresize) or <code>fixed_size</code> (no autoresize), <code>style</code> additional style to give
 */
function field_text(data,editable,config) {
	if (data == null) data = "";
	typed_field.call(this, data, editable, config);
}
field_text.prototype = new typed_field();
field_text.prototype.constructor = field_text;		
field_text.prototype.canBeNull = function() {
	if (!this.config) return true;
	if (this.config.can_be_null) return true;
	if ((typeof this.config.min_length != 'undefined') && this.config.min_length == 0) return true;
	return false;
};		
field_text.prototype._create = function(data) {
	if (this.editable) {
		var t=this;
		var input = document.createElement("INPUT");
		t.input = input;
		input.type = "text";
		if (this.config && this.config.max_length) input.maxLength = this.config.max_length;
		if (data) input.value = data;
		input.style.margin = "0px";
		input.style.padding = "0px";
		if (this.config && this.config.style)
			for (var s in this.config.style)
				input.style[s] = this.config.style[s];

		this.validate = function() {
			var err = null;
			var s = t.getCurrentData();
			if (s != null && t.config && t.config.min_length) {
				if (s.length < t.config.min_length)
					err = "Cannot be empty";
			}
			t.signal_error(err);
		};
		var f = function() { setTimeout(function() {
			t.validate();
			t._datachange();
		},1); };
		input.onkeyup = f;
		input.onblur = f;
		input.onchange = f;

		if (this.config && this.config.fixed_size)
			input.size = this.config.fixed_size;
		else
			require("autoresize_input.js",function(){autoresize_input(input,t.config && t.config.min_size ? t.config.min_size : 0);});
		this.element.appendChild(input);
		this.getCurrentData = function() {
			var data = input.value;
			if (data.length == 0 && t.config && t.config.can_be_null) data = null;
			return data; 
		};
		this.setData = function(data) {
			if (data == null) data = "";
			input.value = data;
			input.onchange();
		};
		this.signal_error = function(error) {
			this.error = error;
			input.style.border = error ? "1px solid red" : "";
			input.title = error ? error : "";
		};
		this.validate();
	} else {
		this.element.appendChild(document.createTextNode(data));
		if (this.config && this.config.style)
			for (var s in this.config.style)
				this.element.style[s] = this.config.style[s];
		this.setData = function(data, first) {
			if (!first && this.element.childNodes[0].nodeValue == data) return;
			this.element.childNodes[0].nodeValue = data;
			if (data == null || data.length == 0) {
				var e = document.createElement("I");
				e.innerHTML = "&nbsp; &nbsp;";
				this.element.appendChild(e);
			} else {
				if (this.element.childNodes.length == 2) this.element.removeChild(this.element.childNodes[1]);
			}
			if (!first) this._datachange();
		};
		this.setData(data, true);
		this.getCurrentData = function() {
			return this.element.childNodes[0].nodeValue;
		};
		this.signal_error = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};