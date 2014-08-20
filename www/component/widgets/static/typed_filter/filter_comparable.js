/* #depends[typed_filter.js] */
function filter_comparable(data, config, editable) {
	if (data == null) data = {type:'equals',value:null};
	if (!data.value_to) data.value_to = null;
	typed_filter.call(this, data, config, editable);
	
	this.select_type = document.createElement("SELECT");
	this.select_type.style.verticalAlign = "bottom";
	var o;
	o = document.createElement("OPTION"); o.value = 'equals'; o.text = "is equals to"; if (data.type == 'equals') o.selected = true; this.select_type.add(o);
	o = document.createElement("OPTION"); o.value = 'not_equals'; o.text = "is not equals to"; if (data.type == 'not_equals') o.selected = true; this.select_type.add(o);
	o = document.createElement("OPTION"); o.value = 'less'; o.text = "is less than"; if (data.type == 'less') o.selected = true; this.select_type.add(o);
	o = document.createElement("OPTION"); o.value = 'more'; o.text = "is greater than"; if (data.type == 'more') o.selected = true; this.select_type.add(o);
	o = document.createElement("OPTION"); o.value = 'less_equals'; o.text = "is less than or equals to"; if (data.type == 'less_equals') o.selected = true; this.select_type.add(o);
	o = document.createElement("OPTION"); o.value = 'more_equals'; o.text = "is greater than or equals to"; if (data.type == 'more_equals') o.selected = true; this.select_type.add(o);
	o = document.createElement("OPTION"); o.value = 'between'; o.text = "is between"; if (data.type == 'between') o.selected = true; this.select_type.add(o);
	o = document.createElement("OPTION"); o.value = 'not_between'; o.text = "is not between"; if (data.type == 'not_between') o.selected = true; this.select_type.add(o);
	if (config.can_be_null) {
		o = document.createElement("OPTION"); o.value = 'is_set'; o.text = "is set (not empty)"; if (data.type == 'is_set') o.selected = true; this.select_type.add(o);
		o = document.createElement("OPTION"); o.value = 'is_not_set'; o.text = "is not set (empty)"; if (data.type == 'is_not_set') o.selected = true; this.select_type.add(o);
	}
	
	this.element.appendChild(this.select_type);
	if (!editable) this.select_type.disabled = "disabled";
	
	this.isActive = function() {
		if (!t.field1) return true;
		switch (this.select_type.value) {
		case "is_set": case "is_not_set": return true;
		case "between": case "not_between": return t.field1.getCurrentData() != null && t.field2.getCurrentData() != null;
		default: return t.field1.getCurrentData() != null;
		}
	};
	
	var t=this;
	this.select_type.onchange = function() {
		data.type = this.value;
		if (t.span_to) {
			t.span_from.style.visibility = data.type != "is_set" && data.type != "is_not_set" ? "visible" : "hidden";
			t.span_to.style.visibility = data.type == "between" || data.type == "not_between" ? "visible" : "hidden";
		}
		t.onchange.fire(t);
	};
	
	require([["typed_field.js",config.value_field_classname+".js"]], function() {
		t.field1 = new window[config.value_field_classname](data.value, editable, objectCopy(config.value_field_config));
		t.field2 = new window[config.value_field_classname](data.value_to, editable, objectCopy(config.value_field_config));
		t.span_from = document.createElement("SPAN");
		t.span_from.appendChild(t.field1.getHTMLElement());
		t.element.appendChild(t.span_from);
		t.span_to = document.createElement("SPAN");
		t.span_to.appendChild(document.createTextNode(" and "));
		t.span_to.appendChild(t.field2.getHTMLElement());
		t.span_to.style.visibility = data.type == "between" || data.type == "not_between" ? "visible" : "hidden";
		t.element.appendChild(t.span_to);
		t.field1.getHTMLElement().style.verticalAlign = "bottom";
		t.field2.getHTMLElement().style.verticalAlign = "bottom";
		t.span_to.style.verticalAlign = "bottom";
		layout.invalidate(t.element);
		t.field1.onchange.add_listener(function() {
			data.value = t.field1.getCurrentData();
			if (typeof t.field2.setMinimum != 'undefined')
				t.field2.setMinimum(data.value);
			t.onchange.fire(t);
		});
		t.field2.onchange.add_listener(function() {
			data.value_to = t.field2.getCurrentData();
			if (typeof t.field1.setMaximum != 'undefined')
				t.field1.setMaximum(data.value_to);
			t.onchange.fire(t);
		});
	});
	
	this.focus = function () {
		if (!t.field1) {
			setTimeout(t.focus,25);
			return;
		}
		t.field1.focus();
	};
}
filter_comparable.prototype = new typed_filter;
filter_comparable.prototype.constructor = filter_comparable;
filter_comparable.prototype.can_multiple = true;
