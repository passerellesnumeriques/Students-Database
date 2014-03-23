/* #depends[typed_field.js] */
/** Enum field: if editable, it will be a combo box (select element), else only a simple text node
 * @constructor
 * @param config must contain:<ul><li><code>can_be_empty</code>: boolean</li><li><code>possible_values</code>: an array of element, each element can be (1) a string, which will be displayed and used as key, (2) an array of 2 elements: the key and the string to display</li></ul>
 */
function field_enum(data,editable,config) {
	typed_field.call(this, data, editable, config);
}
field_enum.prototype = new typed_field();
field_enum.prototype.constructor = field_enum;
field_enum.prototype.canBeNull = function() { return this.config.can_be_empty; };		
field_enum.prototype._create = function(data) {
	if (this.editable) {
		var t=this;
		var select = document.createElement("SELECT");
		var selected = 0;
		var o;
		if (this.config.can_be_empty) {
			o = document.createElement("OPTION");
			o.value = "";
			select.add(o);
		}
		for (var i = 0; i < this.config.possible_values.length; ++i) {
			o = document.createElement("OPTION");
			if (this.config.possible_values[i] instanceof Array) {
				o.value = this.config.possible_values[i][0];
				o.text = this.config.possible_values[i][1];
			} else {
				o.value = this.config.possible_values[i];
				o.text = this.config.possible_values[i];
			}
			select.add(o);
			if (data == o.value) selected = i+(this.config.can_be_empty?1:0);
		}
		select.onclick = function(ev) { stopEventPropagation(ev); };
		select.selectedIndex = selected;
		select.style.margin = "0px";
		select.style.padding = "0px";
		var f = function() { setTimeout(function() { t._datachange(); },1); };
		select.onchange = f;
		select.onblur = f;
		this.element.appendChild(select);
		this.getCurrentData = function() {
			if (select.selectedIndex < 0) return null;
			if (this.config.can_be_empty && select.selectedIndex == 0) return null;
			return select.options[select.selectedIndex].value; 
		};
		this.setData = function(data) {
			for (var i = 0; i < select.options.length; ++i)
				if (select.options[i].value == data) {
					select.selectedIndex = i;
					f();
					break;
				}
		};
		this.signal_error = function(error) {
			this.error = error;
			select.style.border = error ? "1px solid red" : "";
		};
	} else {
		this.get_text_from_data = function(data) {
			var text = "invalid value: "+data;
			if (data == null) text = "";
			else if (!this.config || !this.config.possible_values)
				text = data;
			else {
				for (var i = 0; i < this.config.possible_values.length; ++i) {
					if (this.config.possible_values[i] instanceof Array) {
						if (data == this.config.possible_values[i][0]) {
							text = this.config.possible_values[i][1];
							break;
						}
					} else {
						if (data == this.config.possible_values[i]) {
							text = data;
							break;
						}
					}
				}
			}
			return text;
		};
		this.element.appendChild(this.text = document.createTextNode(this.get_text_from_data(data)));
		this.element.style.height = "16px";
		this.data = data;
		this.setData = function(data) {
			if (this.data == data) return;
			this.text.nodeValue = this.get_text_from_data(data);
			this.data = data;
			this._datachange();
		};
		this.getCurrentData = function() {
			return this.data;
		};
		this.signal_error = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};