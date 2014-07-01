/* #depends[typed_field.js] */
if (typeof require != 'undefined') require("input_utils.js");
/** Text field: if editable, it will be an autoresize text input, else only a simple text node
 * @constructor
 * @param config can contain: <code>max_length</code> (maximum number of characters), <code>min_length</code> optional minimum length, <code>can_be_null</code> optionally if the field is empty it will means the data is null, <code>min_size</code> (minimum size for autoresize) or <code>fixed_size</code> (no autoresize), <code>style</code> additional style to give
 */
function field_text(data,editable,config) {
	if (data == null && config && !config.can_be_null) data = "";
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
		input.onclick = function(ev) { this.focus(); stopEventPropagation(ev); return false; };
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
			if (s == null || s.length == 0) {
				if (!this.canBeNull()) err = "Cannot be empty";
			} else {
				if (t.config && t.config.min_length) {
					if (s.length < t.config.min_length)
						err = "Must have at least "+t.config.min_length+" character"+(t.config.min_length>1?"s":"");
				}
			}
			t.signal_error(err);
		};
		input.onkeyup = function() { setTimeout(function() { t._datachange(); },1); };
		input.onblur = function() { t._datachange(); };
		input.onchange = function() { t._datachange(); };
		listenEvent(input, 'focus', function() { t.onfocus.fire(); });
		this.element.appendChild(input);
		if (this.config && this.config.fixed_size)
			input.size = this.config.fixed_size;
		else
			require("input_utils.js",function(){inputAutoresize(input,t.config && t.config.min_size ? t.config.min_size : 0);});
		
		this._getEditedData = function() {
			var data = input.value;
			if (data.length == 0 && t.config && t.config.can_be_null) data = null;
			return data; 
		};
		this._setData = function(data) {
			if (data == null) data = "";
			input.value = data;
			if (input.autoresize) input.autoresize();
		};
		this._fillWidth = this.fillWidth;
		this.fillWidth = function() {
			this._fillWidth();
			if (input.autoresize) input.setMinimumSize(-1);
			else if (!t.config) t.config = {min_size:-1};
			else t.config.min_size = -1;
		};
		this.signal_error = function(error) {
			this.error = error;
			input.style.border = error ? "1px solid red" : "";
			input.title = error ? error : "";
		};
	} else {
		this.element.appendChild(document.createTextNode(data));
		if (this.config && this.config.style)
			for (var s in this.config.style)
				this.element.style[s] = this.config.style[s];
		this._setData = function(data) {
			this.element.childNodes[0].nodeValue = data;
			if (data == null || data.length == 0) {
				var e = document.createElement("I");
				e.innerHTML = "&nbsp; &nbsp;";
				this.element.appendChild(e);
			} else {
				if (this.element.childNodes.length == 2) this.element.removeChild(this.element.childNodes[1]);
			}
		};
		this._setData(data);
		this.signal_error = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};