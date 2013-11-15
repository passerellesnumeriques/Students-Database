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
		var input = document.createElement("INPUT");
		input.type = "text";
		if (this.config && this.config.min && this.config.max) {
			var m = Math.max((""+this.config.min).length,(""+this.config.max).length);
			input.maxLength = m;
		}
		if (data) input.value = data;
		input.style.margin = "0px";
		input.style.padding = "0px";
		var onkeyup = new Custom_Event();
		input.onkeyup = function(e) { onkeyup.fire(e); };
		var f = function() { setTimeout(function() { t._datachange(); },1); };
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
			if (input.value.length == 0 && (!this.config || !this.config.can_be_null)) input.value = this.config && this.config.min ? this.config.min : 0;
			var i = parseInt(input.value);
			if (this.config && this.config.min && i < this.config.min) input.value = this.config.min;
			if (this.config && this.config.max && i > this.config.max) input.value = this.config.max;
			f();
		};
		require("autoresize_input.js",function(){autoresize_input(input);});
		this.element.appendChild(input);
		this.getCurrentData = function() {
			if (input.value.length == 0) return this.config && this.config.can_be_null ? null : this.config.min;
			return parseInt(input.value);
		};
		this.setData = function(data) {
			if (data == null) input.value = "";
			else input.value = data;
			f();
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
	} else {
		this.element.appendChild(this.text = document.createTextNode(data == null ? "" : data));
		this.setData = function(data) {
			var s = data == null ? "" : data;
			if (this.text.nodeValue == s) return;
			this.text.nodeValue = s;
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