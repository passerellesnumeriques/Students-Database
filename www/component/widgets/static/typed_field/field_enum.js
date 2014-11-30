/* #depends[typed_field.js] */
/** Enum field: if editable, it will be a combo box (select element), else only a simple text node
 * @constructor
 * @param config must contain:<ul><li><code>can_be_null</code>: boolean</li><li><code>possible_values</code>: an array of element, each element can be (1) a string, which will be displayed and used as key, (2) an array of 2 elements: the key and the string to display</li></ul>
 */
function field_enum(data,editable,config) {
	typed_field.call(this, data, editable, config);
}
field_enum.prototype = new typed_field();
field_enum.prototype.constructor = field_enum;
field_enum.prototype.canBeNull = function() { return this.config.can_be_null; };
field_enum.prototype.getPossibleValues = function() {
	var values = [];
	for (var i = 0; i < this.config.possible_values.length; ++i) {
		if (this.config.possible_values[i] instanceof Array)
			values.push(this.config.possible_values[i][1]);
		else
			values.push(this.config.possible_values[i]);
	}
	return values;
};
field_enum.prototype.compare = function(v1,v2) {
	for (var i = 0; i < this.config.possible_values.length; ++i)
		if (this.config.possible_values[i] instanceof Array) {
			if (this.config.possible_values[i][0] == v1)
				v1 = this.config.possible_values[i][1];
			if (this.config.possible_values[i][0] == v2)
				v2 = this.config.possible_values[i][1];
		}
	if (v1 == null) return v2 == null ? 0 : -1;
	if (v2 == null) return 1;
	return v1.localeCompare(v2);
};
field_enum.prototype.exportCell = function(cell) {
	var val = this.getCurrentData();
	if (val == null)
		cell.value = "";
	else {
		cell.value = null;
		for (var i = 0; i < this.config.possible_values.length; ++i) {
			if (this.config.possible_values[i] instanceof Array) {
				if (this.config.possible_values[i][0] == val) {
					cell.value = this.config.possible_values[i][1];
					break;
				}
			} else if (this.config.possible_values[i] == val) {
				cell.value = val;
				break;
			}
		}
		if (call.value === null)
			cell.value = val;
	}
};
field_enum.prototype._create = function(data) {
	if (this.editable) {
		var t=this;
		var select = document.createElement("SELECT");
		var selected = 0;
		var o;
		o = document.createElement("OPTION");
		o.value = "";
		select.add(o);
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
			if (data == o.value) selected = i+1;
		}
		select.onclick = function(ev) { stopEventPropagation(ev); };
		select.selectedIndex = selected;
		select.style.margin = "0px";
		select.style.padding = "0px";
		select.onchange = function() { t._datachange(); };
		select.onblur = function() { t._datachange(); };
		listenEvent(select, 'focus', function() { t.onfocus.fire(); });
		this.element.appendChild(select);
		this._getEditedData = function() {
			if (select.selectedIndex <= 0) return null;
			return select.options[select.selectedIndex].value; 
		};
		this._setData = function(data, from_input) {
			var found = false;
			for (var i = 0; i < select.options.length; ++i)
				if (select.options[i].value == data) {
					select.selectedIndex = i;
					found = true;
					break;
				}
			if (!found && from_input)
				for (var i = 0; i < select.options.length; ++i)
					if (select.options[i].text.isSame(data)) {
						select.selectedIndex = i;
						found = true;
						break;
					}
			if (!found) return this._data;
			return data;
		};
		this.signal_error = function(error) {
			this.error = error;
			select.style.border = error ? "1px solid red" : "";
			select.title = error ? error : "";
		};
		this.validate = function() {
			var err = null;
			if (!this.config.can_be_null && select.selectedIndex == 0)
				err = "Please select a value";
			this.signal_error(err);
		};
		this.fillWidth = function(cache) {
			// calculate the minimum width of the select, to be able to see it...
			if (!cache) {
				cache = {onavail:[]};
				layout.readLayout(function() {
					var style = getComputedStyle(select);
					layout.three_steps_process(function() {
						var sel = document.createElement("SELECT");
						sel.style.display = "inline-block";
						sel.style.position = "absolute";
						sel.style.top = "-10000px";
						sel.style.fontFamily = style.fontFamily;
						sel.style.fontSize = style.fontSize;
						sel.style.fontWeight = style.fontWeight;
						var max = null;
						for (var i = 0; i < select.options.length; ++i) {
							var s = select.options[i].text;
							if (max == null || s.length > max.length) max = s;
						}
						if (max == null) max = "";
						var o = document.createElement("OPTION");
						o.text = max;
						sel.add(o);
						t.element.appendChild(sel);
						return sel;
					}, function(sel) {
						return {sel:sel,w:sel.offsetWidth};
					}, function(o) {
						t.element.removeChild(o.sel);
						t.element.style.width = "100%";
						select.style.width = "100%";
						select.style.minWidth = (o.w+27)+"px";
						cache.w = o.w+27;
						for (var i = 0; i < cache.onavail.length; ++i)
							cache.onavail[i]();
						cache.onavail = null;
					});
				});
				return cache;
			}
			if (!cache.w) cache.onavail.push(function() {
				t.element.style.width = "100%";
				select.style.width = "100%";
				select.style.minWidth = cache.w+"px";
			});
			else {
				t.element.style.width = "100%";
				select.style.width = "100%";
				select.style.minWidth = cache.w+"px";
			}
			return cache;
		};
		this.focus = function() { select.focus(); };
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
		//this.element.style.height = "100%";
		this._setData = function(data) {
			this.text.nodeValue = this.get_text_from_data(data);
			return data;
		};
		this.signal_error = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};