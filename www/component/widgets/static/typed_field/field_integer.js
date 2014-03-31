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
		if (t.config) {
			if (typeof data == 'string') {
				data = parseInt(data);
				if (isNaN(data)) data = null;
			}
			if (!t.config.can_be_null) {
				if (data == null) data = t.config.min;
			}
			if (data != null && data < t.config.min) data = t.config.min;
			if (data != null && data > t.config.max) data = t.config.max;
		}
		if (data) t.input.value = data;
		t.input.style.margin = "0px";
		t.input.style.padding = "0px";
		var onkeyup = new Custom_Event();
		t.input.onkeyup = function(e) { onkeyup.fire(e); };
		var last_data = data;
		var f = function() { if (t.getCurrentData() == last_data) return; last_data = t.getCurrentData(); setTimeout(function() { t._datachange(); },1); };
		t.input.onkeydown = function(e) {
			var ev = getCompatibleKeyEvent(e);
			if (ev.isPrintable) {
				if (!isNaN(parseInt(ev.printableChar)) || ev.ctrlKey) {
					// digit: ok
					f();
					return true;
				}
				// not a digit
				if (ev.printableChar == "-" && t.input.value.length == 0) {
					// - at the beginning: ok
					f();
					return true;
				}
				stopEventPropagation(e);
				return false;
			}
			f();
			return true;
		};
		t.input.onblur = function(ev) {
			if (t.input.value.length == 0 && t.config && t.config.can_be_null) {}
			else {
				if (t.input.value.length == 0 && (!t.config || !t.config.can_be_null)) t.input.value = t.config && t.config.min ? t.config.min : 0;
				var i = parseInt(t.input.value);
				if (t.config && t.config.min && i < t.config.min) t.input.value = t.config.min;
				if (t.config && t.config.max && i > t.config.max) t.input.value = t.config.max;
			}
			f();
		};
		require("input_utils.js",function(){inputAutoresize(t.input);});
		this.element.appendChild(t.input);
		this.getCurrentData = function() {
			if (t.input.value.length == 0) return this.config && this.config.can_be_null ? null : this.config.min;
			return parseInt(t.input.value);
		};
		this.setData = function(data) {
			if (data == null) t.input.value = "";
			else t.input.value = data;
			f();
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
	} else {
		this.element.appendChild(this.text = document.createTextNode(data == null ? "" : data));
		this.setData = function(data) {
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
			this._datachange();
		};
		this.getCurrentData = function() {
			var s = this.text.nodeValue;
			if (s.length == 0) return null;
			return parseInt(s);
		};
		this.signal_error = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};