/** Enum field: if editable, it will be a combo box (select element), else only a simple text node
 * @constructor
 * @param config must contain:<ul><li><code>can_be_empty</code>: boolean</li><li><code>possible_values</code>: an array of string</li></ul>
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
			o.value = config.possible_values[i];
			o.text = config.possible_values[i];
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
		this.getCurrentData = function() { return select.selectedIndex >= 0 ? select.options[select.selectedIndex].value : null; };
	} else {
		this.element = document.createTextNode(data);
	}
}
if (typeof typed_field != 'undefined')
field_enum.prototype = new typed_field;
field_enum.prototype.constructor = field_enum;