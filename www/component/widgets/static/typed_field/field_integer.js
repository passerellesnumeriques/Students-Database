/* #depends[typed_field.js] */
/** Integer field: if editable, it will be a text input, else only a simple text node
 * @constructor
 * @param config can contain: <code>min</code>, <code>max</code>, <code>can_be_null</code>
 */
function field_integer(data,editable,config) {
	if (data == null) data = "";
	typed_field.call(this, data, editable, config);
}
field_integer.prototype = new typed_field();
field_integer.prototype.constructor = field_integer;		
field_integer.prototype.canBeNull = function() { return this.config && this.config.can_be_null; };		
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
		t.input.onkeyup = function(e) { onkeyup.fire(e); };
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
			t.setData(t._getEditedData());
		};
		require("input_utils.js",function(){inputAutoresize(t.input);});
		this.element.appendChild(t.input);
		this._getEditedData = function() {
			var value = getValueFromInput();
			if (value == null) return null;
			return parseInt(value);
		};
		this._setData = function(data) {
			if (data == null) t.input.value = "";
			else t.input.value = data;
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
	} else {
		this.element.appendChild(this.text = document.createTextNode(data == null ? "" : data));
		this._setData = function(data) {
			var s = data == null ? "" : data;
			if (this.text.nodeValue == s) return;
			this.text.nodeValue = s;
			if (this.element.childNodes.length > 1)
				this.element.removeChild(this.element.childNodes[1]);
			if (s.length == 0) {
				var e = document.createElement("I");
				e.innerHTML = "&nbsp; &nbsp;";
				this.element.appendChild(e);
			}
		};
		this.signal_error = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};