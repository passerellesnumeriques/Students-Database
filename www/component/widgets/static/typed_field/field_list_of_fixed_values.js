/* #depends[typed_field.js] */
function field_list_of_fixed_values(data,editable,config) {
	if (data == null) data = [];
	typed_field.call(this, data, editable, config);
}
field_list_of_fixed_values.prototype = new typed_field();
field_list_of_fixed_values.prototype.constructor = field_list_of_fixed_values;		
field_list_of_fixed_values.prototype.canBeNull = function() { return true; };		
field_list_of_fixed_values.prototype._getValue = function(key) {
	var value = null;
	for (var j = 0; j < this.config.possible_values.length; ++j)
		if (this.config.possible_values[j][0] == key) {
			value = this.config.possible_values[j][1];
			break;
		}
	if (value == null) value = "?Invalid Key "+key+"?";
	return value;
};
field_list_of_fixed_values.prototype._create = function(data) {
	if (this.editable) {
		var t=this;
		this.data = [];
		this.data_elements = [];
		this._addElement = function(key) {
			t.data.push(key);
			var text = document.createElement("SPAN");
			text.appendChild(document.createTextNode(this._getValue(key)));
			var remove = document.createElement("IMG");
			remove.src = theme.icons_10.remove;
			remove.style.verticalAlign = "bottom";
			remove.style.paddingLeft = "2px";
			remove.style.paddingRight = "2px";
			remove.style.cursor = "pointer";
			remove.data_index = t.data.length-1;
			remove.onclick = function() {
				if (this.data_index > 0)
					t.element.removeChild(t.data_elements[this.data_index].comma);
				t.element.removeChild(t.data_elements[this.data_index].text);
				t.element.removeChild(t.data_elements[this.data_index].remove);
				t.data.splice(this.data_index,1);
				t.data_elements.splice(this.data_index,1);
				for (var i = this.data_index; i < t.data_elements.length; ++i)
					t.data_elements[i].remove.data_index = i;
				t._datachange();
			};
			var comma = null;
			if (t.data.length > 1) comma = document.createTextNode(", ");
			if (comma != null)
				this.element.insertBefore(comma, this.add_button);
			this.element.insertBefore(text, this.add_button);
			this.element.insertBefore(remove, this.add_button);
			this.data_elements.push({comma:comma,text:text,remove:remove});
		};
		this.add_button = document.createElement("IMG");
		this.add_button.src = theme.icons_10.add;
		this.add_button.className = "button";
		this.add_button.style.verticalAlign = "bottom";
		this.add_button.onclick = function() {
			require("context_menu.js",function(){
				var menu = new context_menu();
				for (var i = 0; i < t.config.possible_values.length; ++i) {
					var val = t.config.possible_values[i];
					var found = false;
					for (var j = 0; j < t.data.length; ++j)
						if (t.data[j] == val[0]) { found = true; break; }
					if (found) continue;
					var item = document.createElement("DIV");
					item.className = "context_menu_item";
					item.appendChild(document.createTextNode(val[1]));
					item.key = val[0];
					item.onclick = function() {
						t._addElement(this.key);
						t._datachange();
					};
					menu.addItem(item);
				}
				if (menu.getItems().length > 0)
					menu.showBelowElement(t.add_button);
			});
		};
		this.element.appendChild(this.add_button);
		for (var i = 0; i < data.length; ++i)
			this._addElement(data[i]);
		
		this.getCurrentData = function() { return this.data; };
	} else {
		var s = "";
		for (var i = 0; i < data.length; ++i) {
			if (s.length > 0) s += ", ";
			s += this._getValue(data[i]);
		}
		this.element.appendChild(document.createTextNode(s));
	}
};
