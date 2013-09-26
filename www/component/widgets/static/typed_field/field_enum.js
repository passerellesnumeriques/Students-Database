/** Enum field: if editable, it will be a combo box (select element), else only a simple text node
 * @constructor
 * @param config must contain:<ul><li><code>can_be_empty</code>: boolean</li><li><code>possible_values</code>: an array of element, each element can be (1) a string, which will be displayed and used as key, (2) an array of 2 elements: the key and the string to display</li></ul>
 */
function field_enum(data,editable,onchanged,onunchanged,config) {
	typed_field.call(this, data, editable, onchanged, onunchanged);
	if (editable) {
		var t=this;
		var select = document.createElement("SELECT");
		var selected = 0;
		var o;
		if (config.can_be_empty) {
			o = document.createElement("OPTION");
			o.value = "";
			select.add(o);
		}
		for (var i = 0; i < config.possible_values.length; ++i) {
			o = document.createElement("OPTION");
			if (config.possible_values[i] instanceof Array) {
				o.value = config.possible_values[i][0];
				o.text = config.possible_values[i][1];
			} else {
				o.value = config.possible_values[i];
				o.text = config.possible_values[i];
			}
			select.add(o);
			if (data == config.possible_values[i]) selected = i+(config.can_be_empty?1:0);
		}
		select.selectedIndex = selected;
		select.style.margin = "0px";
		select.style.padding = "0px";
		var f = function() {
			setTimeout(function() {
				var val = select.selectedIndex >= 0 ? select.options[select.selectedIndex].value : null;
				if (val != data) {
					if (onchanged)
						onchanged(t, val);
				} else {
					if (onunchanged)
						onunchanged(t);
				}
			},1);
		};
		select.onchange = f;
		select.onblur = f;
		this.element = select;
		this.element.typed_field = this;
		this.getCurrentData = function() { return select.selectedIndex >= 0 ? select.options[select.selectedIndex].value : null; };
		this.setData = function(data) {
			for (var i = 0; i < select.options.length; ++i)
				if (select.options[i].value == data) {
					select.selectedIndex = i;
					f();
					break;
				}
		};
		this.signal_error = function(error) {
			select.style.border = error ? "1px solid red" : "";
		};
	} else {
		this.get_text_from_data = function(data) {
			var text = "invalid value";
			if (data == null) text = "";
			else if (!config || !config.possible_values)
				text = data;
			else {
				for (var i = 0; i < config.possible_values.length; ++i) {
					if (config.possible_values[i] instanceof Array) {
						if (data == config.possible_values[i][0]) {
							text = config.possible_values[i][1];
							break;
						}
					} else {
						if (data == config.possible_values[i]) {
							text = data;
							break;
						}
					}
				}
			}
			return text;
		};
		this.element = document.createTextNode(this.get_text_from_data(data));
		this.data = data;
		this.element.typed_field = this;
		this.setData = function(data) {
			if (this.data == data) return;
			this.element.nodeValue = this.get_text_from_data(data);
			this.data = data;
			if (data == this.originalData) {
				if (onunchanged) onunchanged(this);
			} else {
				if (onchanged) onchanged(this, data);
			}
		};
		this.getCurrentData = function() {
			return this.data;
		};
		this.signal_error = function(error) {
			this.element.style.color = error ? "red" : "";
		};
	}
}
if (typeof require != 'undefined')
	require("typed_field.js",function(){
		field_enum.prototype = new typed_field();
		field_enum.prototype.constructor = field_enum;
	});