/* #depends[typed_field.js] */
/**
 * List of values, each value being a choice among possible values.
 * If editable, the user can add and remove values.
 * Configuration must contain <code>possible_values</code> which is an array, each element being a possible value. Each possible value is an array with 2 elements: first is the key, second is the text to display.
 */
function field_list_of_fixed_values(data,editable,config) {
	if (data == null) data = [];
	typed_field_multiple.call(this, data, editable, config);
}
field_list_of_fixed_values.prototype = new typed_field_multiple();
field_list_of_fixed_values.prototype.constructor = field_list_of_fixed_values;		
field_list_of_fixed_values.prototype.canBeNull = function() { return true; };
/**
 * Retrieve the text from a key
 * @param {Object} key the key to search
 * @returns {String} the text
 */
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
field_list_of_fixed_values.prototype.exportCell = function(cell) {
	var val = this.getCurrentData();
	cell.value = "";
	if (val)
		for (var i = 0; i < val.length; ++i) {
			if (i > 0) cell.value += ", ";
			cell.value += this._getValue(val[i]);
		}
};
field_list_of_fixed_values.prototype._create = function(data) {
	if (this.editable) {
		var t=this;
		this._elements = [];
		this._addElement = function(key) {
			var text = document.createElement("SPAN");
			text.style.whiteSpace = 'nowrap';
			text.appendChild(document.createTextNode(this._getValue(key)));
			var remove = document.createElement("IMG");
			remove.src = theme.icons_10.remove;
			remove.style.verticalAlign = "bottom";
			remove.style.paddingLeft = "2px";
			remove.style.paddingRight = "2px";
			remove.style.cursor = "pointer";
			remove.data_index = t._elements.length;
			remove.onclick = function(ev) {
				if (this.data_index > 0)
					t.element.removeChild(t._elements[this.data_index].comma);
				t.element.removeChild(t._elements[this.data_index].text);
				t._elements.splice(this.data_index,1);
				for (var i = this.data_index; i < t._elements.length; ++i)
					t._elements[i].remove.data_index = i;
				t._datachange();
				stopEventPropagation(ev);
			};
			text.appendChild(remove);
			var comma = null;
			if (t._elements.length > 0) comma = document.createTextNode(", ");
			if (comma != null)
				this.element.insertBefore(comma, this.add_button);
			this.element.insertBefore(text, this.add_button);
			this._elements.push({comma:comma,text:text,remove:remove,key:key});
		};
		this.add_button = document.createElement("BUTTON");
		this.add_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
		this.add_button.className = "flat small_icon";
		this.add_button.style.verticalAlign = "middle";
		this.add_button.onclick = function(ev) {
			require("context_menu.js",function(){
				var menu = new context_menu();
				for (var i = 0; i < t.config.possible_values.length; ++i) {
					var val = t.config.possible_values[i];
					var found = false;
					for (var j = 0; j < t._elements.length; ++j)
						if (t._elements[j].key == val[0]) { found = true; break; }
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
			stopEventPropagation(ev);
		};
		this.element.appendChild(this.add_button);
		
		this._getEditedData = function() {
			var list = [];
			for (var i = 0; i < this._elements.length; ++i)
				list.push(this._elements[i].key);
			return list; 
		};
		this.addData = function(new_data) {
			this._addElement(new_data);
		};
		this.getNbData = function() {
			return this._elements.length;
		};
		this.resetData = function() {
			var removes = [];
			for (var i = 0; i < this._elements.length; ++i) removes.push(this._elements[i].remove);
			for (var i = 0; i < removes.length; ++i)
				removes[i].onclick();
		};
		this._setData = function(data) {
			this.element.removeAllChildren();
			this.element.appendChild(this.add_button);
			this._elements = [];
			if (data != null)
				for (var i = 0; i < data.length; ++i)
					this._addElement(data[i]);
			return data;
		};
		this._setData(data);
	} else {
		this.addData = function(new_data) {
			var text = this.element.childNodes[0];
			if (text.nodeValue.length > 0) text.nodeValue += ", ";
			text.nodeValue += this._getValue(new_data);
			this.data.push(new_data);
		};
		this._setData = function(data) {
			var s = "";
			this.data = [];
			for (var i = 0; i < data.length; ++i) {
				if (s.length > 0) s += ", ";
				s += this._getValue(data[i]);
				this.data.push(data[i]);
			}
			this.element.removeAllChildren();
			this.element.appendChild(document.createTextNode(s));
			return data;
		};
		this._setData(data);
	}
};
